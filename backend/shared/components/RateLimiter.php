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
        $startKey = $key . '_start';

        $attempts = $cache->get($key);

        if ($attempts === false) {
            // Первая попытка — сохраняем счётчик и метку старта окна
            $cache->set($key, 1, $timeWindow);
            $cache->set($startKey, time(), $timeWindow);
            return;
        }

        // Вычисляем оставшееся время до сброса окна
        $start = $cache->get($startKey);
        $remaining = $start !== false ? max(1, ($start + $timeWindow) - time()) : $timeWindow;

        if ($attempts >= $maxAttempts) {
            throw new TooManyRequestsHttpException(
                "Слишком много попыток. Попробуйте через " . ceil($remaining / 60) . " минут."
            );
        }

        // Увеличиваем счётчик, сохраняя оставшийся TTL окна
        $cache->set($key, $attempts + 1, $remaining);
    }
    
    /**
     * Сброс лимита (после успешного действия)
     */
    public static function reset(string $action, string $identifier): void
    {
        $cache = Yii::$app->cache;
        $key = self::getCacheKey($action, $identifier);
        $cache->delete($key);
        $cache->delete($key . '_start');
    }
    
    /**
     * Получить ключ кэша
     */
    private static function getCacheKey(string $action, string $identifier): string
    {
        return 'rate_limit_' . $action . '_' . md5($identifier);
    }
}
