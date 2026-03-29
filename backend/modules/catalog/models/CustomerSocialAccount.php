<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property int|null $expires_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Customer $customer
 */
class CustomerSocialAccount extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer_social_account}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['customer_id', 'provider', 'provider_id'], 'required'],
            [['customer_id', 'expires_at', 'created_at', 'updated_at'], 'integer'],
            [['access_token', 'refresh_token'], 'string'],
            [['provider'], 'string', 'max' => 50],
            [['provider_id'], 'string', 'max' => 128],
            [['provider', 'provider_id'], 'unique', 'targetAttribute' => ['provider', 'provider_id']],
            [
                ['customer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Customer::class,
                'targetAttribute' => ['customer_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'provider' => 'Провайдер',
            'provider_id' => 'ID провайдера',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }
}
