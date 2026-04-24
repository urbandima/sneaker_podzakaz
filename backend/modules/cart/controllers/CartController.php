<?php

/**
 * CartController — Контроллер корзины покупок
 *
 * Расширяет frontend CartController, добавляя:
 * - CSRF-валидацию через X-CSRF-Token заголовок для AJAX-запросов
 * - Демо-режим (если БД недоступна) в actionIndex
 * - Поиск Customer через сессию вместо Yii::$app->user
 */
namespace app\backend\modules\cart\controllers;

use Yii;
use yii\web\Response;
use app\frontend\controllers\CartController as FrontendCartController;
use app\backend\modules\account\models\Customer;

class CartController extends FrontendCartController
{
    /**
     * CSRF-валидация через X-CSRF-Token header для AJAX-запросов корзины
     */
    public function beforeAction($action)
    {
        $ajaxActions = ['add', 'update', 'remove', 'clear', 'count', 'has-product'];

        if (in_array($action->id, $ajaxActions) && Yii::$app->request->isAjax) {
            $csrfToken = Yii::$app->request->getHeaders()->get('X-CSRF-Token');

            if (!$csrfToken || !Yii::$app->request->validateCsrfToken($csrfToken)) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->data = [
                    'success' => false,
                    'message' => 'CSRF token validation failed',
                    'error'   => 'csrf_invalid',
                ];
                Yii::$app->response->send();
                return false;
            }

            // CSRF is valid; disable framework-level re-validation to avoid double-check
            Yii::$app->controller->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Страница корзины — с демо-режимом и поиском Customer через сессию
     */
    public function actionIndex()
    {
        return $this->redirect(['/order/index'], 301);
    }
}
