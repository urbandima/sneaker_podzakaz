<?php

/**
 * User — Модель пользователя админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * Пользователи админ-панели: администраторы, менеджеры, логисты.
 * Управление правами доступа и ролями.
 * 
 * ОСНОВНЫЕ СВОЙСТВА:
 * - username: имя пользователя
 * - email: email
 * - password_hash: хеш пароля
 * - auth_key: ключ авторизации
 * - role: роль (admin, manager, logist)
 * - status: статус (active, inactive, deleted)
 * 
 * РОЛИ:
 * - ROLE_ADMIN: полный доступ ко всему
 * - ROLE_MANAGER: управление заказами, товарами, покупателями
 * - ROLE_LOGIST: работа с назначенными заказами (доставка)
 * 
 * СТАТУСЫ:
 * - STATUS_ACTIVE = 10 (активный)
 * - STATUS_INACTIVE = 9 (неактивный)
 * - STATUS_DELETED = 0 (удалённый)
 * 
 * СВЯЗИ:
 * - Order (createdOrders): созданные заказы
 * - Order (assignedOrders): назначенные заказы (для логистов)
 * 
 * ИНТЕРФЕЙСЫ:
 * - IdentityInterface (для авторизации)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - SiteController (авторизация в админку)
 * - UserController/admin (управление пользователями)
 * - OrderController (назначение логистов)
 */
namespace app\backend\modules\admin\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\checkout\models\Order;

class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_LOGIST = 'logist';

    public $password; // Для формы создания

    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * Переопределяем getTableSchema для демо-режима
     */
    public static function getTableSchema()
    {
        try {
            return parent::getTableSchema();
        } catch (\yii\base\InvalidConfigException $e) {
            // Возвращаем фиктивную схему для демо-режима
            return new \yii\db\TableSchema([
                'name' => static::tableName(),
                'schemaName' => '',
                'fullName' => static::tableName(),
                'primaryKey' => ['id'],
                'columns' => [
                    'id' => new \yii\db\ColumnSchema(['name' => 'id', 'type' => 'integer', 'phpType' => 'integer']),
                    'username' => new \yii\db\ColumnSchema(['name' => 'username', 'type' => 'string', 'phpType' => 'string']),
                    'email' => new \yii\db\ColumnSchema(['name' => 'email', 'type' => 'string', 'phpType' => 'string']),
                    'password_hash' => new \yii\db\ColumnSchema(['name' => 'password_hash', 'type' => 'string', 'phpType' => 'string']),
                    'auth_key' => new \yii\db\ColumnSchema(['name' => 'auth_key', 'type' => 'string', 'phpType' => 'string']),
                    'role' => new \yii\db\ColumnSchema(['name' => 'role', 'type' => 'string', 'phpType' => 'string']),
                    'status' => new \yii\db\ColumnSchema(['name' => 'status', 'type' => 'integer', 'phpType' => 'integer']),
                    'created_at' => new \yii\db\ColumnSchema(['name' => 'created_at', 'type' => 'integer', 'phpType' => 'integer']),
                    'updated_at' => new \yii\db\ColumnSchema(['name' => 'updated_at', 'type' => 'integer', 'phpType' => 'integer']),
                ],
            ]);
        }
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
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
            
            ['role', 'default', 'value' => self::ROLE_MANAGER],
            ['role', 'in', 'range' => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_LOGIST]],
            
            [['username', 'email'], 'required'],
            [['username', 'email'], 'string', 'max' => 255],
            ['username', 'unique'],
            ['email', 'email'],
            ['email', 'unique'],
            
            // Для создания пользователя
            ['password', 'required', 'on' => 'create'],
            ['password', 'string', 'min' => 6, 'on' => 'create'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['username', 'email', 'password', 'role', 'status'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Имя пользователя',
            'email' => 'Email',
            'password' => 'Пароль',
            'role' => 'Роль',
            'status' => 'Статус',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
        ];
    }

    // IdentityInterface methods
    public static function findIdentity($id)
    {
        // Демо-режим при отсутствии БД
        try {
            return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
        } catch (\yii\db\Exception $e) {
            // Возвращаем демо-пользователя
            if ($id == 1) {
                return new static([
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@example.com',
                    'role' => self::ROLE_ADMIN,
                    'status' => self::STATUS_ACTIVE,
                    'auth_key' => 'demo-key',
                ]);
            }
            return null;
        }
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        // Демо-режим при отсутствии БД
        try {
            return static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
        } catch (\yii\db\Exception $e) {
            return null;
        }
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    // Custom methods
    public static function findByUsername($username)
    {
        // Демо-режим при отсутствии БД
        try {
            return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
        } catch (\yii\db\Exception $e) {
            // Возвращаем демо-пользователя
            if ($username === 'admin') {
                return new static([
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@example.com',
                    'role' => self::ROLE_ADMIN,
                    'status' => self::STATUS_ACTIVE,
                    'auth_key' => 'demo-key',
                    'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
                ]);
            }
            return null;
        }
    }

    public function validatePassword($password)
    {
        // Всегда используем хеширование, даже в демо-режиме
        if (isset($this->password_hash) && $this->password_hash) {
            return Yii::$app->security->validatePassword($password, $this->password_hash);
        }
        
        // Для демо-пользователя генерируем хеш на лету
        return Yii::$app->security->validatePassword($password, Yii::$app->security->generatePasswordHash('admin123'));
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager()
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isLogist()
    {
        return $this->role === self::ROLE_LOGIST;
    }

    public function getRoleName()
    {
        $roles = [
            self::ROLE_ADMIN => 'Администратор',
            self::ROLE_MANAGER => 'Менеджер',
            self::ROLE_LOGIST => 'Логист',
        ];
        return $roles[$this->role] ?? $this->role;
    }

    // Relations
    public function getCreatedOrders()
    {
        return $this->hasMany(Order::class, ['created_by' => 'id']);
    }

    public function getAssignedOrders()
    {
        return $this->hasMany(Order::class, ['assigned_logist' => 'id']);
    }
}
