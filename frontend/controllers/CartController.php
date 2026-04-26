<?php

namespace app\frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\catalog\models\Product;

/**
 * Контроллер корзины
 */
class CartController extends Controller
{
    public $layout = 'main';

    /**
     * Страница корзины — редиректим на оформление заказа
     */
    public function actionIndex()
    {
        return $this->redirect(['/checkout'], 302);
    }

    /**
     * Добавить товар в корзину (AJAX)
     */
    public function actionAdd()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $productId = Yii::$app->request->post('product_id');
        $quantity = Yii::$app->request->post('quantity', 1);
        $size = Yii::$app->request->post('size');
        $color = Yii::$app->request->post('color');

        if (!$productId) {
            return ['success' => false, 'message' => 'Товар не указан'];
        }

        $product = Product::findOne($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        // Refuse zero-price products
        if ($product->price <= 0) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'message' => 'Цена товара уточняется — добавление в корзину недоступно'];
        }

        // Require size when product has sizes
        if (empty($size) && $product->getSizes()->count() > 0) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'message' => 'Пожалуйста, выберите размер'];
        }

        if (Cart::add($productId, $quantity, $size, $color)) {
            return [
                'success' => true,
                'message' => 'Товар добавлен в корзину',
                'count' => Cart::getItemsCount(),
                'total' => Cart::getTotal(),
            ];
        }

        return ['success' => false, 'message' => 'Ошибка добавления'];
    }

    /**
     * Обновить количество товара (AJAX)
     */
    public function actionUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $quantity = Yii::$app->request->post('quantity');

        $cart = Cart::findOne($id);
        if (!$cart) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        if ($cart->updateQuantity($quantity)) {
            return [
                'success' => true,
                'subtotal' => (float) $cart->getSubtotal(),
                'total' => (float) Cart::getTotal(),
                'count' => Cart::getItemsCount(),
            ];
        }

        return ['success' => false, 'message' => 'Ошибка обновления'];
    }

    /**
     * Удалить товар из корзины (AJAX)
     */
    public function actionRemove($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $cart = Cart::findOne($id);
        if (!$cart) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }

        if ($cart->delete()) {
            return [
                'success' => true,
                'message' => 'Товар удален',
                'count' => Cart::getItemsCount(),
                'total' => Cart::getTotal(),
            ];
        }

        return ['success' => false, 'message' => 'Ошибка удаления'];
    }

    /**
     * Очистить корзину
     */
    public function actionClear()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        Cart::clear();

        return [
            'success' => true,
            'message' => 'Корзина очищена',
        ];
    }

    /**
     * Получить количество товаров (для обновления счетчика)
     */
    public function actionCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'count' => Cart::getItemsCount(),
            'total' => Cart::getTotal(),
        ];
    }

    /**
     * Получить содержимое корзины для Drawer (AJAX)
     */
    public function actionDrawerItems()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $items = Cart::getItems();
        $total = Cart::getTotal();

        $html = $this->renderPartial('_drawer_items', [
            'items' => $items,
        ]);

        return [
            'success' => true,
            'html' => $html,
            'count' => Cart::getItemsCount(),
            'total' => $total,
        ];
    }

    /**
     * Проверить наличие товара в корзине (AJAX)
     */
    public function actionHasProduct($productId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $sessionId = Yii::$app->session->id;
        $exists = Cart::find()
            ->where(['session_id' => $sessionId, 'product_id' => $productId])
            ->exists();

        return [
            'inCart' => $exists,
            'count' => Cart::getItemsCount(),
        ];
    }
}
