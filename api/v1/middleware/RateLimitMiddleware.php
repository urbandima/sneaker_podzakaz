<?php

namespace app\api\v1\middleware;

use yii\base\ActionFilter;
use yii\web\TooManyRequestsHttpException;

/**
 * Middleware ограничения запросов (Rate Limiting)
 */
class RateLimitMiddleware extends ActionFilter
{
    /**
     * @var int Максимальное количество запросов
     */
    public int $maxRequests = 100;

    /**
     * @var int Окно времени в секундах
     */
    public int $window = 60;

    public string $keyPrefix = 'rate_limit';

    public function beforeAction($action)
    {
        $key = $this->getRateLimitKey();

        // Получаем текущий счётчик из Redis
        $redis = \Yii::$app->redis;
        $current = (int) $redis->get($key);

        if ($current >= $this->maxRequests) {
            $ttl = $redis->ttl($key);
            throw new TooManyRequestsHttpException(
                "Превышен лимит запросов. Попробуйте через {$ttl} секунд."
            );
        }

        // Увеличиваем счётчик
        if ($current === 0) {
            $redis->setex($key, $this->window, 1);
        } else {
            $redis->incr($key);
        }

        // Добавляем заголовки
        $response = \Yii::$app->response;
        $response->headers->set('X-RateLimit-Limit', $this->maxRequests);
        $response->headers->set('X-RateLimit-Remaining', max(0, $this->maxRequests - $current - 1));
        $response->headers->set('X-RateLimit-Reset', time() + $this->window);

        return true;
    }

    /**
     * Ключ для хранения счётчика — включает action для изолирования лимитов по эндпоинтам
     */
    private function getRateLimitKey(): string
    {
        $ip = \Yii::$app->request->userIP;
        $userId = \Yii::$app->user->id ?? 'guest';
        $actionId = \Yii::$app->controller->action->id;
        return "{$this->keyPrefix}:{$ip}:{$userId}:{$actionId}";
    }
}
