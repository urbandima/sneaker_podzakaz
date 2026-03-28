<?php

/**
 * CustomerLoginForm — Форма авторизации покупателя
 * 
 * НАЗНАЧЕНИЕ:
 * Форма входа для покупателей интернет-магазина.
 * Авторизация по email и паролю.
 * 
 * ПОЛЯ:
 * - email: email покупателя
 * - password: пароль
 * - rememberMe: запомнить меня
 * 
 * МЕТОДЫ:
 * - validatePassword(): валидация пароля
 * - login(): выполнение авторизации
 * - getCustomer(): получение покупателя
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - AccountController (actionLogin)
 * 
 * БЕЗОПАСНОСТЬ:
 * - Проверка статуса покупателя
 * - Поддержка rememberMe
 */
namespace app\backend\modules\account\models;

use Yii;
use yii\base\Model;

/**
 * Форма входа для покупателей
 */
class CustomerLoginForm extends Model
{
    public $email;
    public $password;
    public $rememberMe = true;

    private $_customer = false;

    public function rules()
    {
        return [
            [['email', 'password'], 'required'],
            ['email', 'email'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'email' => 'Email',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня',
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $customer = $this->getCustomer();

            if (!$customer || !$customer->validatePassword($this->password)) {
                $this->addError($attribute, 'Неверный email или пароль');
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            $customer = $this->getCustomer();
            
            // Сохраняем данные в сессию для совместимости с существующей системой
            Yii::$app->session->set('customer_id', $customer->id);
            Yii::$app->session->set('customer_email', $customer->email);
            Yii::$app->session->set('customer_phone', $customer->phone);
            Yii::$app->session->set('customer_name', $customer->getFullName());
            
            // Обновляем информацию о входе
            $customer->updateLoginInfo();
            
            // Устанавливаем cookie для rememberMe
            if ($this->rememberMe) {
                $duration = 3600 * 24 * 30; // 30 дней
                // HMAC через Yii security вместо MD5: защита от подделки токена
                $tokenData = $customer->id . ':' . $customer->password_hash;
                $secureToken = Yii::$app->security->hashData($tokenData, Yii::$app->params['cookieValidationKey'] ?? Yii::$app->request->cookieValidationKey);
                Yii::$app->response->cookies->add(new \yii\web\Cookie([
                    'name' => 'customer_token',
                    'value' => $secureToken,
                    'expire' => time() + $duration,
                    'httpOnly' => true,
                    'secure' => !YII_ENV_DEV,
                    'sameSite' => 'Lax',
                ]));
            }
            
            return true;
        }
        return false;
    }

    public function getCustomer()
    {
        if ($this->_customer === false) {
            $this->_customer = Customer::findByEmail($this->email);
        }

        return $this->_customer;
    }
}
