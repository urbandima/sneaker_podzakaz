<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

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
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
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
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
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
