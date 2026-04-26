<?php

namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;

class BuyoutOrderLink extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%buyout_order_link}}';
    }

    public function rules(): array
    {
        return [
            [['buyout_id', 'order_id'], 'required'],
            [['buyout_id', 'order_id', 'order_item_id', 'qty'], 'integer'],
            [['qty'], 'integer', 'min' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'buyout_id'     => 'Выкуп',
            'order_id'      => 'Заказ',
            'order_item_id' => 'Позиция заказа',
            'qty'           => 'Кол-во',
        ];
    }

    public function getBuyout()
    {
        return $this->hasOne(Buyout::class, ['id' => 'buyout_id']);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getOrderItem()
    {
        return $this->hasOne(OrderItem::class, ['id' => 'order_item_id']);
    }
}
