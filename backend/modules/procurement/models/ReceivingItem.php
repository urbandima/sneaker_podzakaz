<?php

namespace app\backend\modules\procurement\models;

use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;
use yii\db\ActiveRecord;

class ReceivingItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%receiving_item}}';
    }

    public function rules(): array
    {
        return [
            [['receiving_id', 'product_id'], 'required'],
            [['receiving_id', 'product_id', 'size_id', 'qty_expected', 'qty_arrived', 'qty_defected'], 'integer'],
            [['unit_cost_source', 'exchange_rate', 'unit_cost_byn', 'allocated_expenses_byn', 'final_cost_byn'], 'number'],
            [['source_currency'], 'string', 'max' => 3],
            [['notes'], 'string'],
            [['qty_arrived', 'qty_defected'], 'integer', 'min' => 0],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                     => 'ID',
            'receiving_id'           => 'Приёмка',
            'product_id'             => 'Товар',
            'size_id'                => 'Размер',
            'qty_expected'           => 'Ожидалось',
            'qty_arrived'            => 'Прибыло',
            'qty_defected'           => 'Дефект',
            'unit_cost_source'       => 'Цена (источник)',
            'source_currency'        => 'Валюта',
            'exchange_rate'          => 'Курс',
            'unit_cost_byn'          => 'Цена (BYN)',
            'allocated_expenses_byn' => 'Расходы',
            'final_cost_byn'         => 'Итого',
            'notes'                  => 'Примечания',
        ];
    }

    public function getReceiving()
    {
        return $this->hasOne(Receiving::class, ['id' => 'receiving_id']);
    }

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getSize()
    {
        return $this->hasOne(ProductSize::class, ['id' => 'size_id']);
    }

    public function getActualQty(): int
    {
        return max(0, $this->qty_arrived - $this->qty_defected);
    }
}
