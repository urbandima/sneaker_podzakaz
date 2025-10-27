<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class OrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_item}}';
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
            [['order_id', 'quantity'], 'integer'],
            [['price', 'total'], 'number'],
            [['product_name'], 'string', 'max' => 255],
            ['quantity', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Заказ',
            'product_name' => 'Товар',
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
}
