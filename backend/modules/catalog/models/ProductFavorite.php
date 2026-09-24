<?php

namespace app\backend\modules\catalog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\account\models\Customer;

/**
 * Модель ProductFavorite (Избранный товар)
 *
 * CMP-446: product_favorite.user_id хранит id покупателя (Customer::getCurrentCustomerId()),
 * не админского пользователя — имя поля исторически совпало с колонкой user_id, но по смыслу
 * это customer_id (тот же паттерн, что в ProductReview и CMP-435 cart.user_id).
 *
 * @property int $id
 * @property int|null $user_id ID покупателя (Customer), не админского пользователя
 * @property int $product_id ID товара
 * @property string|null $session_id ID сессии (для неавторизованных)
 * @property int $created_at
 *
 * @property Customer $customer
 * @property Product $product
 */
class ProductFavorite extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_favorite';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false, // Нет поля updated_at
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id'], 'required'],
            [['user_id', 'product_id'], 'integer'],
            [['session_id'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'targetClass' => Customer::class, 'targetAttribute' => 'id'],
            [['product_id'], 'exist', 'targetClass' => Product::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'product_id' => 'Товар',
            'session_id' => 'ID сессии',
            'created_at' => 'Добавлен',
        ];
    }

    /**
     * Покупатель (Customer), не админский пользователь
     */
    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'user_id']);
    }

    /**
     * Товар
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * Добавить товар в избранное
     */
    public static function add($productId, $userId = null, $sessionId = null)
    {
        // Проверка - уже в избранном?
        $exists = static::find()
            ->where(['product_id' => $productId])
            ->andWhere($userId ? ['user_id' => $userId] : ['session_id' => $sessionId])
            ->exists();

        if ($exists) {
            return false;
        }

        $favorite = new static();
        $favorite->product_id = $productId;
        $favorite->user_id = $userId;
        $favorite->session_id = $sessionId;

        return $favorite->save();
    }

    /**
     * Удалить товар из избранного
     */
    public static function remove($productId, $userId = null, $sessionId = null)
    {
        return static::deleteAll([
            'product_id' => $productId,
            'user_id' => $userId,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Получить все избранные товары
     */
    public static function getFavorites($userId = null, $sessionId = null)
    {
        $query = static::find()
            ->with('product')
            ->orderBy(['created_at' => SORT_DESC]);

        if ($userId) {
            $query->where(['user_id' => $userId]);
        } elseif ($sessionId) {
            $query->where(['session_id' => $sessionId]);
        }

        return $query->all();
    }

    /**
     * Получить количество избранных товаров
     */
    public static function getCount($userId = null, $sessionId = null)
    {
        $query = static::find();

        if ($userId) {
            $query->where(['user_id' => $userId]);
        } elseif ($sessionId) {
            $query->where(['session_id' => $sessionId]);
        }

        return $query->count();
    }
}
