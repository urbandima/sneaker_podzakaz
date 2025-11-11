<?php

namespace app\components;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\web\Application as WebApplication;
use app\services\Sitemap\SitemapGenerator;

/**
 * Автоматический генератор sitemap
 * Перехватывает завершение запроса и запускает генерацию если установлен флаг pending
 */
class SitemapAutoGenerator extends Component implements BootstrapInterface
{
    private const CACHE_LOCK_KEY = 'sitemap:lock';
    private const CACHE_CHECK_KEY = 'sitemap:lastCheck';

    public function bootstrap($app)
    {
        if (!$app instanceof WebApplication) {
            return;
        }

        $app->on(WebApplication::EVENT_AFTER_REQUEST, function () {
            $this->handleAfterRequest();
        });
    }

    private function handleAfterRequest(): void
    {
        $request = Yii::$app->request;

        $checkInterval = Yii::$app->params['sitemap']['cacheCheckInterval'] ?? 60;
        $now = time();

        $cache = Yii::$app->cache;
        if ($cache) {
            $lastCheck = $cache->get(self::CACHE_CHECK_KEY);
            if (is_array($lastCheck)) {
                $lastTime = (int)($lastCheck['time'] ?? 0);
                $lastPending = (bool)($lastCheck['pending'] ?? false);
                if (!$lastPending && $lastTime > 0 && ($now - $lastTime) < $checkInterval) {
                    return;
                }
            }
        }

        $pending = SitemapNotifier::isPending();

        if ($cache) {
            $cache->set(self::CACHE_CHECK_KEY, [
                'pending' => $pending,
                'time' => $now,
            ], $checkInterval * 2);
        }

        if (!$pending) {
            return;
        }

        if ($request->isAjax) {
            return;
        }

        $route = Yii::$app->requestedRoute;
        if ($route && strncmp($route, 'sitemap', 7) === 0) {
            return;
        }

        $cache = Yii::$app->cache;
        $lockTtl = Yii::$app->params['sitemap']['lockTtl'] ?? 600;
        
        if (!$cache || !$cache->add(self::CACHE_LOCK_KEY, time(), $lockTtl)) {
            return;
        }

        try {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }

            $generator = new SitemapGenerator();
            $generator->generate();
            SitemapNotifier::reset();
            if ($cache) {
                $cache->set(self::CACHE_CHECK_KEY, [
                    'pending' => false,
                    'time' => time(),
                ], $checkInterval * 2);
            }

            Yii::info('Sitemap regenerated automatically after pending flag.', __METHOD__);
        } catch (\Throwable $e) {
            Yii::error('Sitemap auto-generation failed: ' . $e->getMessage(), __METHOD__);
        } finally {
            if ($cache) {
                $cache->delete(self::CACHE_LOCK_KEY);
            }
        }
    }
}
