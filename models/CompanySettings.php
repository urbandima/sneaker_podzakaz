<?php

namespace app\models;

use yii\db\ActiveRecord;

class CompanySettings extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%company_settings}}';
    }

    public function rules()
    {
        return [
            [['name', 'unp', 'address', 'bank', 'bic', 'account', 'phone', 'email'], 'required'],
            [['name', 'address', 'bank', 'email', 'offer_url'], 'string', 'max' => 255],
            [['unp', 'bic', 'phone'], 'string', 'max' => 50],
            [['account'], 'string', 'max' => 64],
            ['email', 'email'],
            [['updated_at'], 'integer'],
        ];
    }
}
