<?php

namespace app\backend\security\validators;

use yii\base\Model;

/**
 * Валидатор входных данных
 */
class InputValidator
{
    /**
     * Валидация данных модели
     */
    public static function validate(Model $model, array $rules = []): bool
    {
        if (!empty($rules)) {
            $model->rules = $rules;
        }

        return $model->validate();
    }

    /**
     * Валидация email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Валидация телефона (Беларусь)
     */
    public static function validatePhone(string $phone): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^(375|80)(29|33|44|25|17)\d{7}$/', $phone);
    }

    /**
     * Валидация пароля
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Минимум 8 символов';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Минимум 1 заглавная буква';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Минимум 1 строчная буква';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Минимум 1 цифра';
        }

        return $errors;
    }

    /**
     * Валидация SKU
     */
    public static function validateSku(string $sku): bool
    {
        return preg_match('/^[A-Z0-9\-]+$/', $sku);
    }

    /**
     * Валидация цены
     */
    public static function validatePrice(float $price): bool
    {
        return $price >= 0 && $price <= 999999.99;
    }
}
