<?php

/**
 * Cart — Модель корзины покупок
 * 
 * НАЗНАЧЕНИЕ:
 * Корзина покупателя: хранение выбранных товаров до оформления заказа.
 * Поддержка гостевой корзины (по сессии) и авторизованной (по user_id).
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - Идентификация: user_id (для авторизованных), session_id (для гостей)
 * - Товар: product_id, quantity, price
 * - Опции: size, color
 * 
 * СТАТИЧЕСКИЕ МЕТОДЫ:
 * - getItems(): получение всех товаров корзины
 * - getTotal(): расчёт общей суммы
 * - getItemsCount(): количество позиций
 * - add(): добавление товара
 * - clear(): очистка корзины
 * 
 * СВЯЗИ:
 * - Product (товар в корзине)
 * 
 * ОСОБЕННОСТИ:
 * - Автоматическое удаление неактивных товаров
 * - Поддержка гостевой корзины через сессию
 * - Слияние корзин при авторизации
 */
namespace app\backend\modules\cart\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\catalog\models\Product;

/**
 * Модель корзины
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property int $product_id
 * @property int $quantity
 * @property string $size
 * @property string $color
 * @property float $price
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property Product $product
 */
class Cart extends ActiveRecord
{
    public static function tableName()
    {
        return 'cart';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['product_id', 'quantity', 'price'], 'required'],
            [['user_id', 'product_id', 'quantity'], 'integer'],
            [['price'], 'number'],
            [['quantity'], 'integer', 'min' => 1, 'max' => 99],
            [['session_id'], 'string', 'max' => 255],
            [['size'], 'string', 'max' => 20],
            [['color'], 'string', 'max' => 50],
        ];
    }

    /**
     * Связь с товаром
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * Добавить товар в корзину
     */
    public static function add($productId, $quantity = 1, $size = null, $color = null)
    {
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        
        // Гарантируем наличие сессии
        if (Yii::$app->session->getIsActive() === false) {
            Yii::$app->session->open();
        }
        $sessionId = Yii::$app->session->id;

        $product = Product::findOne($productId);
        if (!$product) {
            return false;
        }
        
        // Проверка наличия товара
        if ($product->stock_status === Product::STOCK_OUT_OF_STOCK) {
            Yii::$app->session->setFlash('error', 'Товар отсутствует на складе');
            return false;
        }
        
        // Валидация размера — только если для товара есть записи в product_size
        if ($size !== null) {
            $hasSizeRecords = \app\backend\modules\catalog\models\ProductSize::find()
                ->where(['product_id' => $productId])
                ->exists();
            if ($hasSizeRecords) {
                $sizeModel = \app\backend\modules\catalog\models\ProductSize::find()
                    ->where(['product_id' => $productId])
                    ->andWhere(['OR',
                        ['eu_size' => $size],
                        ['size' => $size],
                        new \yii\db\Expression('`size` LIKE :sizePrefix', [':sizePrefix' => $size . ' %']),
                    ])
                    ->one();
                if (!$sizeModel) {
                    Yii::$app->session->setFlash('error', 'Указанный размер недоступен');
                    return false;
                }
            }
        }

        // Проверяем - есть ли уже такой товар
        $cart = self::findOne([
            'product_id' => $productId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'size' => $size,
            'color' => $color,
        ]);

        if ($cart) {
            // Атомарное обновление через UPDATE ... SET quantity = quantity + N,
            // чтобы исключить race condition при одновременном добавлении товара
            if ($cart->quantity + $quantity > 99) {
                Yii::$app->session->setFlash('error', 'Максимальное количество одного товара в корзине — 99');
                return false;
            }
            return (bool) self::updateAll(
                ['quantity' => new \yii\db\Expression('LEAST(quantity + :q, 99)', [':q' => $quantity])],
                ['id' => $cart->id]
            );
        } else {
            $cart = new self([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'size' => $size,
                'color' => $color,
                'price' => $product->price,
            ]);
        }

        if (!$cart->save()) {
            Yii::error('Cart::add() save failed: ' . json_encode($cart->errors), 'cart');
            return false;
        }
        return true;
    }

    /**
     * Получить товары корзины для текущего пользователя/сессии
     */
    public static function getItems()
    {
        // Гарантируем активную сессию
        if (Yii::$app->session->getIsActive() === false) {
            Yii::$app->session->open();
        }
        
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        $query = self::find()->with('product');

        if ($userId) {
            $query->where(['user_id' => $userId]);
        } else {
            $query->where(['session_id' => $sessionId, 'user_id' => null]);
        }

        return $query->all();
    }

    /**
     * Получить количество товаров в корзине (оптимизировано)
     */
    public static function getItemsCount()
    {
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        $query = self::find();
        
        if ($userId) {
            $query->where(['user_id' => $userId]);
        } else {
            $query->where(['session_id' => $sessionId, 'user_id' => null]);
        }
        
        // Оптимизированный SQL запрос вместо PHP цикла
        return (int) $query->sum('quantity') ?: 0;
    }

    /**
     * Получить общую сумму корзины (оптимизировано)
     */
    public static function getTotal()
    {
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        $query = self::find();
        
        if ($userId) {
            $query->where(['user_id' => $userId]);
        } else {
            $query->where(['session_id' => $sessionId, 'user_id' => null]);
        }
        
        // Оптимизированный SQL запрос вместо PHP цикла
        return (float) $query->sum('price * quantity') ?: 0;
    }

    /**
     * Очистить корзину
     */
    public static function clear()
    {
        // Гарантируем активную сессию
        if (Yii::$app->session->getIsActive() === false) {
            Yii::$app->session->open();
        }
        
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        if ($userId) {
            return self::deleteAll(['user_id' => $userId]);
        } else {
            return self::deleteAll(['session_id' => $sessionId, 'user_id' => null]);
        }
    }

    /**
     * Обновить количество товара
     */
    public function updateQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this->save();
    }

    /**
     * Получить подытог для этой позиции
     */
    public function getSubtotal()
    {
        return $this->price * $this->quantity;
    }
}
