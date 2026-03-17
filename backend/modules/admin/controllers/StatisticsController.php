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
            $statusStats[$label] = $count;
        }

        try {
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
        } catch (\Exception $e) {
            // Демо-данные при отсутствии БД
            $managerStats = [];
            $logistStats = [];
        }

        // Общая статистика
        $totalOrders = Order::find()->count();
        $totalAmount = Order::find()->sum('total_amount');
        $pendingPayment = Order::find()->where(['status' => 'created'])->count();
        $completedOrders = Order::find()->where(['status' => 'issued'])->count();

        return $this->render('index', [
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
