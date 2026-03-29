<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель остатков товаров на складе
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $size_id
 * @property int $quantity
 * @property int $reserved
 * @property string $warehouse
 * @property int $min_quantity
 * @property string|null $last_restock_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Product $product
 * @property ProductSize $size
 */
class ProductStock extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%product_stock}}';
    }

    public function rules()
    {
        return [
            [['product_id'], 'required'],
            [['product_id', 'size_id', 'quantity', 'reserved', 'min_quantity'], 'integer'],
            [['warehouse'], 'string', 'max' => 100],
            [['last_restock_at', 'created_at', 'updated_at'], 'safe'],
            [['quantity', 'reserved', 'min_quantity'], 'default', 'value' => 0],
            [['warehouse'], 'default', 'value' => 'main'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Товар',
            'size_id' => 'Размер',
            'quantity' => 'Количество',
            'reserved' => 'Зарезервировано',
            'warehouse' => 'Склад',
            'min_quantity' => 'Мин. количество',
            'last_restock_at' => 'Последнее пополнение',
        ];
    }

    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getSize()
    {
        return $this->hasOne(ProductSize::class, ['id' => 'size_id']);
    }

    /**
     * Доступное количество
     */
    public function getAvailable()
    {
        return max(0, $this->quantity - $this->reserved);
    }

    /**
     * Нужно ли пополнение
     */
    public function needsRestock()
    {
        return $this->getAvailable() <= $this->min_quantity;
    }

    /**
     * Резервирование товара
     */
    public function reserve($qty)
    {
        if ($qty > $this->getAvailable()) {
            return false;
        }
        $this->reserved += $qty;
        return $this->save();
    }

    /**
     * Снятие резерва
     */
    public function unreserve($qty)
    {
        $this->reserved = max(0, $this->reserved - $qty);
        return $this->save();
    }

    /**
     * Списание товара
     */
    public function deduct($qty)
    {
        if ($qty > $this->quantity) {
            return false;
        }
        $this->quantity -= $qty;
        $this->reserved = max(0, $this->reserved - $qty);
        return $this->save();
    }

    /**
     * Пополнение склада
     */
    public function restock($qty)
    {
        $this->quantity += $qty;
        $this->last_restock_at = date('Y-m-d H:i:s');
        return $this->save();
    }
}
