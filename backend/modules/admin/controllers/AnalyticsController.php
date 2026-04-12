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
        
        // RFM data
        $rfmSegments = $this->getRfmSegments();

        // Team stats
        $teamStats = $this->getTeamStats();

        // Conversion funnel
        $conversionFunnel = $this->getConversionFunnel($dateFrom, $dateTo);

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
            // aliases expected by view
            'salesByDay' => $ordersByDay,
            'topProducts' => $popularProducts,
            // new blocks
            'rfmSegments' => $rfmSegments,
            'teamStats' => $teamStats,
            'conversionFunnel' => $conversionFunnel,
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
     * RFM-анализ клиентов - HTML страница
     */
    public function actionRfm()
    {
        return $this->render('rfm');
    }

    /**
     * RFM-анализ клиентов - JSON API
     */
    public function actionRfmApi()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $segments = $this->getRfmSegments();
        return ['success' => true, 'segments' => $segments];
    }

    /**
     * Отчёт по команде (менеджеры / логисты)
     */
    public function actionTeam()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $team = $this->getTeamStats();
        return ['success' => true, 'team' => $team];
    }

    /**
     * Экспорт RFM-сегмента в CSV
     */
    public function actionExportRfm()
    {
        $segment = Yii::$app->request->get('segment', '');
        $rows    = $this->getRfmCustomersForSegment($segment);
        $filename = 'rfm_' . preg_replace('/[^a-z0-9]/i', '_', $segment) . '_' . date('Y-m-d');
        return $this->exportCsv($rows, $filename);
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
     * Конверсионная воронка из таблицы analytics_event
     */
    protected function getConversionFunnel($dateFrom, $dateTo)
    {
        if (!AnalyticsEvent::isAvailable()) {
            return ['views' => 0, 'add_to_cart' => 0, 'orders' => 0];
        }

        $row = Yii::$app->db->createCommand("
            SELECT
                SUM(CASE WHEN event_type IN ('view','product_view','page_view') THEN 1 ELSE 0 END) AS views,
                SUM(CASE WHEN event_type = 'add_to_cart' THEN 1 ELSE 0 END) AS add_to_cart,
                SUM(CASE WHEN event_type IN ('order','order_created') THEN 1 ELSE 0 END) AS orders
            FROM analytics_event
            WHERE DATE(created_at) BETWEEN :from AND :to
        ", [':from' => $dateFrom, ':to' => $dateTo])->queryOne();

        return $row ?: ['views' => 0, 'add_to_cart' => 0, 'orders' => 0];
    }

    /**
     * RFM-сегменты: Champion, Loyal, At Risk, Lost, New
     * Возвращает массив ['segment' => ..., 'count' => ..., 'avg_monetary' => ...]
     */
    protected function getRfmSegments()
    {
        $rows = Yii::$app->db->createCommand("
            SELECT
                c.id,
                CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')) as name,
                c.email,
                DATEDIFF(NOW(), MAX(o.created_at)) AS recency,
                COUNT(DISTINCT o.id)               AS frequency,
                COALESCE(SUM(o.total_amount), 0)   AS monetary
            FROM customer c
            JOIN `order` o ON o.customer_id = c.id
            GROUP BY c.id
        ")->queryAll();

        $segments = [
            'Champion'  => ['count' => 0, 'monetary_sum' => 0],
            'Loyal'     => ['count' => 0, 'monetary_sum' => 0],
            'At Risk'   => ['count' => 0, 'monetary_sum' => 0],
            'Lost'      => ['count' => 0, 'monetary_sum' => 0],
            'New'       => ['count' => 0, 'monetary_sum' => 0],
        ];

        foreach ($rows as $r) {
            $r_val = (int)$r['recency'];
            $f_val = (int)$r['frequency'];
            $m_val = (float)$r['monetary'];

            if ($r_val < 30 && $f_val > 3 && $m_val > 5000) {
                $seg = 'Champion';
            } elseif ($f_val > 3) {
                $seg = 'Loyal';
            } elseif ($r_val > 60 && $f_val > 1) {
                $seg = 'At Risk';
            } elseif ($r_val > 90 && $f_val === 1) {
                $seg = 'Lost';
            } else {
                $seg = 'New';
            }

            $segments[$seg]['count']++;
            $segments[$seg]['monetary_sum'] += $m_val;
        }

        $result = [];
        foreach ($segments as $name => $data) {
            $result[] = [
                'segment'      => $name,
                'count'        => $data['count'],
                'avg_monetary' => $data['count'] > 0 ? round($data['monetary_sum'] / $data['count'], 2) : 0,
            ];
        }

        return $result;
    }

    /**
     * Клиенты конкретного RFM-сегмента (для CSV-экспорта)
     */
    protected function getRfmCustomersForSegment(string $segment)
    {
        $rows = Yii::$app->db->createCommand("
            SELECT
                c.id,
                CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')) as name,
                c.email,
                c.phone,
                DATEDIFF(NOW(), MAX(o.created_at)) AS recency,
                COUNT(DISTINCT o.id)               AS frequency,
                COALESCE(SUM(o.total_amount), 0)   AS monetary
            FROM customer c
            JOIN `order` o ON o.customer_id = c.id
            GROUP BY c.id
        ")->queryAll();

        $filtered = [];
        foreach ($rows as $r) {
            $r_val = (int)$r['recency'];
            $f_val = (int)$r['frequency'];
            $m_val = (float)$r['monetary'];

            if ($segment === 'Champion') {
                $match = $r_val < 30 && $f_val > 3 && $m_val > 5000;
            } elseif ($segment === 'Loyal') {
                $match = $f_val > 3 && !($r_val < 30 && $m_val > 5000);
            } elseif ($segment === 'At Risk') {
                $match = $r_val > 60 && $f_val > 1;
            } elseif ($segment === 'Lost') {
                $match = $r_val > 90 && $f_val === 1;
            } else {
                $match = true; // New — всё остальное
            }

            if ($match) {
                $filtered[] = [
                    'ID'        => $r['id'],
                    'Имя'       => $r['name'],
                    'Email'     => $r['email'],
                    'Телефон'   => $r['phone'] ?? '',
                    'Давность'  => $r_val . ' дн.',
                    'Заказов'   => $f_val,
                    'Выручка'   => number_format($m_val, 2),
                ];
            }
        }

        return $filtered;
    }

    /**
     * Статистика по команде (менеджеры)
     */
    protected function getTeamStats()
    {
        // Пробуем сгруппировать по assigned_logist или created_by
        $colCheck = Yii::$app->db->createCommand("SHOW COLUMNS FROM `order` LIKE 'assigned_logist'")->queryOne();
        $colBy    = Yii::$app->db->createCommand("SHOW COLUMNS FROM `order` LIKE 'created_by'")->queryOne();

        if ($colCheck) {
            $groupCol = 'assigned_logist';
        } elseif ($colBy) {
            $groupCol = 'created_by';
        } else {
            return [];
        }

        return Yii::$app->db->createCommand("
            SELECT
                COALESCE({$groupCol}, 'Не назначен') AS manager,
                COUNT(*) AS order_count,
                COALESCE(SUM(total_amount), 0) AS revenue,
                COALESCE(AVG(total_amount), 0) AS avg_check
            FROM `order`
            GROUP BY {$groupCol}
            ORDER BY revenue DESC
            LIMIT 50
        ")->queryAll();
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
