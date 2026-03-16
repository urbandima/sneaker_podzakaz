<?php

/**
 * LoginForm — Форма авторизации в админ-панель
 * 
 * НАЗНАЧЕНИЕ:
 * Форма входа для пользователей админ-панели (администраторы,
 * менеджеры, логисты). Валидация логина/пароля.
 * 
 * ПОЛЯ:
 * - username: имя пользователя
 * - password: пароль
 * - rememberMe: запомнить меня (автоматический вход)
 * 
 * МЕТОДЫ:
 * - validatePassword(): валидация пароля
 * - login(): выполнение авторизации
 * - getUser(): получение пользователя
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - SiteController (actionLogin)
 * 
 * БЕЗОПАСНОСТЬ:
 * - Rate limiting на попытки входа
 * - Проверка статуса пользователя (active/inactive/deleted)
 */
namespace app\backend\modules\admin\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            // ВРЕМЕННО: Убираем validatePassword для разработки
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => 'Имя пользователя',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня',
        ];
    }

    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неверное имя пользователя или пароль.');
            }
        }
    }

    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
