<?php
namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;

class SupplierReturnItem extends ActiveRecord
{
    public static function tableName() { return 'supplier_return_item'; }

    public function rules()
    {
        return [
            [['supplier_return_id', 'product_name'], 'required'],
            [['supplier_return_id', 'purchase_order_item_id', 'quantity'], 'integer'],
            [['price_cny', 'price_byn'], 'number'],
            [['product_name'], 'string', 'max' => 255],
            [['size'], 'string', 'max' => 20],
            [['reason'], 'string'],
        ];
    }

    public function getSupplierReturn()
    {
        return $this->hasOne(SupplierReturn::class, ['id' => 'supplier_return_id']);
    }

    public function getPurchaseOrderItem()
    {
        return $this->hasOne(PurchaseOrderItem::class, ['id' => 'purchase_order_item_id']);
    }

    public function getTotalByn(): float
    {
        return (float)$this->price_byn * (int)$this->quantity;
    }
}
