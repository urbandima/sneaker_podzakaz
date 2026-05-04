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
use app\backend\shared\services\RevenueService;
use app\infrastructure\plugins\analytics\LiveDunePlugin;

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
            'period' => $rawPeriod, // Z80: pass raw period string for active button comparison
            'periodDays' => $period,
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
     * Instagram-метрики через LiveDune API
     */
    public function actionInstagram()
    {
        $plugin = new LiveDunePlugin();
        $error = null;

        $thisWeekFrom = date('Y-m-d', strtotime('monday this week'));
        $thisWeekTo   = date('Y-m-d');
        $prevWeekFrom = date('Y-m-d', strtotime('monday last week'));
        $prevWeekTo   = date('Y-m-d', strtotime('sunday last week'));

        $thisHistory = $plugin->getHistory($thisWeekFrom, $thisWeekTo);
        $prevHistory = $plugin->getHistory($prevWeekFrom, $prevWeekTo);
        $posts       = $plugin->getPosts($prevWeekFrom, $thisWeekTo);

        if ($thisHistory === null) {
            $token = $plugin->getToken();
            if (empty($token)) {
                $error = 'Токен LiveDune не настроен. Добавьте `livedune_api_token` в разделе настроек интеграций.';
            } else {
                $error = 'Не удалось получить данные от LiveDune API. Показаны кешированные данные (если есть).';
                $thisHistory = Yii::$app->cache->get("livedune_history_{$thisWeekFrom}_{$thisWeekTo}") ?: [];
                $prevHistory = Yii::$app->cache->get("livedune_history_{$prevWeekFrom}_{$prevWeekTo}") ?: [];
                $posts = Yii::$app->cache->get("livedune_posts_{$prevWeekFrom}_{$thisWeekTo}") ?: [];
            }
        }

        $thisMetrics = $thisHistory ? $plugin->aggregateMetrics($thisHistory) : null;
        $prevMetrics = $prevHistory ? $plugin->aggregateMetrics($prevHistory) : null;

        $topPosts = [];
        if (is_array($posts)) {
            usort($posts, fn($a, $b) => (int)($b['reach'] ?? 0) <=> (int)($a['reach'] ?? 0));
            $topPosts = array_slice($posts, 0, 3);
        }

        return $this->render('instagram', [
            'error'       => $error,
            'thisMetrics' => $thisMetrics,
            'prevMetrics' => $prevMetrics,
            'topPosts'    => $topPosts,
            'thisWeekFrom' => $thisWeekFrom,
            'thisWeekTo'   => $thisWeekTo,
            'prevWeekFrom' => $prevWeekFrom,
            'prevWeekTo'   => $prevWeekTo,
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
     * Uses RevenueService::EXCLUDED_STATUSES for consistency with dashboard/P&L figures.
     */
    protected function getRevenueStats($dateFrom, $dateTo)
    {
        $excl = implode("','", RevenueService::EXCLUDED_STATUSES);
        return Yii::$app->db->createCommand("
            SELECT
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COUNT(*) as total_orders,
                COALESCE(AVG(total_amount), 0) as avg_order_value,
                COALESCE(MAX(total_amount), 0) as max_order,
                COALESCE(MIN(total_amount), 0) as min_order
            FROM `order`
            WHERE DATE(FROM_UNIXTIME(created_at)) BETWEEN :from AND :to
              AND status NOT IN ('{$excl}')
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
     * Z78: unified query — only rows with price > 0, same as DashboardController::getTopProducts()
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
            WHERE DATE(FROM_UNIXTIME(o.created_at)) BETWEEN :from AND :to
              AND oi.price IS NOT NULL AND oi.price > 0
            GROUP BY oi.product_name
            ORDER BY orders_count DESC, total_qty DESC
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

    // ─── AmoCRM analytics ─────────────────────────────────────────────────────

    /**
     * AmoCRM funnel analytics — pipeline 4453963.
     * Shows lead counts per stage, conversion rates, top loss reasons.
     * If AMOCRM_LONG_TOKEN returns 401, the page still loads with a blocked-status banner.
     */
    public function actionAmocrm()
    {
        $amoCrm        = Yii::$app->amocrm;
        $pipelineId    = 4453963;
        $tokenStatus   = 'ok';
        $leads         = [];
        $statuses      = [];
        $lossReasons   = [];
        $stageMetrics  = [];
        $totalLeads    = 0;
        $wonLeads      = 0;
        $lostLeads     = 0;

        try {
            $account = $amoCrm->getAccount();
            if ($account === null) {
                $tokenStatus = 'error';
            } else {
                // Fetch statuses for the pipeline
                $rawStatuses = $amoCrm->getPipelineStatuses($pipelineId);
                foreach ($rawStatuses as $s) {
                    $statuses[$s['id']] = $s['name'];
                }

                // Fetch up to 250 leads from the production pipeline
                $raw = $amoCrm->getLeads([
                    'filter[pipeline_id]' => $pipelineId,
                    'limit'               => 250,
                ]);
                $leads = $raw['_embedded']['leads'] ?? [];
                $totalLeads = count($leads);

                // Aggregate per status
                $statusCounts = [];
                foreach ($leads as $lead) {
                    $sid  = (int)$lead['status_id'];
                    $name = $statuses[$sid] ?? 'Неизвестен';
                    $statusCounts[$name] = ($statusCounts[$name] ?? 0) + 1;

                    if ($lead['closed_at'] ?? false) {
                        if ($lead['is_deleted'] ?? false) {
                            $lostLeads++;
                        } else {
                            $wonLeads++;
                        }
                    }
                    // Collect loss reason from custom fields (field_id varies — use value text)
                    foreach ($lead['custom_fields_values'] ?? [] as $cf) {
                        if (mb_stripos($cf['field_name'] ?? '', 'причина') !== false ||
                            mb_stripos($cf['field_name'] ?? '', 'reason')  !== false) {
                            $val = $cf['values'][0]['value'] ?? null;
                            if ($val) {
                                $lossReasons[$val] = ($lossReasons[$val] ?? 0) + 1;
                            }
                        }
                    }
                }

                // Build stage metrics ordered by status list
                foreach ($statuses as $sId => $sName) {
                    $cnt = $statusCounts[$sName] ?? 0;
                    $stageMetrics[] = ['name' => $sName, 'count' => $cnt];
                }

                arsort($lossReasons);
                $lossReasons = array_slice($lossReasons, 0, 10, true);
            }
        } catch (\Throwable $e) {
            $tokenStatus = 'error';
            Yii::warning('[analytics/amocrm] ' . $e->getMessage(), 'amocrm');
        }

        return $this->render('amocrm', [
            'tokenStatus'  => $tokenStatus,
            'pipelineId'   => $pipelineId,
            'stageMetrics' => $stageMetrics,
            'lossReasons'  => $lossReasons,
            'totalLeads'   => $totalLeads,
            'wonLeads'     => $wonLeads,
            'lostLeads'    => $lostLeads,
            'statuses'     => $statuses,
        ]);
    }

    // ─── Moysklad analytics ───────────────────────────────────────────────────

    /**
     * МойСклад analytics — revenue, top products, order status counts, debt.
     * Data sourced from local DB (order table) + MS API for payments.
     */
    public function actionMoysklad()
    {
        $thisMonthFrom = date('Y-m-01');
        $thisMonthTo   = date('Y-m-d');
        $prevMonthFrom = date('Y-m-01', strtotime('first day of last month'));
        $prevMonthTo   = date('Y-m-t', strtotime('first day of last month'));

        // Revenue this month vs previous (from local DB, synced from MS)
        $thisRevenue = $this->getRevenueStats($thisMonthFrom, $thisMonthTo);
        $prevRevenue = $this->getRevenueStats($prevMonthFrom, $prevMonthTo);

        // Top 10 products by revenue this month
        $topProducts = $this->getTopSellingProducts($thisMonthFrom, $thisMonthTo, 10);

        // Orders by status (all time — snapshot of current state)
        $ordersByStatus = Yii::$app->db->createCommand("
            SELECT status, COUNT(*) as cnt
            FROM `order`
            GROUP BY status
            ORDER BY cnt DESC
        ")->queryAll();

        // Last 20 orders from MS (local DB, sorted by created_at desc)
        $recentOrders = Yii::$app->db->createCommand("
            SELECT id, order_number, status, total_amount, client_name, moysklad_id,
                   FROM_UNIXTIME(created_at) as created_at
            FROM `order`
            ORDER BY created_at DESC
            LIMIT 20
        ")->queryAll();

        // Payments from MS API (incoming) — best-effort
        $msPayments  = [];
        $msPayStatus = 'ok';
        try {
            $payData    = Yii::$app->moyskladClient->getPayments(['limit' => 20]);
            $msPayments = $payData['rows'];
        } catch (\Throwable $e) {
            $msPayStatus = 'error';
            Yii::warning('[analytics/moysklad] payments: ' . $e->getMessage(), 'moysklad');
        }

        // Receivables (дебиторка) — sum of invoiceout where sumShipped < sum
        $debtData    = [];
        $msDebtTotal = 0;
        try {
            $inv = Yii::$app->moyskladClient->getInvoices(['limit' => 100, 'filter' => 'paymentPlannedMoment>=' . $prevMonthFrom]);
            foreach ($inv['rows'] as $row) {
                $sum      = ($row['sum'] ?? 0) / 100;
                $paidSum  = ($row['payedSum'] ?? 0) / 100;
                $debt     = $sum - $paidSum;
                if ($debt > 0) {
                    $msDebtTotal += $debt;
                    $debtData[]   = [
                        'name'    => $row['name'] ?? '',
                        'agent'   => $row['agent']['name'] ?? '',
                        'sum'     => $sum,
                        'paid'    => $paidSum,
                        'debt'    => $debt,
                        'moment'  => $row['moment'] ?? '',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Yii::warning('[analytics/moysklad] invoices: ' . $e->getMessage(), 'moysklad');
        }

        return $this->render('moysklad', [
            'thisRevenue'    => $thisRevenue,
            'prevRevenue'    => $prevRevenue,
            'thisMonthFrom'  => $thisMonthFrom,
            'prevMonthFrom'  => $prevMonthFrom,
            'topProducts'    => $topProducts,
            'ordersByStatus' => $ordersByStatus,
            'recentOrders'   => $recentOrders,
            'msPayments'     => $msPayments,
            'msPayStatus'    => $msPayStatus,
            'debtData'       => $debtData,
            'msDebtTotal'    => $msDebtTotal,
        ]);
    }

    /**
     * Summary analytics — end-to-end funnel AmoCRM→Orders, margin estimate, repeat rate.
     */
    public function actionSummary()
    {
        $thisMonthFrom = date('Y-m-01');
        $thisMonthTo   = date('Y-m-d');

        // Funnel from local analytics events
        $funnel = $this->getConversionFunnel($thisMonthFrom, $thisMonthTo);

        // AmoCRM leads count this month (best-effort)
        $amoLeads    = 0;
        $amoStatus   = 'ok';
        try {
            $raw      = Yii::$app->amocrm->getLeads([
                'filter[pipeline_id]' => 4453963,
                'limit'               => 1,
            ]);
            $amoLeads = $raw['_embedded']['leads'][0] ? ($raw['_embedded']['leads'][0]['id'] ? 1 : 0) : 0;
            // Use meta total count if available
            $amoLeads = $raw['_embedded']['meta']['total'] ?? count($raw['_embedded']['leads'] ?? []);
        } catch (\Throwable $e) {
            $amoStatus = 'error';
        }

        // Orders this month
        $revenue    = $this->getRevenueStats($thisMonthFrom, $thisMonthTo);
        $orderCount = (int)($revenue['total_orders'] ?? 0);
        $totalRev   = (float)($revenue['total_revenue'] ?? 0);
        $marginEst  = round($totalRev * 0.30, 2); // 30% базовая маржа

        // Repeat customers rate
        $repeatData = Yii::$app->db->createCommand("
            SELECT
                COUNT(*) AS total_customers,
                SUM(CASE WHEN order_count > 1 THEN 1 ELSE 0 END) AS repeat_customers
            FROM (
                SELECT customer_id, COUNT(*) AS order_count
                FROM `order`
                WHERE customer_id IS NOT NULL
                GROUP BY customer_id
            ) t
        ")->queryOne();
        $totalCust  = (int)($repeatData['total_customers'] ?? 0);
        $repeatCust = (int)($repeatData['repeat_customers'] ?? 0);
        $repeatRate = $totalCust > 0 ? round($repeatCust / $totalCust * 100, 1) : 0;

        // Margin by period (last 6 months)
        $marginByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $mFrom = date('Y-m-01', strtotime("-{$i} months"));
            $mTo   = date('Y-m-t',  strtotime("-{$i} months"));
            $r     = $this->getRevenueStats($mFrom, $mTo);
            $rev   = (float)($r['total_revenue'] ?? 0);
            $marginByMonth[] = [
                'month'   => date('M Y', strtotime($mFrom)),
                'revenue' => $rev,
                'margin'  => round($rev * 0.30, 2),
            ];
        }

        return $this->render('summary', [
            'funnel'        => $funnel,
            'amoLeads'      => $amoLeads,
            'amoStatus'     => $amoStatus,
            'orderCount'    => $orderCount,
            'totalRevenue'  => $totalRev,
            'marginEst'     => $marginEst,
            'repeatRate'    => $repeatRate,
            'totalCust'     => $totalCust,
            'repeatCust'    => $repeatCust,
            'marginByMonth' => $marginByMonth,
            'thisMonthFrom' => $thisMonthFrom,
        ]);
    }

    // ─── CSV Exports ──────────────────────────────────────────────────────────

    /**
     * GET /admin/analytics/export-orders — CSV of all orders with key fields.
     */
    public function actionExportOrders()
    {
        $rows = Yii::$app->db->createCommand("
            SELECT
                o.id              AS `ID`,
                o.order_number    AS `Номер`,
                o.status          AS `Статус`,
                o.total_amount    AS `Сумма`,
                o.client_name     AS `Клиент`,
                o.client_phone    AS `Телефон`,
                o.client_email    AS `Email`,
                o.delivery_address AS `Адрес`,
                o.moysklad_id     AS `ID_МойСклад`,
                FROM_UNIXTIME(o.created_at) AS `Создан`
            FROM `order` o
            ORDER BY o.created_at DESC
        ")->queryAll();

        return $this->exportCsv($rows, 'orders_moysklad_' . date('Y-m-d'));
    }

    /**
     * GET /admin/analytics/export-customers — CSV of customers + purchase history.
     */
    public function actionExportCustomers()
    {
        $rows = Yii::$app->db->createCommand("
            SELECT
                c.id               AS `ID`,
                TRIM(CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,''))) AS `Имя`,
                c.email            AS `Email`,
                c.phone            AS `Телефон`,
                COUNT(DISTINCT o.id)              AS `Заказов`,
                COALESCE(SUM(o.total_amount), 0)  AS `Выручка`,
                COALESCE(AVG(o.total_amount), 0)  AS `Средний_чек`,
                MAX(FROM_UNIXTIME(o.created_at))  AS `Последний_заказ`
            FROM customer c
            LEFT JOIN `order` o ON o.customer_id = c.id
            GROUP BY c.id
            ORDER BY `Выручка` DESC
        ")->queryAll();

        return $this->exportCsv($rows, 'customers_' . date('Y-m-d'));
    }

    /**
     * GET /admin/analytics/export-deals — CSV of AmoCRM leads from pipeline 4453963.
     */
    public function actionExportDeals()
    {
        $rows = [];
        try {
            $statuses = [];
            foreach (Yii::$app->amocrm->getPipelineStatuses(4453963) as $s) {
                $statuses[$s['id']] = $s['name'];
            }
            $raw   = Yii::$app->amocrm->getLeads(['filter[pipeline_id]' => 4453963, 'limit' => 250]);
            $leads = $raw['_embedded']['leads'] ?? [];
            foreach ($leads as $lead) {
                $rows[] = [
                    'ID'       => $lead['id'],
                    'Название' => $lead['name'] ?? '',
                    'Сумма'    => $lead['price'] ?? 0,
                    'Статус'   => $statuses[$lead['status_id']] ?? $lead['status_id'],
                    'Создан'   => $lead['created_at'] ? date('Y-m-d H:i', $lead['created_at']) : '',
                    'Закрыт'   => $lead['closed_at']  ? date('Y-m-d H:i', $lead['closed_at'])  : '',
                    'Теги'     => implode(', ', array_column($lead['_embedded']['tags'] ?? [], 'name')),
                ];
            }
        } catch (\Throwable $e) {
            Yii::warning('[export-deals] ' . $e->getMessage(), 'amocrm');
            // Return empty CSV with error note
            $rows = [['Ошибка' => 'AmoCRM недоступен: ' . $e->getMessage()]];
        }

        return $this->exportCsv($rows, 'amocrm_deals_' . date('Y-m-d'));
    }

    /**
     * GET /admin/analytics/export-products — CSV of products + stock snapshot.
     */
    public function actionExportProducts()
    {
        $rows = Yii::$app->db->createCommand("
            SELECT
                p.id       AS `ID`,
                p.name     AS `Название`,
                p.sku      AS `Артикул`,
                p.price    AS `Цена`,
                p.status   AS `Статус`,
                p.ms_code  AS `Код_МС`
            FROM product p
            ORDER BY p.name
        ")->queryAll();

        return $this->exportCsv($rows, 'products_stock_' . date('Y-m-d'));
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
