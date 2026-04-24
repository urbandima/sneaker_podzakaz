<?php

namespace app\frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\catalog\models\ProductFavorite;
use app\backend\modules\catalog\models\Product;

/**
 * Контроллер избранного
 */
class FavoriteController extends Controller
{
    public $layout = 'main'; // Единый layout
    
    /**
     * Страница избранного (редирект на каталог/избранное)
     */
    public function actionIndex()
    {
        return $this->redirect(['/catalog/favorites']);
    }

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

        $userId    = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        return ['count' => ProductFavorite::getCount($userId, $sessionId)];
    }

    /**
     * Получить список product_id в избранном (для клиентской отметки кнопок)
     */
    public function actionIds()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId    = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        $query = ProductFavorite::find()->select('product_id');
        if ($userId) {
            $query->where(['user_id' => $userId]);
        } else {
            $query->where(['session_id' => $sessionId]);
        }

        return ['ids' => $query->column()];
    }

    /**
     * Merge guest localStorage wishlist after login (POST AJAX, JSON body)
     */
    public function actionMergeGuest()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->session->get('customer_id');
        if (!$userId) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }

        $body = Yii::$app->request->rawBody;
        $data = json_decode($body, true);
        $ids = isset($data['ids']) && is_array($data['ids']) ? $data['ids'] : [];

        foreach ($ids as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) continue;
            ProductFavorite::add($productId, $userId, null);
        }

        return [
            'success' => true,
            'count' => ProductFavorite::getCount($userId, null),
        ];
    }

    /**
     * Очистить всё избранное (POST AJAX)
     */
    public function actionClear()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId    = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $sessionId = Yii::$app->session->id;

        if ($userId) {
            ProductFavorite::deleteAll(['user_id' => $userId]);
        } else {
            ProductFavorite::deleteAll(['session_id' => $sessionId]);
        }

        return ['success' => true, 'count' => 0];
    }
}
