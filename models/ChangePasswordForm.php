<?php

namespace app\models;

use Yii;
use yii\base\Model;

class ChangePasswordForm extends Model
{
    public $old_password;
    public $new_password;
    public $new_password_repeat;

    public function rules()
    {
        return [
            [['old_password', 'new_password', 'new_password_repeat'], 'required', 'message' => 'Поле обязательно для заполнения'],
            ['old_password', 'validateOldPassword'],
            ['new_password', 'string', 'min' => 6, 'message' => 'Пароль должен содержать минимум 6 символов'],
            ['new_password_repeat', 'compare', 'compareAttribute' => 'new_password', 'message' => 'Пароли не совпадают'],
        ];
    }

    public function validateOldPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = Yii::$app->user->identity;
            if (!$user || !$user->validatePassword($this->old_password)) {
                $this->addError($attribute, 'Неверный текущий пароль');
            }
        }
    }

    public function attributeLabels()
    {
        return [
            'old_password' => 'Текущий пароль',
            'new_password' => 'Новый пароль',
            'new_password_repeat' => 'Повторите новый пароль',
        ];
    }

    public function changePassword()
    {
        if ($this->validate()) {
            $user = Yii::$app->user->identity;
            $user->setPassword($this->new_password);
            $user->generateAuthKey();
            
            if ($user->save(false)) {
                Yii::info('Пользователь #' . $user->id . ' (' . $user->username . ') сменил пароль', 'user');
                return true;
            }
        }
        return false;
    }
}
