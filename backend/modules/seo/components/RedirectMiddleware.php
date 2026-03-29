<?php

/**
 * RedirectMiddleware — Middleware для обработки редиректов
 * 
 * Проверяет URL и выполняет редирект если настроен
 */
namespace app\backend\modules\seo\components;

use Yii;
use yii\base\BaseObject;
use yii\web\Response;
use app\backend\modules\seo\models\Redirect;

class RedirectMiddleware extends BaseObject
{
    /**
     * Обработка запроса
     * 
     * @param \yii\web\Request $request
     * @param \Closure $next
     * @return Response|mixed
     */
    public function handle($request, $next)
    {
        $path = $request->pathInfo;
        $query = $request->queryString;
        $fullUrl = $path . ($query ? '?' . $query : '');

        // Ищем редирект
        $redirect = $this->findRedirect($path, $fullUrl);

        if ($redirect && $redirect['type'] !== Redirect::TYPE_404) {
            // Выполняем редирект
            $response = Yii::$app->response;
            $response->redirect($redirect['url'], $redirect['type']);
            return $response;
        }

        return $next($request);
    }

    /**
     * Найти редирект
     */
    private function findRedirect(string $path, string $fullUrl): ?array
    {
        // Пробуем найти по полному URL
        $result = Redirect::findAndApply($fullUrl);
        if ($result) {
            return $result;
        }

        // Пробуем найти по пути без query string
        $result = Redirect::findAndApply($path);
        if ($result) {
            return $result;
        }

        // Пробуем найти с/без слеша
        if (substr($path, -1) === '/') {
            $result = Redirect::findAndApply(rtrim($path, '/'));
        } else {
            $result = Redirect::findAndApply($path . '/');
        }

        return $result;
    }
}
