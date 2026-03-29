<?php

/**
 * StatisticsController — Статистика и аналитика заказов
 * 
 * НАЗНАЧЕНИЕ:
 * Сбор и отображение статистики по заказам, менеджерам, логистам:
 * распределение по статусам, эффективность сотрудников.
 * 
 * ФУНКЦИИ:
 * - Общая статистика по заказам (index)
 * - Статистика по статусам заказов
 * - Статистика по менеджерам (кто создал больше заказов)
 * - Статистика по логистам (кто обработал больше заказов)
 * - Общая сумма заказов
 * 
 * СВЯЗИ:
 * - Order (модель заказа)
 * - User (модель пользователя)
 * 
 * ДОСТУП:
 * - Все авторизованные пользователи админки
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\admin\models\User;

class StatisticsController extends BaseAdminController
{
    /**
     * Главная страница статистики
     */
    public function actionIndex()
    {
        // Статистика по статусам
        $statusStats = [];
        foreach (Yii::$app->settings->getStatuses() as $key => $label) {
            $count = Order::find()->where(['status' => $key])->count();
            $statusStats[$key] = ['label' => $label, 'count' => (int)$count];
        }

        try {
            $managerStats = User::find()
                ->where(['role' => User::ROLE_MANAGER])
                ->with(['createdOrders'])
                ->all();

            $logistStats = User::find()
                ->where(['role' => User::ROLE_LOGIST])
                ->with(['assignedOrders'])
                ->all();
        } catch (\Exception $e) {
            $managerStats = [];
            $logistStats = [];
        }

        // KPI
        $totalOrders = (int)Order::find()->count();
        $totalAmount = (float)(Order::find()->sum('total_amount') ?: 0);
        $pendingPayment = (int)Order::find()->where(['status' => 'created'])->count();
        $completedOrders = (int)Order::find()->where(['status' => 'issued'])->count();
        $avgOrderAmount = $totalOrders > 0 ? round($totalAmount / $totalOrders, 2) : 0;

        // Данные для графика продаж за 30 дней
        $salesChart = $this->getSalesChartData(30);

        // Топ-5 товаров
        $topProducts = $this->getTopProductsData();

        return $this->render('index', [
            'statusStats' => $statusStats,
            'managerStats' => $managerStats,
            'logistStats' => $logistStats,
            'totalOrders' => $totalOrders,
            'totalAmount' => $totalAmount,
            'pendingPayment' => $pendingPayment,
            'completedOrders' => $completedOrders,
            'avgOrderAmount' => $avgOrderAmount,
            'salesChart' => $salesChart,
            'topProducts' => $topProducts,
        ]);
    }

    /**
     * Данные для графика продаж за N дней
     */
    private function getSalesChartData(int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $startTs = strtotime($date);
            $endTs = strtotime($date . ' +1 day');

            $count = (int)Order::find()
                ->where(['>=', 'created_at', $startTs])
                ->andWhere(['<', 'created_at', $endTs])
                ->count();

            $amount = (float)(Order::find()
                ->where(['>=', 'created_at', $startTs])
                ->andWhere(['<', 'created_at', $endTs])
                ->sum('total_amount') ?: 0);

            $data[] = [
                'date' => $date,
                'label' => date('d.m', $startTs),
                'orders' => $count,
                'amount' => $amount,
            ];
        }
        return $data;
    }

    /**
     * Топ товаров по количеству заказов
     */
    private function getTopProductsData(): array
    {
        try {
            $sql = "
                SELECT oi.product_name, 
                       COUNT(DISTINCT oi.order_id) as order_count, 
                       SUM(oi.quantity) as total_quantity,
                       AVG(oi.price) as avg_price,
                       SUM(oi.quantity * oi.price) as total_revenue
                FROM order_item oi
                INNER JOIN `order` o ON oi.order_id = o.id
                GROUP BY oi.product_name
                ORDER BY total_revenue DESC
                LIMIT 10
            ";
            return Yii::$app->db->createCommand($sql)->queryAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * API-экшн для экспорта аналитики в CSV
     */
    public function actionExportCsv()
    {
        $salesChart = $this->getSalesChartData(90);

        $csv = "Дата;Заказы;Выручка (BYN)\n";
        foreach ($salesChart as $row) {
            $csv .= $row['date'] . ';' . $row['orders'] . ';' . number_format($row['amount'], 2, '.', '') . "\n";
        }

        Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="analytics_' . date('Y-m-d') . '.csv"');
        return "\xEF\xBB\xBF" . $csv;
    }
}
