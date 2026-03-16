<?php

/**
 * SearchController — Глобальный поиск по админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * Быстрый поиск по всем сущностям системы: заказы, товары, пользователи.
 * Используется для глобального поиска в шапке админки.
 * 
 * ФУНКЦИИ:
 * - Глобальный поиск по всем сущностям (global)
 * - Поиск заказов (orders)
 * - Поиск товаров (products)
 * - Поиск пользователей (users)
 * 
 * СВЯЗИ:
 * - Order (модель заказа)
 * - Product (модель товара)
 * - User (модель пользователя)
 * 
 * ОСОБЕННОСТИ:
 * - Работает через AJAX
 * - Возвращает результаты в формате JSON
 * - Ограничение на количество результатов (max 50)
 * - Учитывает права доступа (логисты видят только свои заказы)
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\filters\AccessControl;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\admin\models\User;

class SearchController extends BaseAdminController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * AJAX поиск по всем сущностям
     */
    public function actionGlobal()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q', '');
        $limit = min((int)Yii::$app->request->get('limit', 10), 50);
        
        if (strlen($query) < 2) {
            return ['results' => []];
        }
        
        $user = Yii::$app->user->identity;
        $results = [];
        
        // Поиск заказов
        $orderQuery = Order::find()
            ->with(['creator', 'logist'])
            ->where(['or',
                ['like', 'order_number', $query],
                ['like', 'client_name', $query],
                ['like', 'client_phone', $query],
                ['like', 'client_email', $query],
                ['like', 'china_track_number', $query],
            ])
            ->limit($limit / 2);
            
        if ($user->isLogist()) {
            $orderQuery->andWhere(['assigned_logist' => $user->id]);
        }
        
        $orders = $orderQuery->all();
        
        foreach ($orders as $order) {
            $results[] = [
                'type' => 'order',
                'title' => 'Заказ #' . $order->order_number,
                'description' => $order->client_name . ' • ' . Yii::$app->formatter->asDecimal($order->total_amount, 2) . ' BYN',
                'url' => ['/admin/order/view', 'id' => $order->id],
                'status' => $order->getStatusLabel(),
                'date' => Yii::$app->formatter->asDate($order->created_at, 'short'),
                'icon' => 'bi bi-box-seam',
            ];
        }
        
        // Поиск товаров (только для админов)
        if ($user->isAdmin()) {
            $products = Product::find()
                ->with(['brand'])
                ->where(['or',
                    ['like', 'name', $query],
                    ['like', 'vendor_code', $query],
                    ['like', 'poizon_id', $query],
                ])
                ->andWhere(['is_active' => 1])
                ->limit($limit / 2)
                ->all();
                
            foreach ($products as $product) {
                $results[] = [
                    'type' => 'product',
                    'title' => $product->name,
                    'description' => ($product->brand ? $product->brand->name . ' • ' : '') . 
                                    ($product->vendor_code ? 'Арт. ' . $product->vendor_code . ' • ' : '') .
                                    Yii::$app->formatter->asCurrency($product->price, 'BYN'),
                    'url' => ['/admin/product/view', 'id' => $product->id],
                    'status' => $product->stock_status,
                    'date' => $product->created_at ? Yii::$app->formatter->asDate($product->created_at, 'short') : '',
                    'icon' => 'bi bi-box',
                ];
            }
        }
        
        // Поиск пользователей (только для админов)
        if ($user->isAdmin()) {
            $users = User::find()
                ->where(['or',
                    ['like', 'username', $query],
                    ['like', 'email', $query],
                ])
                ->limit(5)
                ->all();
                
            foreach ($users as $user) {
                $results[] = [
                    'type' => 'user',
                    'title' => $user->username,
                    'description' => $user->email . ' • ' . $user->getRoleLabel(),
                    'url' => ['/admin/user/view', 'id' => $user->id],
                    'status' => $user->status,
                    'date' => Yii::$app->formatter->asDate($user->created_at, 'short'),
                    'icon' => 'bi bi-person',
                ];
            }
        }
        
        // Сортировка по релевантности (новые и точные совпадения выше)
        usort($results, function($a, $b) use ($query) {
            $aExact = stripos($a['title'], $query) === 0 ? 1 : 0;
            $bExact = stripos($b['title'], $query) === 0 ? 1 : 0;
            
            if ($aExact !== $bExact) {
                return $bExact - $aExact;
            }
            
            return 0;
        });
        
        return ['results' => array_slice($results, 0, $limit)];
    }
    
    /**
     * Быстрый поиск заказов для autocomplete
     */
    public function actionOrders()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q', '');
        $limit = min((int)Yii::$app->request->get('limit', 20), 50);
        
        if (strlen($query) < 2) {
            return ['results' => []];
        }
        
        $user = Yii::$app->user->identity;
        $orderQuery = Order::find()
            ->select(['id', 'order_number', 'client_name', 'total_amount', 'status'])
            ->where(['or',
                ['like', 'order_number', $query],
                ['like', 'client_name', $query],
                ['like', 'client_phone', $query],
                ['like', 'china_track_number', $query],
            ])
            ->limit($limit);
            
        if ($user->isLogist()) {
            $orderQuery->andWhere(['assigned_logist' => $user->id]);
        }
        
        $orders = $orderQuery->all();
        
        $results = [];
        foreach ($orders as $order) {
            $results[] = [
                'id' => $order->id,
                'text' => $order->order_number . ' - ' . $order->client_name,
                'total' => Yii::$app->formatter->asDecimal($order->total_amount, 2) . ' BYN',
                'status' => $order->getStatusLabel(),
            ];
        }
        
        return ['results' => $results];
    }
}
