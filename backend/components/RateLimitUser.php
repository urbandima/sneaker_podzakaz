<?php

/**
 * RateLimitUser - Компонент для rate limiting пользователей
 * 
 * НАЗНАЧЕНИЕ:
 * Ограничение частоты запросов для защиты от брутфорса и DoS атак
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - В контроллерах через behaviors()
 * - Для форм входа, регистрации, API
 */

namespace app\backend\components;

use yii\filters\RateLimiter;
use yii\web\User as BaseUser;

class RateLimitUser extends BaseUser
{
    /**
     * {@inheritdoc}
     */
    public function getRateLimit($request, $action)
    {
        // Лимиты для разных типов пользователей
        if ($this->isGuest) {
            // Для гостей: 5 попыток входа в минуту
            return [5, 60];
        }
        
        // Для авторизованных: более мягкие лимиты
        return [20, 60];
    }

    /**
     * {@inheritdoc}
     */
    public function loadAllowance($request, $action)
    {
        // Используем сессию или IP для хранения счетчиков
        $key = $this->isGuest 
            ? 'rate_limit_' . $request->getUserIP() 
            : 'rate_limit_user_' . $this->id;
            
        $cache = \Yii::$app->cache;
        $data = $cache->get($key);
        
        if ($data === false) {
            return [$this->getRateLimit($request, $action)[1], time()];
        }
        
        return [$data['allowance'], $data['timestamp']];
    }

    /**
     * {@inheritdoc}
     */
    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $key = $this->isGuest 
            ? 'rate_limit_' . $request->getUserIP() 
            : 'rate_limit_user_' . $this->id;
            
        $cache = \Yii::$app->cache;
        $cache->set($key, [
            'allowance' => $allowance,
            'timestamp' => $timestamp
        ], 300); // Храним 5 минут
    }
}
