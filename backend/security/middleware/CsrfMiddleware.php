<?php

namespace app\backend\security\middleware;

use yii\base\ActionFilter;
use yii\web\BadRequestHttpException;

/**
 * Middleware CSRF защиты
 */
class CsrfMiddleware extends ActionFilter
{
    public array $except = [];

    public function beforeAction($action)
    {
        if (in_array($action->id, $this->except)) {
            return true;
        }

        $request = \Yii::$app->request;
        
        // Пропускаем GET запросы
        if ($request->getIsGet()) {
            return true;
        }

        // Валидация CSRF токена
        $token = $request->getBodyParam($request->csrfParam);
        $headerToken = $request->getHeaders()->get('X-CSRF-Token');

        if (!$this->validateToken($token, $headerToken)) {
            throw new BadRequestHttpException('Неверный CSRF токен');
        }

        return true;
    }

    private function validateToken(?string $token, ?string $headerToken): bool
    {
        $trueToken = \Yii::$app->request->csrfToken;

        return $token === $trueToken || $headerToken === $trueToken;
    }
}
