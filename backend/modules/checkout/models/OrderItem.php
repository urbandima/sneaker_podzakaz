<?php

/**
 * OrderItem — Модель позиции заказа
 * 
 * НАЗНАЧЕНИЕ:
 * Позиция товара в заказе: название, количество, цена.
 * Хранит snapshot данных товара на момент заказа.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - order_id: ID заказа
 * - product_name: название товара (snapshot)
 * - quantity: количество
 * - price: цена за единицу
 * - total: общая сумма (price * quantity)
 * 
 * СВЯЗИ:
 * - Order (принадлежит заказу)
 * 
 * ОСОБЕННОСТИ:
 * - Snapshot данных: название товара сохраняется на момент заказа
 * - Автоматический расчёт total
 */
namespace app\backend\modules\checkout\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class OrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return 'order_item';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['order_id', 'product_name', 'price'], 'required'],
            [['order_id', 'quantity', 'product_id'], 'integer'],
            [['price', 'total'], 'number'],
            [['product_name', 'size'], 'string', 'max' => 255],
            [['product_article', 'color'], 'string', 'max' => 100],
            ['quantity', 'default', 'value' => 1],
            [['size', 'color', 'product_article', 'product_id'], 'default', 'value' => null],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Заказ',
            'product_id' => 'Товар (ID)',
            'product_name' => 'Товар',
            'product_article' => 'Артикул',
            'size' => 'Размер',
            'color' => 'Цвет',
            'quantity' => 'Количество',
            'price' => 'Цена',
            'total' => 'Итого',
            'created_at' => 'Добавлен',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Автоматически рассчитываем итого
            $this->total = $this->quantity * $this->price;
            return true;
        }
        return false;
    }

    // Relations
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getProduct()
    {
        return $this->hasOne(\app\backend\modules\catalog\models\Product::class, ['id' => 'product_id']);
    }
}
