<?php

/**
 * CartController — Контроллер корзины покупок
 * 
 * НАЗНАЧЕНИЕ:
 * Управление корзиной покупателя: добавление, удаление, изменение количества товаров.
 * 
 * ФУНКЦИИ:
 * - Отображение корзины (index)
 * - Добавление товара в корзину (add)
 * - Обновление количества товара (update)
 * - Удаление товара из корзины (remove)
 * - Очистка корзины (clear)
 * - Получение количества товаров для счетчика в шапке (count)
 * - Проверка наличия товара в корзине (has-product)
 * 
 * СВЯЗИ:
 * - Cart (модель корзины)
 * - Product (модель товара)
 * 
 * ОСОБЕННОСТИ:
 * - Работает через AJAX-запросы
 * - Поддержка гостевой корзины (через сессию)
 * - CSRF отключен для AJAX для совместимости
 */
namespace app\backend\modules\cart\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\cart\models\Cart;
use app\backend\modules\catalog\models\Product;
use app\frontend\assets\CartAsset;

class CartController extends Controller
{
    public $layout = 'main'; // Единый layout frontend
    
    
    /**
     * Настройка CSRF для AJAX-запросов корзины
     * CSRF проверяется через X-CSRF-Token header
     */
    public function beforeAction($action)
    {
        if (in_array($action->id, ['add', 'update', 'remove', 'clear', 'count', 'has-product'])) {
            if (Yii::$app->request->isAjax) {
                // Проверяем CSRF через header вместо отключения
                $csrfToken = Yii::$app->request->getHeaders()->get('X-CSRF-Token');
                $validToken = Yii::$app->request->getCsrfToken();
                
                if ($csrfToken && hash_equals($validToken, $csrfToken)) {
                    // CSRF токен валиден
                    return parent::beforeAction($action);
                }
                // Если токен невалиден - CSRF проверка останется включенной
                
                // Если токен невалиден — возвращаем ошибку
                Yii::$app->response->format = Response::FORMAT_JSON;
                Yii::$app->response->data = [
                    'success' => false,
                    'message' => 'CSRF token validation failed',
                    'error' => 'csrf_invalid'
                ];
                Yii::$app->response->send();
                return false;
            }
        }
        return parent::beforeAction($action);
    }

    /**
     * Страница корзины
     */
    public function actionIndex()
    {
        // Демо-режим без БД
        $demoMode = false;
        $items = [];
        $total = 0;
        $customer = null;
        
        try {
            $items = Cart::getItems();
            $total = Cart::getTotal();
            
            $customerId = Yii::$app->session->get('customer_id');
            if ($customerId) {
                $customer = \app\backend\modules\catalog\models\Customer::findOne($customerId);
            }
        } catch (\Exception $e) {
            $demoMode = true;
            // Демо данные для корзины
            $items = [];
            $total = 0;
        }

        return $this->render('index', [
            'items' => $items,
            'total' => $total,
            'customer' => $customer,
            'demoMode' => $demoMode,
        ]);
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
