<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель ProductCharacteristic (Характеристика товара)
 *
 * @property int $id
 * @property int $product_id
 * @property string $characteristic_key
 * @property string $characteristic_name
 * @property string $characteristic_value
 * @property int $sort_order
 * @property string $created_at
 * 
 * @property Product $product
 */
class ProductCharacteristic extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%product_characteristic}}';
    }

    public function rules()
    {
        return [
            [['product_id', 'characteristic_key', 'characteristic_name', 'characteristic_value'], 'required'],
            [['product_id', 'sort_order'], 'integer'],
            [['characteristic_value'], 'string'],
            [['characteristic_key'], 'string', 'max' => 100],
            [['characteristic_name'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Товар',
            'characteristic_key' => 'Ключ',
            'characteristic_name' => 'Название',
            'characteristic_value' => 'Значение',
            'sort_order' => 'Порядок сортировки',
            'created_at' => 'Создано',
        ];
    }

    /**
     * Товар
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
}
