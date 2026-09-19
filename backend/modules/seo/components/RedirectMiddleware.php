<?php

/**
 * RedirectMiddleware — Middleware для обработки редиректов
 *
 * Проверяет URL и выполняет редирект если настроен.
 * Подключается через bootstrap EVENT_BEFORE_REQUEST.
 */

namespace app\backend\modules\seo\components;

use Yii;
use yii\base\BaseObject;
use yii\base\BootstrapInterface;
use yii\web\Application as WebApplication;
use app\backend\modules\seo\models\Redirect;

class RedirectMiddleware extends BaseObject implements BootstrapInterface
{
    /**
     * Статические паттерн-редиректы для SEO (301 Permanent).
     * brand/{slug} и catalog/brand/{slug} — устаревшие форматы;
     * canonical URL брендов — /brands/{slug}.
     */
    private const PATTERN_REDIRECTS = [
        '~^brand/([a-z0-9-]+)$~'         => '/brands/$1',
        '~^catalog/brand/([a-z0-9-]+)$~' => '/brands/$1',
    ];

    /**
     * Регистрируем обработчик до обработки запроса.
     */
    public function bootstrap($app): void
    {
        if (!($app instanceof WebApplication)) {
            return;
        }

        $app->on(WebApplication::EVENT_BEFORE_REQUEST, function () use ($app) {
            $path    = $app->request->pathInfo;
            $query   = $app->request->queryString;
            $fullUrl = $path . ($query ? '?' . $query : '');

            // 1) Паттерн-редиректы (без DB-запроса)
            foreach (self::PATTERN_REDIRECTS as $pattern => $replacement) {
                if (preg_match($pattern, $path)) {
                    $to = preg_replace($pattern, $replacement, $path);
                    if ($query) {
                        $to .= '?' . $query;
                    }
                    $app->response->redirect($to, 301)->send();
                    $app->end();
                    return;
                }
            }

            // 2) DB-редиректы
            $redirect = $this->findRedirect($path, $fullUrl);
            if ($redirect && $redirect['type'] !== Redirect::TYPE_404) {
                $app->response->redirect($redirect['url'], $redirect['type'])->send();
                $app->end();
            }
        });
    }

    /**
     * Найти редирект по URL (полный URL с query или только path).
     */
    private function findRedirect(string $path, string $fullUrl): ?array
    {
        try {
            $result = Redirect::findAndApply($fullUrl);
            if ($result) {
                return $result;
            }

            $result = Redirect::findAndApply($path);
            if ($result) {
                return $result;
            }

            // Попробуем с/без trailing slash
            if (substr($path, -1) === '/') {
                $result = Redirect::findAndApply(rtrim($path, '/'));
            } else {
                $result = Redirect::findAndApply($path . '/');
            }

            return $result;
        } catch (\yii\db\Exception $e) {
            Yii::error('RedirectMiddleware: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }
}
