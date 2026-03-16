<?php

/**
 * CustomerSocialAccount — Модель социального аккаунта покупателя
 * 
 * НАЗНАЧЕНИЕ:
 * Привязка социальных сетей к аккаунту покупателя:
 * авторизация через Google, VK, Facebook и т.д.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - customer_id: ID покупателя
 * - provider: провайдер (google, vk, facebook)
 * - provider_id: ID пользователя у провайдера
 * - access_token: токен доступа
 * - refresh_token: токен обновления
 * - expires_at: время истечения токена
 * 
 * СВЯЗИ:
 * - Customer (покупатель)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - AccountController (авторизация через соцсети)
 * - OAuth callback handlers
 * 
 * ОСОБЕННОСТИ:
 * - Один покупатель может иметь несколько соцсетей
 * - Автоматическое обновление токенов
 */
namespace app\backend\modules\account\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\backend\modules\account\models\Customer;

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
