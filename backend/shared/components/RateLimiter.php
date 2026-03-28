<?php

namespace app\backend\shared\components;

use Yii;
use yii\base\Component;
use yii\web\TooManyRequestsHttpException;

/**
 * Rate Limiter для защиты от брутфорса
 * 
 * Использование:
 * RateLimiter::check('login', $ip, 5, 900); // 5 попыток за 15 минут
 */
class RateLimiter extends Component
{
    /**
     * Проверка лимита запросов
     * 
     * @param string $action Действие (login, register, etc)
     * @param string $identifier Идентификатор (IP, user_id, etc)
     * @param int $maxAttempts Максимум попыток
     * @param int $timeWindow Временное окно в секундах
     * @throws TooManyRequestsHttpException
     */
    public static function check(string $action, string $identifier, int $maxAttempts = 5, int $timeWindow = 900): void
    {
        $cache = Yii::$app->cache;
        $key = self::getCacheKey($action, $identifier);
        
        $attempts = $cache->get($key);
        
        if ($attempts === false) {
            // Первая попытка
            $cache->set($key, 1, $timeWindow);
            return;
        }
        
        if ($attempts >= $maxAttempts) {
            $ttl = $cache->get($key . '_ttl') ?: $timeWindow;
            throw new TooManyRequestsHttpException(
                "Слишком много попыток. Попробуйте через " . ceil($ttl / 60) . " минут."
            );
        }
        
        // Увеличиваем счётчик
        $cache->set($key, $attempts + 1, $timeWindow);
        $cache->set($key . '_ttl', $timeWindow, $timeWindow);
    }
    
    /**
     * Сброс лимита (после успешного действия)
     */
    public static function reset(string $action, string $identifier): void
    {
        $cache = Yii::$app->cache;
        $key = self::getCacheKey($action, $identifier);
        $cache->delete($key);
        $cache->delete($key . '_ttl');
    }
    
    /**
     * Получить ключ кэша
     */
    private static function getCacheKey(string $action, string $identifier): string
    {
        return 'rate_limit_' . $action . '_' . md5($identifier);
    }
}
