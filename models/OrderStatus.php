<?php

namespace app\models;

use yii\db\ActiveRecord;

class OrderStatus extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%order_status}}';
    }

    public function rules()
    {
        return [
            [['key', 'label'], 'required'],
            [['key'], 'string', 'max' => 50],
            [['label'], 'string', 'max' => 100],
            [['sort'], 'integer'],
            [['logist_available'], 'boolean'],
            [['key'], 'unique'],
        ];
    }
}
