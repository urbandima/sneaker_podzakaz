<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Inflector;
use app\models\history\CharacteristicHistory;
use app\models\Characteristic;

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
            [['characteristic_id', 'value'], 'required'],
            [['characteristic_id', 'sort_order', 'is_active'], 'integer'],
            [['slug'], 'match', 'pattern' => '/^[a-z0-9\-_]+$/i', 'message' => 'Slug может содержать только латиницу, цифры, дефис и подчёркивание'],
            [['value', 'slug'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
            [['characteristic_id'], 'exist', 'targetClass' => Characteristic::class, 'targetAttribute' => 'id'],
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if (!$this->slug && $this->value) {
            $this->slug = Inflector::slug($this->value, '-', true);
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $this->logHistory('value.create:' . $this->id, null, $this->value);
        } else {
            foreach ($changedAttributes as $attr => $oldValue) {
                $newValue = $this->$attr;
                if ($newValue == $oldValue) {
                    continue;
                }
                $this->logHistory('value.' . $attr . ':' . $this->id, $oldValue, $newValue);
            }
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->logHistory('value.delete:' . $this->id, $this->value, null);
    }

    protected function logHistory(string $field, $oldValue, $newValue): void
    {
        $history = new CharacteristicHistory([
            'characteristic_id' => $this->characteristic_id,
            'field_name' => $field,
            'old_value' => is_scalar($oldValue) ? (string)$oldValue : json_encode($oldValue, JSON_UNESCAPED_UNICODE),
            'new_value' => is_scalar($newValue) ? (string)$newValue : json_encode($newValue, JSON_UNESCAPED_UNICODE),
            'changed_by' => Yii::$app->has('user') && !Yii::$app->user->isGuest ? Yii::$app->user->id : null,
        ]);
        $history->save(false);

        Characteristic::updateAllCounters(['version' => 1], ['id' => $this->characteristic_id]);
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
