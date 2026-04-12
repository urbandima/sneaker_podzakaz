<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\marketing\services\AbandonedCartService;
use app\backend\modules\marketing\services\UpsellService;
use app\backend\modules\catalog\models\Product;

class MarketingController extends BaseAdminController
{
    /**
     * Главная страница маркетинга
     */
    public function actionIndex()
    {
        $abandonedService = new AbandonedCartService();
        $upsellService = new UpsellService();
        
        $abandonedStats = $abandonedService->getAbandonedCartStats();
        $abandonedCarts = $abandonedService->getAbandonedCarts(10);
        $recommendationStats = $upsellService->getRecommendationStats();
        
        return $this->render('index', [
            'abandonedStats' => $abandonedStats,
            'abandonedCarts' => $abandonedCarts,
            'recommendationStats' => $recommendationStats,
        ]);
    }
    
    /**
     * Брошенные корзины
     */
    public function actionAbandonedCarts()
    {
        $service = new AbandonedCartService();
        
        $carts = $service->getAbandonedCarts(100);
        $stats = $service->getAbandonedCartStats();
        
        return $this->render('abandoned-carts', [
            'carts' => $carts,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Отправить напоминание о брошенной корзине
     */
    public function actionSendReminder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $cartId = Yii::$app->request->post('cart_id');
        
        if (!$cartId) {
            return ['success' => false, 'message' => 'ID корзины не указан'];
        }
        
        $cart = \app\backend\modules\cart\models\Cart::findOne($cartId);
        
        if (!$cart) {
            return ['success' => false, 'message' => 'Корзина не найдена'];
        }
        
        $service = new AbandonedCartService();
        
        if ($service->sendAbandonedCartEmail($cart)) {
            return ['success' => true, 'message' => 'Напоминание отправлено'];
        }
        
        return ['success' => false, 'message' => 'Ошибка отправки'];
    }
    
    /**
     * Массовая отправка напоминаний
     */
    public function actionSendBulkReminders()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $service = new AbandonedCartService();
        $sent = $service->sendAutomaticReminders();
        
        return [
            'success' => true,
            'message' => "Отправлено напоминаний: {$sent}",
            'count' => $sent,
        ];
    }
    
    /**
     * Рекомендации товаров
     */
    public function actionRecommendations()
    {
        $service = new UpsellService();
        $stats = $service->getRecommendationStats();
        
        // Примеры рекомендаций для популярных товаров
        $products = Product::find()
            ->where(['is_active' => true])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit(5)
            ->all();
        
        $recommendations = [];
        foreach ($products as $product) {
            $recommendations[] = [
                'product' => $product,
                'cross_sell' => $service->getProductRecommendations($product, 3),
                'upsell' => $service->getUpsellProducts($product, 2),
                'frequently_bought' => $service->getFrequentlyBoughtTogether($product, 2),
            ];
        }
        
        return $this->render('recommendations', [
            'stats' => $stats,
            'recommendations' => $recommendations,
        ]);
    }
    
    /**
     * Получить рекомендации для товара (AJAX)
     */
    public function actionGetRecommendations()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $productId = Yii::$app->request->get('product_id');
        $type = Yii::$app->request->get('type', 'cross-sell');
        
        if (!$productId) {
            return ['success' => false, 'message' => 'ID товара не указан'];
        }
        
        $product = Product::findOne($productId);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Товар не найден'];
        }
        
        $service = new UpsellService();
        
        switch ($type) {
            case 'upsell':
                $products = $service->getUpsellProducts($product);
                break;
            case 'frequently-bought':
                $products = $service->getFrequentlyBoughtTogether($product);
                break;
            default:
                $products = $service->getProductRecommendations($product);
        }
        
        $result = [];
        foreach ($products as $p) {
            $result[] = [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'image' => $p->getMainImageUrl(),
            ];
        }
        
        return [
            'success' => true,
            'products' => $result,
        ];
    }
    
    /**
     * Маркетинговые кампании
     */
    public function actionCampaigns()
    {
        return $this->render('campaigns');
    }
}
