<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель ProductCharacteristicValue (Связь товара с характеристикой)
 *
 * @property int $id
 * @property int $product_id
 * @property int $characteristic_id
 * @property int|null $characteristic_value_id
 * @property string|null $value_text
 * @property float|null $value_number
 * @property int|null $value_boolean
 * @property string $created_at
 * 
 * @property Product $product
 * @property Characteristic $characteristic
 * @property CharacteristicValue $characteristicValue
 */
class ProductCharacteristicValue extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%product_characteristic_value}}';
    }

    public function rules()
    {
        return [
            [['product_id', 'characteristic_id'], 'required'],
            [['product_id', 'characteristic_id', 'characteristic_value_id', 'value_boolean'], 'integer'],
            [['value_text'], 'string'],
            [['value_number'], 'number'],
            [['created_at'], 'safe'],
            [['product_id'], 'exist', 'targetClass' => Product::class, 'targetAttribute' => 'id'],
            [['characteristic_id'], 'exist', 'targetClass' => Characteristic::class, 'targetAttribute' => 'id'],
            [['characteristic_value_id'], 'exist', 'targetClass' => CharacteristicValue::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Товар',
            'characteristic_id' => 'Характеристика',
            'characteristic_value_id' => 'Значение',
            'value_text' => 'Текстовое значение',
            'value_number' => 'Числовое значение',
            'value_boolean' => 'Булево значение',
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

    /**
     * Характеристика
     */
    public function getCharacteristic()
    {
        return $this->hasOne(Characteristic::class, ['id' => 'characteristic_id']);
    }

    /**
     * Значение характеристики
     */
    public function getCharacteristicValue()
    {
        return $this->hasOne(CharacteristicValue::class, ['id' => 'characteristic_value_id']);
    }

    /**
     * Получить отображаемое значение
     */
    public function getDisplayValue()
    {
        if ($this->characteristicValue) {
            return $this->characteristicValue->value;
        }
        
        if ($this->value_text) {
            return $this->value_text;
        }
        
        if ($this->value_number !== null) {
            return $this->value_number;
        }
        
        if ($this->value_boolean !== null) {
            return $this->value_boolean ? 'Да' : 'Нет';
        }
        
        return '-';
    }
}
