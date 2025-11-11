<?php

namespace app\models\history;

use yii\db\ActiveRecord;
use app\models\Characteristic;
use app\models\User;

/**
 * История изменений характеристик
 *
 * @property int $id
 * @property int $characteristic_id
 * @property string $field_name
 * @property string|null $old_value
 * @property string|null $new_value
 * @property int|null $changed_by
 * @property string $created_at
 *
 * @property Characteristic $characteristic
 * @property User|null $user
 */
class CharacteristicHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%characteristic_history}}';
    }

    public function rules()
    {
        return [
            [['characteristic_id', 'field_name'], 'required'],
            [['characteristic_id', 'changed_by'], 'integer'],
            [['old_value', 'new_value'], 'string'],
            [['created_at'], 'safe'],
            [['field_name'], 'string', 'max' => 100],
        ];
    }

    public function getCharacteristic()
    {
        return $this->hasOne(Characteristic::class, ['id' => 'characteristic_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'changed_by']);
    }
}
