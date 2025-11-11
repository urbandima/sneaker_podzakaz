<?php

namespace app\controllers\admin;

use Yii;
use app\models\Order;
use app\models\User;

/**
 * StatisticsController - Статистика и аналитика
 * 
 * Методы:
 * - index() - Общая статистика по заказам, менеджерам, логистам
 */
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
            $statusStats[$label] = $count;
        }

        // Статистика по менеджерам
        $managerStats = User::find()
            ->where(['role' => User::ROLE_MANAGER])
            ->with(['createdOrders'])
            ->all();

        // Статистика по логистам
        $logistStats = User::find()
            ->where(['role' => User::ROLE_LOGIST])
            ->with(['assignedOrders'])
            ->all();

        // Общая статистика
        $totalOrders = Order::find()->count();
        $totalAmount = Order::find()->sum('total_amount');
        $pendingPayment = Order::find()->where(['status' => 'created'])->count();
        $completedOrders = Order::find()->where(['status' => 'issued'])->count();

        return $this->render('/admin/statistics', [
            'statusStats' => $statusStats,
            'managerStats' => $managerStats,
            'logistStats' => $logistStats,
            'totalOrders' => $totalOrders,
            'totalAmount' => $totalAmount,
            'pendingPayment' => $pendingPayment,
            'completedOrders' => $completedOrders,
        ]);
    }
}
