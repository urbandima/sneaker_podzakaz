<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель Characteristic (Тип характеристики)
 *
 * @property int $id
 * @property string $key Ключ характеристики
 * @property string $name Название
 * @property string $type Тип (select, multiselect, text, number, boolean)
 * @property int $is_filter Использовать в фильтрах
 * @property int $is_required Обязательная
 * @property int $sort_order Порядок сортировки
 * @property int $is_active Активна
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property CharacteristicValue[] $values
 * @property ProductCharacteristicValue[] $productCharacteristicValues
 */
class Characteristic extends ActiveRecord
{
    const TYPE_SELECT = 'select';
    const TYPE_MULTISELECT = 'multiselect';
    const TYPE_TEXT = 'text';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';

    public static function tableName()
    {
        return '{{%characteristic}}';
    }

    public function rules()
    {
        return [
            [['key', 'name'], 'required'],
            [['key'], 'unique'],
            [['is_filter', 'is_required', 'is_active', 'sort_order'], 'integer'],
            [['type'], 'in', 'range' => [self::TYPE_SELECT, self::TYPE_MULTISELECT, self::TYPE_TEXT, self::TYPE_NUMBER, self::TYPE_BOOLEAN]],
            [['key'], 'string', 'max' => 100],
            [['name'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => 'Ключ',
            'name' => 'Название',
            'type' => 'Тип',
            'is_filter' => 'Фильтр',
            'is_required' => 'Обязательная',
            'sort_order' => 'Порядок',
            'is_active' => 'Активна',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Значения характеристики
     */
    public function getValues()
    {
        return $this->hasMany(CharacteristicValue::class, ['characteristic_id' => 'id'])
            ->where(['is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    /**
     * Связи с товарами
     */
    public function getProductCharacteristicValues()
    {
        return $this->hasMany(ProductCharacteristicValue::class, ['characteristic_id' => 'id']);
    }

    /**
     * Получить список типов
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_SELECT => 'Выпадающий список',
            self::TYPE_MULTISELECT => 'Множественный выбор',
            self::TYPE_TEXT => 'Текст',
            self::TYPE_NUMBER => 'Число',
            self::TYPE_BOOLEAN => 'Да/Нет',
        ];
    }

    /**
     * Получить активные характеристики для фильтров
     */
    public static function getFilterCharacteristics()
    {
        return self::find()
            ->where(['is_active' => 1, 'is_filter' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }
}
