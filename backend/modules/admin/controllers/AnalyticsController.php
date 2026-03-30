<?php

/**
 * AnalyticsController — Контроллер аналитики и отчетов
 * 
 * НАЗНАЧЕНИЕ:
 * Сбор и отображение аналитических данных о работе магазина:
 * конверсия, продажи, популярные товары, поведение пользователей.
 * 
 * ФУНКЦИИ:
 * - Главная страница аналитики с виджетами (index)
 * - Отчет по конверсии (conversion)
 * - Отчет по продажам (sales)
 * - Отчет по товарам (products)
 * - Отчет по пользователям (users)
 * 
 * СВЯЗИ:
 * - AnalyticsEvent (модель событий аналитики)
 * - Order (модель заказа)
 * - Product (модель товара)
 * - User (модель пользователя)
 * 
 * ДОСТУП:
 * - Только администраторы и менеджеры
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\backend\modules\catalog\models\AnalyticsEvent;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\admin\models\User;

class AnalyticsController extends BaseAdminController
{
    public $layout = 'admin';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            return $user->isAdmin() || $user->isManager();
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Главная страница аналитики
     */
    public function actionIndex()
    {
        $period = Yii::$app->request->get('period', '30');
        $dateFrom = date('Y-m-d', strtotime("-{$period} days"));
        $dateTo = date('Y-m-d');
        
        // Конверсия
        $conversion = AnalyticsEvent::getConversionStats($dateFrom, $dateTo);
        
        // Источники трафика
        $trafficSources = AnalyticsEvent::getTrafficSources($dateFrom, $dateTo);
        
        // Популярные товары
        $popularProducts = AnalyticsEvent::getPopularProducts(10, $dateFrom, $dateTo);
        
        // Статистика заказов по дням
        $ordersByDay = $this->getOrdersByDay($dateFrom, $dateTo);
        
        // Статистика выручки
        $revenueStats = $this->getRevenueStats($dateFrom, $dateTo);
        
        // Статистика по устройствам
        $deviceStats = $this->getDeviceStats($dateFrom, $dateTo);
        
        return $this->render('index', [
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'conversion' => $conversion,
            'trafficSources' => $trafficSources,
            'popularProducts' => $popularProducts,
            'ordersByDay' => $ordersByDay,
            'revenueStats' => $revenueStats,
            'deviceStats' => $deviceStats,
        ]);
    }

    /**
     * Отчет по конверсии
     */
    public function actionConversion()
    {
        $period = Yii::$app->request->get('period', '30');
        $dateFrom = date('Y-m-d', strtotime("-{$period} days"));
        $dateTo = date('Y-m-d');
        
        $conversion = AnalyticsEvent::getConversionStats($dateFrom, $dateTo);
        
        // Конверсия по дням
        $conversionByDay = $this->getConversionByDay($dateFrom, $dateTo);
        
        return $this->render('conversion', [
            'period' => $period,
            'conversion' => $conversion,
            'conversionByDay' => $conversionByDay,
        ]);
    }

    /**
     * Отчет по продажам
     */
    public function actionSales()
    {
        $period = Yii::$app->request->get('period', '30');
        $dateFrom = date('Y-m-d', strtotime("-{$period} days"));
        $dateTo = date('Y-m-d');
        
        // Продажи по дням
        $salesByDay = $this->getSalesByDay($dateFrom, $dateTo);
        
        // Топ товары по продажам
        $topProducts = $this->getTopSellingProducts($dateFrom, $dateTo);
        
        // Топ категории
        $topCategories = $this->getTopCategories($dateFrom, $dateTo);
        
        // Средний чек
        $avgOrderValue = $this->getAverageOrderValue($dateFrom, $dateTo);
        
        return $this->render('sales', [
            'period' => $period,
            'salesByDay' => $salesByDay,
            'topProducts' => $topProducts,
            'topCategories' => $topCategories,
            'avgOrderValue' => $avgOrderValue,
        ]);
    }

    /**
     * RFM аналитика - сегментация клиентов
     */
    public function actionRfm()
    {
        return $this->render('rfm');
    }

    /**
     * Экспорт отчета
     */
    public function actionExport()
    {
        $type = Yii::$app->request->get('type', 'sales');
        $period = Yii::$app->request->get('period', '30');
        $format = Yii::$app->request->get('format', 'csv');
        
        $dateFrom = date('Y-m-d', strtotime("-{$period} days"));
        $dateTo = date('Y-m-d');
        
        // Генерация данных в зависимости от типа отчета
        $data = [];
        $filename = "report_{$type}_{$dateFrom}_{$dateTo}";
        
        switch ($type) {
            case 'sales':
                $data = $this->getSalesByDay($dateFrom, $dateTo);
                break;
            case 'conversion':
                $data = [AnalyticsEvent::getConversionStats($dateFrom, $dateTo)];
                break;
            case 'products':
                $data = $this->getTopSellingProducts($dateFrom, $dateTo, 100);
                break;
        }
        
        if ($format === 'csv') {
            return $this->exportCsv($data, $filename);
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Заказы по дням
     */
    protected function getOrdersByDay($dateFrom, $dateTo)
    {
        return Yii::$app->db->createCommand("
            SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue
            FROM `order`
            WHERE DATE(created_at) BETWEEN :from AND :to
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryAll();
    }

    /**
     * Статистика выручки
     */
    protected function getRevenueStats($dateFrom, $dateTo)
    {
        return Yii::$app->db->createCommand("
            SELECT 
                SUM(total_amount) as total_revenue,
                COUNT(*) as total_orders,
                AVG(total_amount) as avg_order_value,
                MAX(total_amount) as max_order,
                MIN(total_amount) as min_order
            FROM `order`
            WHERE DATE(created_at) BETWEEN :from AND :to
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryOne();
    }

    /**
     * Статистика по устройствам
     */
    protected function getDeviceStats($dateFrom, $dateTo)
    {
        if (!AnalyticsEvent::isAvailable()) {
            return [];
        }

        return Yii::$app->db->createCommand("
            SELECT device_type, COUNT(*) as count
            FROM analytics_event
            WHERE event_type = 'page_view' 
            AND DATE(created_at) BETWEEN :from AND :to
            GROUP BY device_type
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryAll();
    }

    /**
     * Конверсия по дням
     */
    protected function getConversionByDay($dateFrom, $dateTo)
    {
        if (!AnalyticsEvent::isAvailable()) {
            return [];
        }

        return Yii::$app->db->createCommand("
            SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) as page_views,
                SUM(CASE WHEN event_type = 'product_view' THEN 1 ELSE 0 END) as product_views,
                SUM(CASE WHEN event_type = 'add_to_cart' THEN 1 ELSE 0 END) as add_to_cart,
                SUM(CASE WHEN event_type = 'order_created' THEN 1 ELSE 0 END) as orders
            FROM analytics_event
            WHERE DATE(created_at) BETWEEN :from AND :to
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryAll();
    }

    /**
     * Продажи по дням
     */
    protected function getSalesByDay($dateFrom, $dateTo)
    {
        return Yii::$app->db->createCommand("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as avg_order
            FROM `order`
            WHERE DATE(created_at) BETWEEN :from AND :to
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryAll();
    }

    /**
     * Топ продаваемых товаров
     */
    protected function getTopSellingProducts($dateFrom, $dateTo, $limit = 10)
    {
        return Yii::$app->db->createCommand("
            SELECT 
                oi.product_name,
                SUM(oi.quantity) as total_qty,
                SUM(oi.price * oi.quantity) as total_revenue,
                COUNT(DISTINCT oi.order_id) as orders_count
            FROM order_item oi
            JOIN `order` o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN :from AND :to
            GROUP BY oi.product_name
            ORDER BY total_qty DESC
            LIMIT :limit
        ", [':from' => $dateFrom, ':to' => $dateTo, ':limit' => $limit])->queryAll();
    }

    /**
     * Топ категории
     */
    protected function getTopCategories($dateFrom, $dateTo)
    {
        return Yii::$app->db->createCommand("
            SELECT 
                c.name as category_name,
                COUNT(DISTINCT o.id) as orders_count,
                SUM(oi.quantity) as total_qty
            FROM order_item oi
            JOIN `order` o ON oi.order_id = o.id
            JOIN product p ON oi.product_name = p.name
            JOIN category c ON p.category_id = c.id
            WHERE DATE(o.created_at) BETWEEN :from AND :to
            GROUP BY c.id
            ORDER BY total_qty DESC
            LIMIT 10
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryAll();
    }

    /**
     * Средний чек
     */
    protected function getAverageOrderValue($dateFrom, $dateTo)
    {
        return Yii::$app->db->createCommand("
            SELECT AVG(total_amount) as avg_value
            FROM `order`
            WHERE DATE(created_at) BETWEEN :from AND :to
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryScalar() ?: 0;
    }

    /**
     * Экспорт в CSV
     */
    protected function exportCsv($data, $filename)
    {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_RAW;
        $response->headers->add('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->add('Content-Disposition', "attachment; filename={$filename}.csv");
        
        $output = fopen('php://temp', 'r+');
        
        if (!empty($data)) {
            // Заголовки
            fputcsv($output, array_keys($data[0]));
            
            // Данные
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return "\xEF\xBB\xBF" . $csv; // BOM для UTF-8
    }
}
