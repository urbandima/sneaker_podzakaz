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
        $rawPeriod = Yii::$app->request->get('period', '30');
        $periodMap = ['week' => 7, 'month' => 30, 'year' => 365, 'quarter' => 90];
        $dateTo = date('Y-m-d');
        if ($rawPeriod === 'today') {
            $period = 1;
            $dateFrom = $dateTo;
        } else {
            $period = isset($periodMap[$rawPeriod]) ? $periodMap[$rawPeriod] : max(1, (int) $rawPeriod);
            $dateFrom = date('Y-m-d', strtotime("-{$period} days"));
        }

        // Конверсия
        $conversion = AnalyticsEvent::getConversionStats($dateFrom, $dateTo);

        // Источники трафика
        $trafficSources = AnalyticsEvent::getTrafficSources($dateFrom, $dateTo);

        // Популярные товары
        $popularProducts = AnalyticsEvent::getPopularProducts(10, $dateFrom, $dateTo);

        // Статистика заказов по дням
        $ordersByDay = $this->getOrdersByDay($dateFrom, $dateTo);

        // Статистика выручки текущего периода
        $revenueStats = $this->getRevenueStats($dateFrom, $dateTo);

        // Статистика выручки предыдущего периода (для сравнения)
        $prevDateFrom = date('Y-m-d', strtotime("-" . ($period * 2) . " days"));
        $prevDateTo   = date('Y-m-d', strtotime("-{$period} days - 1 day"));
        $prevRevenueStats = $this->getRevenueStats($prevDateFrom, $prevDateTo);

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
            'prevRevenueStats' => $prevRevenueStats,
            'deviceStats' => $deviceStats,
            'salesByDay' => $this->getSalesByDay($dateFrom, $dateTo),
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
     * Конверсионная аналитика по товарам
     */
    public function actionConversions()
    {
        $period = Yii::$app->request->get('period', '30');
        $dateFrom = date('Y-m-d', strtotime("-{$period} days"));
        $dateTo = date('Y-m-d');

        // Топ товаров: просмотры, корзина, покупки
        $products = AnalyticsEvent::getPopularProducts(50, $dateFrom, $dateTo);

        // Общая воронка
        $funnel = $this->getConversionFunnel($dateFrom, $dateTo);

        // Продажи по дням для мини-графика
        $salesByDay = $this->getSalesByDay($dateFrom, $dateTo);

        return $this->render('conversions', [
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'products' => $products,
            'funnel' => $funnel,
            'salesByDay' => $salesByDay,
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
        // Pull every customer + their order totals in one query (LEFT JOIN keeps 0-order customers)
        $rows = Yii::$app->db->createCommand("
            SELECT
                c.id,
                TRIM(CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,''))) AS name,
                COALESCE(c.email, '')  AS email,
                COALESCE(c.phone, '')  AS phone,
                COUNT(DISTINCT o.id)                                                   AS frequency,
                COALESCE(SUM(o.total_amount), 0)                                       AS monetary,
                CASE WHEN MAX(o.created_at) IS NOT NULL
                     THEN DATEDIFF(NOW(), FROM_UNIXTIME(MAX(o.created_at)))
                     ELSE NULL END                                                      AS recency,
                CASE WHEN MAX(o.created_at) IS NOT NULL
                     THEN DATE(FROM_UNIXTIME(MAX(o.created_at)))
                     ELSE NULL END                                                      AS last_order_date
            FROM customer c
            LEFT JOIN `order` o ON o.customer_id = c.id
                AND o.status NOT IN ('cancelled', 'refunded')
            GROUP BY c.id
            ORDER BY monetary DESC
        ")->queryAll();

        $segmentDefs = [
            'champions' => ['label' => 'Чемпионы',            'color' => '#10b981', 'desc' => 'Покупают часто, недавно и много'],
            'loyal'     => ['label' => 'Лояльные',             'color' => '#3b82f6', 'desc' => 'Регулярные покупатели с высоким чеком'],
            'potential' => ['label' => 'Потенциальные',        'color' => '#8b5cf6', 'desc' => 'Недавние клиенты с потенциалом роста'],
            'new'       => ['label' => 'Новички',              'color' => '#06b6d4', 'desc' => 'Новые клиенты, требуют внимания'],
            'at_risk'   => ['label' => 'Нуждаются во внимании','color' => '#f59e0b', 'desc' => 'Снижение активности, нужно удержать'],
            'lost'      => ['label' => 'Потерянные',           'color' => '#ef4444', 'desc' => 'Давно не покупали'],
            'no_orders' => ['label' => 'Без заказов',          'color' => '#9ca3af', 'desc' => 'Зарегистрированы, но ещё не купили'],
        ];

        $stats = [];
        foreach ($segmentDefs as $key => $def) {
            $stats[$key] = ['count' => 0, 'revenue' => 0] + $def;
        }

        $allCustomers   = [];
        $atRiskCustomers = [];

        foreach ($rows as $r) {
            $days  = $r['recency'] !== null ? (int)$r['recency'] : null;
            $freq  = (int)$r['frequency'];
            $money = (float)$r['monetary'];

            // R score: fewer days = higher
            if ($days === null)      $rScore = 0;
            elseif ($days <= 30)     $rScore = 5;
            elseif ($days <= 60)     $rScore = 4;
            elseif ($days <= 90)     $rScore = 3;
            elseif ($days <= 180)    $rScore = 2;
            else                     $rScore = 1;

            // F score
            if ($freq >= 10)    $fScore = 5;
            elseif ($freq >= 5) $fScore = 4;
            elseif ($freq >= 3) $fScore = 3;
            elseif ($freq >= 2) $fScore = 2;
            elseif ($freq >= 1) $fScore = 1;
            else                $fScore = 0;

            // M score
            if ($money >= 5000)      $mScore = 5;
            elseif ($money >= 2000)  $mScore = 4;
            elseif ($money >= 1000)  $mScore = 3;
            elseif ($money >= 500)   $mScore = 2;
            elseif ($money > 0)      $mScore = 1;
            else                     $mScore = 0;

            // Segment
            if ($freq === 0) {
                $seg = 'no_orders';
            } else {
                $avg = ($rScore + $fScore + $mScore) / 3;
                if ($avg >= 4.5)                        $seg = 'champions';
                elseif ($rScore >= 4 && $freq === 1)    $seg = 'new';
                elseif ($avg >= 3.0)                    $seg = 'loyal';
                elseif ($days !== null && $days > 60 && $freq >= 2) $seg = 'at_risk';
                elseif ($days !== null && $days > 90)   $seg = 'lost';
                else                                    $seg = 'potential';
            }

            $r['r_score'] = $rScore;
            $r['f_score'] = $fScore;
            $r['m_score'] = $mScore;
            $r['segment'] = $seg;
            $r['days']    = $days;

            $allCustomers[] = $r;
            $stats[$seg]['count']++;
            $stats[$seg]['revenue'] += $money;

            // At-risk table: had orders + hasn't bought in 30+ days
            if ($freq > 0 && $days !== null && $days >= 30) {
                $atRiskCustomers[] = $r;
            }
        }

        usort($atRiskCustomers, fn($a, $b) => ($b['days'] ?? 0) <=> ($a['days'] ?? 0));
        $atRiskCustomers = array_slice($atRiskCustomers, 0, 100);

        // Build segment rows for the view
        $rfmSegments = [];
        foreach ($stats as $key => $s) {
            if ($s['count'] === 0 && $key === 'no_orders') {
                continue; // hide empty no_orders row only
            }
            $rfmSegments[] = [
                'key'       => $key,
                'segment'   => $s['label'],
                'count'     => $s['count'],
                'revenue'   => $s['revenue'],
                'color'     => $s['color'],
                'desc'      => $s['desc'],
                'avg_check' => $s['count'] > 0 ? round($s['revenue'] / $s['count'], 0) : 0,
            ];
        }

        // LTV tiers (real counts)
        $ltvSegments = [
            ['name' => 'VIP',     'min' => 5000, 'max' => null, 'count' => 0, 'color' => '#7c3aed', 'desc' => 'Высокая ценность, особый подход'],
            ['name' => 'Высокий', 'min' => 2000, 'max' => 4999, 'count' => 0, 'color' => '#10b981', 'desc' => 'Активные покупатели с хорошим LTV'],
            ['name' => 'Средний', 'min' => 500,  'max' => 1999, 'count' => 0, 'color' => '#3b82f6', 'desc' => 'Стабильные клиенты с потенциалом роста'],
            ['name' => 'Низкий',  'min' => 0,    'max' => 499,  'count' => 0, 'color' => '#6b7280', 'desc' => 'Требуют развития и удержания'],
        ];
        foreach ($allCustomers as $c) {
            $m = (float)$c['monetary'];
            if ($m >= 5000)     $ltvSegments[0]['count']++;
            elseif ($m >= 2000) $ltvSegments[1]['count']++;
            elseif ($m >= 500)  $ltvSegments[2]['count']++;
            else                $ltvSegments[3]['count']++;
        }

        $totalRevenue = array_sum(array_column($allCustomers, 'monetary'));

        return $this->render('rfm', [
            'rfmSegments'     => $rfmSegments,
            'ltvSegments'     => $ltvSegments,
            'atRiskCustomers' => $atRiskCustomers,
            'allCustomers'    => $allCustomers,
            'totalCustomers'  => count($allCustomers),
            'totalRevenue'    => $totalRevenue,
        ]);
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
            SELECT DATE(FROM_UNIXTIME(created_at)) as date, COUNT(*) as count, SUM(total_amount) as revenue
            FROM `order`
            WHERE DATE(FROM_UNIXTIME(created_at)) BETWEEN :from AND :to
            GROUP BY DATE(FROM_UNIXTIME(created_at))
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
            WHERE DATE(FROM_UNIXTIME(created_at)) BETWEEN :from AND :to
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
            AND DATE(FROM_UNIXTIME(created_at)) BETWEEN :from AND :to
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
                DATE(FROM_UNIXTIME(created_at)) as date,
                SUM(CASE WHEN event_type = 'page_view' THEN 1 ELSE 0 END) as page_views,
                SUM(CASE WHEN event_type = 'product_view' THEN 1 ELSE 0 END) as product_views,
                SUM(CASE WHEN event_type = 'add_to_cart' THEN 1 ELSE 0 END) as add_to_cart,
                SUM(CASE WHEN event_type = 'order_created' THEN 1 ELSE 0 END) as orders
            FROM analytics_event
            WHERE DATE(FROM_UNIXTIME(created_at)) BETWEEN :from AND :to
            GROUP BY DATE(FROM_UNIXTIME(created_at))
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
                DATE(FROM_UNIXTIME(created_at)) as date,
                COUNT(*) as orders_count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as avg_order
            FROM `order`
            WHERE DATE(FROM_UNIXTIME(created_at)) BETWEEN :from AND :to
            GROUP BY DATE(FROM_UNIXTIME(created_at))
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
            WHERE DATE(FROM_UNIXTIME(created_at)) BETWEEN :from AND :to
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
