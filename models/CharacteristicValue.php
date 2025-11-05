<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель CharacteristicValue (Значение характеристики)
 *
 * @property int $id
 * @property int $characteristic_id
 * @property string $value Значение
 * @property string $slug URL slug
 * @property int $sort_order
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property Characteristic $characteristic
 * @property ProductCharacteristicValue[] $productCharacteristicValues
 */
class CharacteristicValue extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%characteristic_value}}';
    }

    public function rules()
    {
        return [
            [['characteristic_id', 'value', 'slug'], 'required'],
            [['characteristic_id', 'sort_order', 'is_active'], 'integer'],
            [['value', 'slug'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
            [['characteristic_id'], 'exist', 'targetClass' => Characteristic::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'characteristic_id' => 'Характеристика',
            'value' => 'Значение',
            'slug' => 'URL Slug',
            'sort_order' => 'Порядок',
            'is_active' => 'Активно',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Характеристика
     */
    public function getCharacteristic()
    {
        return $this->hasOne(Characteristic::class, ['id' => 'characteristic_id']);
    }

    /**
     * Связи с товарами
     */
    public function getProductCharacteristicValues()
    {
        return $this->hasMany(ProductCharacteristicValue::class, ['characteristic_value_id' => 'id']);
    }
}
