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
use app\backend\modules\checkout\models\Order;

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
            
            // Связываем существующие заказы с этим покупателем
            $this->linkExistingOrders($customer);
            
            return $customer;
        }

        return null;
    }

    /**
     * Связывает существующие заказы (по email/phone) с новым покупателем
     */
    protected function linkExistingOrders(Customer $customer)
    {
        $conditions = ['or'];
        if ($customer->email) {
            $conditions[] = ['client_email' => $customer->email];
        }
        if ($customer->phone) {
            $conditions[] = ['client_phone' => $customer->phone];
        }

        if (count($conditions) > 1) {
            Order::updateAll(
                ['customer_id' => $customer->id],
                ['and', ['customer_id' => null], $conditions]
            );
            
            // Обновляем статистику
            $customer->updateOrderStats();
        }
    }
}
