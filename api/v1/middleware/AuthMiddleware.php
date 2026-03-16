<?php

namespace app\api\v1\middleware;

use yii\base\ActionFilter;
use yii\web\UnauthorizedHttpException;

/**
 * Middleware аутентификации API
 */
class AuthMiddleware extends ActionFilter
{
    public function beforeAction($action)
    {
        $request = \Yii::$app->request;
        $authHeader = $request->getHeaders()->get('Authorization');

        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            throw new UnauthorizedHttpException('Требуется токен авторизации');
        }

        $token = $matches[1];
        
        // Валидация токена (JWT или API key)
        $user = $this->validateToken($token);
        
        if (!$user) {
            throw new UnauthorizedHttpException('Недействительный токен');
        }

        \Yii::$app->user->setIdentity($user);
        return true;
    }

    /**
     * Валидация токена
     */
    private function validateToken(string $token): ?\app\backend\modules\admin\models\User
    {
        // JWT валидация
        try {
            $payload = \Firebase\JWT\JWT::decode($token, \Yii::$app->params['jwtSecret'], ['HS256']);
            return \app\backend\modules\admin\models\User::findOne($payload->user_id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
