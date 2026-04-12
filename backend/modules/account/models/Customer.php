<?php

/**
 * Customer — Модель покупателя
 * 
 * НАЗНАЧЕНИЕ:
 * Зарегистрированные покупатели интернет-магазина: профиль,
 * авторизация, история заказов, адреса доставки.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - Авторизация: email, password_hash, auth_key
 * - Профиль: first_name, last_name, middle_name, phone, birth_date, gender
 * - Адрес: default_country, default_city, default_address, default_postal_code
 * - Документы: passport_series, passport_number, passport_issue_date, inn
 * - Статистика: orders_count, total_spent, last_order_at
 * - Настройки: subscribe_news, subscribe_promo
 * - Статус: status (active/inactive/deleted)
 * 
 * СТАТУСЫ (поле is_active):
 * - STATUS_ACTIVE = 1 (активный)
 * - STATUS_INACTIVE = 0 (неактивный/удалённый)
 * 
 * СВЯЗИ:
 * - Order[] (заказы покупателя)
 * - ProductFavorite[] (избранные товары)
 * - CustomerSocialAccount[] (социальные аккаунты)
 * 
 * ИНТЕРФЕЙСЫ:
 * - IdentityInterface (для авторизации через сессию)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - AccountController (личный кабинет)
 * - CustomerController/admin (управление покупателями)
 * - OrderController (привязка к заказу)
 */
namespace app\backend\modules\account\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\checkout\models\Order;

/**
 * Модель покупателя (Customer)
 *
 * @property int $id
 * @property string $email
 * @property string|null $phone
 * @property string $password_hash
 * @property string|null $auth_key
 * @property string|null $password_reset_token
 * @property string|null $verification_token
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $middle_name
 * @property string|null $birth_date
 * @property string|null $gender
 * @property string|null $default_country
 * @property string|null $default_city
 * @property string|null $default_address
 * @property string|null $default_postal_code
 * @property string|null $passport_series
 * @property string|null $passport_number
 * @property string|null $passport_issue_date
 * @property string|null $inn
 * @property int $orders_count
 * @property float $total_spent
 * @property int|null $last_order_at
 * @property int $status
 * @property bool $email_verified
 * @property bool $phone_verified
 * @property bool $subscribe_news
 * @property bool $subscribe_promo
 * @property int|null $last_login_at
 * @property string|null $last_login_ip
 * @property int $created_at
 * @property int $updated_at
 */
