<?php

/**
 * CustomerRegisterForm — Форма регистрации покупателя
 *
 * НАЗНАЧЕНИЕ:
 * Форма регистрации нового покупателя в интернет-магазине.
 * Валидация данных, проверка уникальности email.
 *
 * ПОЛЯ:
 * - email: email (уникальный)
 * - phone: телефон (опционально)
 * - password: пароль
 * - password_confirm: подтверждение пароля
 * - first_name: имя
 * - last_name: фамилия
 * - agree_terms: согласие с условиями
 * - subscribe_news: подписка на новости
 *
 * МЕТОДЫ:
 * - register(): создание покупателя
 * - sendEmail(): отправка приветственного письма
 *
 * ИСПОЛЬЗОВАНИЕ:
 * - AccountController (actionRegister)
 *
 * ВАЛИДАЦИЯ:
 * - Уникальность email
 * - Минимум 6 символов пароля
 * - Совпадение паролей
 * - Обязательное согласие с условиями
 */

namespace app\backend\modules\account\models;

use Yii;
use yii\base\Model;
use app\backend\modules\account\models\Customer;

/**
 * Форма регистрации покупателя
 */
class CustomerRegisterForm extends Model
{
    public $email;
    public $phone;
    public $password;
    public $password_confirm;
    public $first_name;
    public $last_name;
    public $agree_terms = false;
    public $subscribe_news = true;

    public function rules()
    {
        return [
            [['email', 'password', 'password_confirm'], 'required'],
            ['email', 'trim'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => Customer::class, 'message' => 'Этот email уже зарегистрирован'],

            ['phone', 'string', 'max' => 50],
            ['phone', 'match', 'pattern' => '/^[\+]?[0-9\s\-\(\)]+$/', 'message' => 'Неверный формат телефона'],

            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['first_name', 'last_name'], 'trim'],

            ['password', 'string', 'min' => 6, 'message' => 'Пароль должен содержать минимум 6 символов'],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают'],

            ['agree_terms', 'required', 'requiredValue' => 1, 'message' => 'Необходимо принять условия'],
            ['subscribe_news', 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'email' => 'Email',
            'phone' => 'Телефон',
            'password' => 'Пароль',
            'password_confirm' => 'Подтверждение пароля',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'agree_terms' => 'Я принимаю условия оферты',
            'subscribe_news' => 'Подписаться на новости и акции',
        ];
    }

    public function register()
    {
        if (!$this->validate()) {
            return null;
        }

        $customer = new Customer();
        $customer->email = $this->email;
        $customer->phone = $this->phone ?: null;
        $customer->first_name = $this->first_name ?: null;
        $customer->last_name = $this->last_name ?: null;
        $customer->setPassword($this->password);
        $customer->generateAuthKey();
        $customer->generateVerificationToken();
        $customer->subscribe_news = $this->subscribe_news;
        $customer->subscribe_promo = $this->subscribe_news;
        $customer->status = Customer::STATUS_ACTIVE;

        if ($customer->save()) {
            // Автоматически авторизуем после регистрации
            Yii::$app->session->set('customer_id', $customer->id);
            Yii::$app->session->set('customer_email', $customer->email);
            Yii::$app->session->set('customer_phone', $customer->phone);
            Yii::$app->session->set('customer_name', $customer->getFullName());

            // ВНИМАНИЕ (CMP-465): здесь раньше стоял linkExistingOrders() — автоматическая
            // привязка гостевых заказов к новому аккаунту по совпадению email/телефона.
            // Регистрация не подтверждает владение email/телефоном, поэтому это был захват
            // чужих заказов: атакующий регистрировался на email/телефон жертвы и получал
            // customer_id её гостевых заказов в постоянное владение. Метод удалён целиком.
            // Покупатель всё ещё может получить доступ к своим гостевым заказам через
            // защищённый механизм AccountController::actionFindOrders() (ссылка отправляется
            // на email, указанный в самом заказе, а не вводимый атакующим).

            return $customer;
        }

        return null;
    }
}
