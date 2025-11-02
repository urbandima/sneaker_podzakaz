<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\ProductFavorite;
use app\models\Product;

/**
 * Контроллер избранного
 */
class FavoriteController extends Controller
{
    /**
     * Добавить в избранное (AJAX)
     */
    public function actionAdd()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = Yii::$app->request->post('product_id');

        if (!$productId) {
            return ['success' => false, 'message' => 'Товар не указан'];
        }

        $product = Product::findOne($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        if (ProductFavorite::add($productId, $userId, $sessionId)) {
            return [
                'success' => true,
                'message' => 'Товар добавлен в избранное',
                'count' => ProductFavorite::getCount($userId, $sessionId),
            ];
        }

        return ['success' => false, 'message' => 'Товар уже в избранном'];
    }

    /**
     * Удалить из избранного (AJAX)
     */
    public function actionRemove()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = Yii::$app->request->post('product_id');

        if (!$productId) {
            return ['success' => false, 'message' => 'Товар не указан'];
        }

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        if (ProductFavorite::remove($productId, $userId, $sessionId)) {
            return [
                'success' => true,
                'message' => 'Товар удален из избранного',
                'count' => ProductFavorite::getCount($userId, $sessionId),
            ];
        }

        return ['success' => false, 'message' => 'Ошибка удаления'];
    }

    /**
     * Toggle (добавить/удалить) избранное
     */
    public function actionToggle()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = Yii::$app->request->post('product_id');

        if (!$productId) {
            return ['success' => false, 'message' => 'Товар не указан'];
        }

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        // Проверяем - есть ли в избранном
        $exists = ProductFavorite::find()
            ->where(['product_id' => $productId])
            ->andWhere($userId ? ['user_id' => $userId] : ['session_id' => $sessionId])
            ->exists();

        if ($exists) {
            // Удаляем
            ProductFavorite::remove($productId, $userId, $sessionId);
            return [
                'success' => true,
                'action' => 'removed',
                'message' => 'Товар удален из избранного',
                'count' => ProductFavorite::getCount($userId, $sessionId),
            ];
        } else {
            // Добавляем
            if (ProductFavorite::add($productId, $userId, $sessionId)) {
                return [
                    'success' => true,
                    'action' => 'added',
                    'message' => 'Товар добавлен в избранное',
                    'count' => ProductFavorite::getCount($userId, $sessionId),
                ];
            }
        }

        return ['success' => false, 'message' => 'Ошибка'];
    }

    /**
     * Получить количество избранных
     */
    public function actionCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        return [
            'count' => ProductFavorite::getCount($userId, $sessionId),
        ];
    }
}
