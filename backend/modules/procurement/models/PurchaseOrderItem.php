<?php
namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class PurchaseOrderItem extends ActiveRecord
{
    public static function tableName() { return 'purchase_order_item'; }

    public function rules()
    {
        return [
            [['purchase_order_id'], 'required'],
            [['purchase_order_id', 'product_id', 'quantity', 'received_quantity'], 'integer'],
            [['price_cny', 'price_byn'], 'number'],
            [['product_name'], 'string', 'max' => 255],
            [['size'], 'string', 'max' => 20],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'purchase_order_id' => 'Закупка',
            'product_id'        => 'Товар',
            'product_name'      => 'Название',
            'size'              => 'Размер',
            'quantity'          => 'Кол-во',
            'price_cny'         => 'Цена CNY',
            'price_byn'         => 'Цена BYN',
            'received_quantity' => 'Получено',
        ];
    }

    public function getPurchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, ['id' => 'purchase_order_id']);
    }

    public function getTotalCny(): float
    {
        return (float)$this->price_cny * (int)$this->quantity;
    }

    public function getTotalByn(): float
    {
        return (float)$this->price_byn * (int)$this->quantity;
    }
}
