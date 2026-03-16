<?php

namespace app\backend\security\middleware;

use yii\base\ActionFilter;
use yii\web\TooManyRequestsHttpException;

/**
 * Middleware ограничения запросов (Rate Limiting)
 */
class RateLimitMiddleware extends ActionFilter
{
    public int $maxRequests = 100;
    public int $window = 60;
    public string $keyPrefix = 'rate_limit';

    public function beforeAction($action)
    {
        $key = $this->getKey();
        $redis = \Yii::$app->redis;

        $current = (int) $redis->get($key);

        if ($current >= $this->maxRequests) {
            $ttl = $redis->ttl($key);
            throw new TooManyRequestsHttpException(
                "Превышен лимит запросов. Попробуйте через {$ttl} секунд."
            );
        }

        if ($current === 0) {
            $redis->setex($key, $this->window, 1);
        } else {
            $redis->incr($key);
        }

        $this->setHeaders($current);

        return true;
    }

    private function getKey(): string
    {
        $ip = \Yii::$app->request->userIP;
        $userId = \Yii::$app->user->id ?? 'guest';
        $action = \Yii::$app->controller->action->id;
        
        return "{$this->keyPrefix}:{$ip}:{$userId}:{$action}";
    }

    private function setHeaders(int $current): void
    {
        $response = \Yii::$app->response;
        $response->headers->set('X-RateLimit-Limit', $this->maxRequests);
        $response->headers->set('X-RateLimit-Remaining', max(0, $this->maxRequests - $current - 1));
        $response->headers->set('X-RateLimit-Reset', time() + $this->window);
    }
}
