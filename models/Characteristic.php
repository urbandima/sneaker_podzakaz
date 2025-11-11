<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\history\CharacteristicHistory;

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
            [['is_filter', 'is_required', 'is_active', 'sort_order', 'version', 'updated_by'], 'integer'],
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

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (Yii::$app->has('user') && !Yii::$app->user->isGuest) {
            $this->updated_by = Yii::$app->user->id;
        }

        if ($insert) {
            $this->version = 1;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $this->logHistory('create', null, json_encode($this->attributes, JSON_UNESCAPED_UNICODE));
            return;
        }

        foreach ($changedAttributes as $attr => $oldValue) {
            $newValue = $this->getAttribute($attr);
            if ($newValue == $oldValue) {
                continue;
            }

            $this->logHistory($attr, $oldValue, $newValue);
        }

        // Обновляем версию
        static::updateAllCounters(['version' => 1], ['id' => $this->id]);
        $this->version++;
    }

    protected function logHistory(string $field, $oldValue, $newValue): void
    {
        $history = new CharacteristicHistory([
            'characteristic_id' => $this->id,
            'field_name' => $field,
            'old_value' => is_scalar($oldValue) ? (string)$oldValue : json_encode($oldValue, JSON_UNESCAPED_UNICODE),
            'new_value' => is_scalar($newValue) ? (string)$newValue : json_encode($newValue, JSON_UNESCAPED_UNICODE),
            'changed_by' => Yii::$app->has('user') && !Yii::$app->user->isGuest ? Yii::$app->user->id : null,
        ]);
        $history->save(false);
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
            ->with('values')
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }
}
