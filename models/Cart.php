<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

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
        $sessionId = Yii::$app->session->id;

        $product = Product::findOne($productId);
        if (!$product) {
            return false;
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
            $cart->quantity += $quantity;
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

        return $cart->save();
    }

    /**
     * Получить товары корзины для текущего пользователя/сессии
     */
    public static function getItems()
    {
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
     * Получить количество товаров в корзине
     */
    public static function getItemsCount()
    {
        $items = self::getItems();
        $count = 0;
        foreach ($items as $item) {
            $count += $item->quantity;
        }
        return $count;
    }

    /**
     * Получить общую сумму корзины
     */
    public static function getTotal()
    {
        $items = self::getItems();
        $total = 0;
        foreach ($items as $item) {
            $total += $item->price * $item->quantity;
        }
        return $total;
    }

    /**
     * Очистить корзину
     */
    public static function clear()
    {
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