class Customer extends ActiveRecord implements IdentityInterface
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_ACTIVE_DB = 10;
    const STATUS_INACTIVE_DB = 9;
    const STATUS_DELETED = 0;

    public $password;
    public $password_confirm;

    public static function tableName()
    {
        return '{{%customer}}';
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
            ['is_active', 'default', 'value' => self::STATUS_ACTIVE],
            ['is_active', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'message' => 'Этот email уже зарегистрирован'],

            ['phone', 'string', 'max' => 50],
            ['phone', 'match', 'pattern' => '/^[\+]?[0-9\s\-\(\)]+$/', 'message' => 'Неверный формат телефона'],

            [['first_name', 'last_name', 'middle_name'], 'string', 'max' => 100],
            [['first_name', 'last_name', 'middle_name'], 'trim'],

            ['birth_date', 'date', 'format' => 'php:Y-m-d'],
            ['gender', 'in', 'range' => ['male', 'female']],

            [['default_country'], 'string', 'max' => 50],
            [['default_city'], 'string', 'max' => 100],
            [['default_address'], 'string'],
            [['default_postal_code'], 'string', 'max' => 20],

            [['passport_series'], 'string', 'max' => 10],
            [['passport_number'], 'string', 'max' => 20],
            ['passport_issue_date', 'date', 'format' => 'php:Y-m-d'],
            [['inn'], 'string', 'max' => 20],

            [['orders_count', 'last_order_at', 'last_login_at'], 'integer'],
            [['total_spent'], 'number'],

            [['email_verified', 'phone_verified', 'subscribe_news', 'subscribe_promo'], 'boolean'],

            ['password', 'required', 'on' => 'register'],
            ['password', 'string', 'min' => 6, 'message' => 'Пароль должен содержать минимум 6 символов'],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают', 'on' => 'register'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['register'] = ['email', 'phone', 'password', 'password_confirm', 'first_name', 'last_name', 'subscribe_news', 'subscribe_promo'];
        $scenarios['profile'] = ['first_name', 'last_name', 'middle_name', 'phone', 'birth_date', 'gender', 
            'default_country', 'default_city', 'default_address', 'default_postal_code',
            'passport_series', 'passport_number', 'passport_issue_date', 'inn',
            'subscribe_news', 'subscribe_promo'];
        $scenarios['change-password'] = ['password', 'password_confirm'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'phone' => 'Телефон',
            'password' => 'Пароль',
            'password_confirm' => 'Подтверждение пароля',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'middle_name' => 'Отчество',
            'birth_date' => 'Дата рождения',
            'gender' => 'Пол',
            'default_country' => 'Страна',
            'default_city' => 'Город',
            'default_address' => 'Адрес доставки',
            'default_postal_code' => 'Индекс',
            'passport_series' => 'Серия паспорта',
            'passport_number' => 'Номер паспорта',
            'passport_issue_date' => 'Дата выдачи паспорта',
            'inn' => 'ИНН',
            'orders_count' => 'Заказов',
            'total_spent' => 'Потрачено',
            'status' => 'Статус',
            'email_verified' => 'Email подтвержден',
            'phone_verified' => 'Телефон подтвержден',
            'subscribe_news' => 'Подписка на новости',
            'subscribe_promo' => 'Подписка на акции',
            'last_login_at' => 'Последний вход',
            'created_at' => 'Дата регистрации',
            'updated_at' => 'Обновлено',
        ];
    }

    // IdentityInterface methods
    public static function findIdentity($id)
    {
        return static::find()
            ->where(['id' => $id])
            ->andWhere(['is_active' => self::STATUS_ACTIVE])
            ->one();
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        // В этой таблице нет auth_key, возвращаем null
        return null;
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        // В этой таблице нет auth_key, возвращаем null
        return null;
    }

    public function validateAuthKey($authKey)
    {
        // В этой таблице нет auth_key, возвращаем false
        return false;
    }

    // Custom methods
    public static function findByEmail($email)
    {
        return static::find()
            ->where(['email' => $email])
            ->andWhere(['is_active' => self::STATUS_ACTIVE])
            ->one();
    }

    public static function findByEmailOrPhone($emailOrPhone)
    {
        return static::find()
            ->where(['is_active' => self::STATUS_ACTIVE])
            ->andWhere(['or', ['email' => $emailOrPhone], ['phone' => $emailOrPhone]])
            ->one();
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function generateVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function getFullName()
    {
        $parts = array_filter([$this->last_name, $this->first_name]);
        return $parts ? implode(' ', $parts) : $this->email;
    }

    public function getShortName()
    {
        if ($this->first_name) {
            return $this->first_name . ($this->last_name ? ' ' . mb_substr($this->last_name, 0, 1) . '.' : '');
        }
        return $this->email;
    }

    public function getStatusLabel()
    {
        $statuses = [
            self::STATUS_ACTIVE_DB => 'Активен',
            self::STATUS_INACTIVE_DB => 'Неактивен',
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_DELETED => 'Удалён',
        ];
        return $statuses[$this->status] ?? ($this->is_active ? 'Активен' : 'Неактивен');
    }

    public function getStatusBadgeClass()
    {
        $classes = [
            self::STATUS_ACTIVE_DB => 'success',
            self::STATUS_INACTIVE_DB => 'warning',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_DELETED => 'danger',
        ];
        return $classes[$this->status] ?? ($this->is_active ? 'success' : 'secondary');
    }

    public function updateLoginInfo()
    {
        // В реальной таблице нет этих полей, просто обновляем updated_at
        $this->save(false, ['updated_at']);
    }

    public function updateOrderStats()
    {
        // В реальной таблице нет этих полей, метод не нужен
        return true;
    }

    /**
     * Getter для orders_count
     */
    public function getOrdersCount()
    {
        return $this->getOrders()->count();
    }

    /**
     * Getter для total_spent
     */
    public function getTotalSpent()
    {
        return $this->getOrders()->sum('total_amount') ?? 0;
    }

    /**
     * Getter для last_order_at
     */
    public function getLastOrderAt()
    {
        $lastOrder = $this->getOrders()->one();
        return $lastOrder ? $lastOrder->created_at : null;
    }

    /**
     * Magic getter для виртуальных вычисляемых свойств.
     * Обрабатывает только явно объявленные виртуальные поля.
     * Прочие обращения передаются в родительский __get без перехвата исключений,
     * чтобы опечатки в именах свойств не скрывались.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'orders_count':
                return $this->getOrdersCount();
            case 'total_spent':
                return $this->getTotalSpent();
            case 'last_order_at':
                return $this->getLastOrderAt();
        }

        return parent::__get($name);
    }

    // Relations
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['customer_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    public function getLastOrders($limit = 5)
    {
        return $this->hasMany(Order::class, ['customer_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit);
    }
}
