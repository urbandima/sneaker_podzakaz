<?php

/**
 * DashboardController — Главная панель администратора
 * 
 * НАЗНАЧЕНИЕ:
 * Главная страница админ-панели с виджетами статистики,
 * профилем пользователя и системными настройками.
 * 
 * ФУНКЦИИ:
 * - Главная страница с виджетами и статистикой (index)
 * - Профиль пользователя и смена пароля (profile)
 * - Системные настройки компании (settings)
 * - Выход из системы (logout)
 * - Очистка кэша (clear-cache)
 * 
 * СВЯЗИ:
 * - CompanySettings (модель настроек компании)
 * - OrderStatus (модель статусов заказа)
 * - Tariff (модель тарифов)
 * - ChangePasswordForm (форма смены пароля)
 * - Order, Product, User (для статистики)
 * 
 * ДОСТУП:
 * - Все авторизованные пользователи
 * - Настройки — только администраторы
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use app\backend\modules\admin\models\CompanySettings;
use app\backend\modules\checkout\models\OrderStatus;
use app\backend\modules\account\models\ChangePasswordForm;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\admin\models\User;

class DashboardController extends BaseAdminController
{
    /**
     * Главная страница админ-панели с виджетами и статистикой
     */
    public function actionIndex()
    {
        $user = $this->getCurrentUser();
        $demoMode = false;
        
        // Пробуем загрузить реальные данные
        try {
            // Статистика заказов
            $orderStats = $this->getOrderStats($user);
            
            // Статистика товаров
            $productStats = $this->getProductStats();
            
            // Статистика пользователей
            $userStats = $this->getUserStats();
            
            // Топ товары
            $topProducts = $this->getTopProducts();
            
            // Активные логисты
            $activeLogists = $this->getActiveLogists();
            
            // Данные для графика
            $chartData = $this->getChartData($user);
            
            // Настройки компании
            $companySettings = CompanySettings::getSettings();
            
        } catch (\Exception $e) {
            // Только при ошибке БД показываем демо
            $demoMode = true;
            $orderStats = [
                'total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0,
                'today' => 0, 'thisMonth' => 0, 'totalAmount' => 0,
            ];
            $productStats = ['total' => 0, 'active' => 0, 'inStock' => 0, 'outOfStock' => 0];
            $userStats = ['total' => 0, 'active' => 0, 'admins' => 0, 'managers' => 0, 'logists' => 0];
            $topProducts = [];
            $activeLogists = [];
            $chartData = [];
            $companySettings = ['name' => 'СНИКЕРХЭД', 'email' => '', 'phone' => ''];
            Yii::warning('Dashboard demo mode: ' . $e->getMessage(), 'admin');
        }
        
        return $this->render('index', [
            'user' => $user,
            'orderStats' => $orderStats,
            'productStats' => $productStats,
            'userStats' => $userStats,
            'topProducts' => $topProducts,
            'activeLogists' => $activeLogists,
            'chartData' => $chartData,
            'companySettings' => $companySettings,
            'demoMode' => $demoMode,
            'recentOrders' => $this->getRecentOrders($user),
        ]);
    }
    
    /**
     * Получить последние заказы для дашборда
     */
    private function getRecentOrders($user, int $limit = 10): array
    {
        $query = Order::find()
            ->with(['orderItems', 'creator'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit);
        
        // Логист видит только свои заказы
        if ($user->isLogist()) {
            $query->andWhere(['assigned_logist' => $user->id]);
        }
        
        return $query->all();
    }
    
    /**
     * Получение статистики заказов
     */
    private function getOrderStats($user)
    {
        $baseQuery = Order::find();
        
        if ($user->isLogist()) {
            $baseQuery->andWhere(['assigned_logist' => $user->id]);
        }
        
        return [
            'total' => (int)(clone $baseQuery)->count(),
            'today' => (int)(clone $baseQuery)->andWhere(['>=', 'created_at', strtotime('today')])->count(),
            'thisMonth' => (int)(clone $baseQuery)->andWhere(['>=', 'created_at', strtotime('first day of this month')])->count(),
            'totalAmount' => (float)((clone $baseQuery)->sum('total_amount') ?: 0),
            'pending' => (int)(clone $baseQuery)->andWhere(['status' => 'created'])->count(),
            'processing' => (int)(clone $baseQuery)->andWhere(['status' => ['confirmed', 'paid', 'ordered']])->count(),
            'completed' => (int)(clone $baseQuery)->andWhere(['status' => ['delivered', 'issued']])->count(),
        ];
    }
    
    /**
     * Получение статистики товаров
     */
    private function getProductStats()
    {
        return [
            'total' => (int)Product::find()->count(),
            'active' => (int)Product::find()->where(['is_active' => true])->count(),
            'inStock' => (int)Product::find()->where(['!=', 'stock_status', 'out_of_stock'])->count(),
            'outOfStock' => (int)Product::find()->where(['stock_status' => 'out_of_stock'])->count(),
        ];
    }
    
    /**
     * Получение статистики пользователей
     */
    private function getUserStats()
    {
        return [
            'total' => (int)User::find()->count(),
            'active' => (int)User::find()->where(['status' => 'active'])->count(),
            'admins' => (int)User::find()->where(['role' => 'admin'])->count(),
            'logists' => (int)User::find()->where(['role' => 'logist'])->count(),
            'managers' => (int)User::find()->where(['role' => 'manager'])->count(),
        ];
    }
    
    /**
     * Получение топ товаров
     */
    private function getTopProducts()
    {
        // Топ товаров по количеству заказов
        $sql = "
            SELECT oi.product_name, SUM(oi.quantity) as total_quantity, 
                   COUNT(DISTINCT oi.order_id) as order_count, AVG(oi.price) as avg_price
            FROM order_item oi
            INNER JOIN `order` o ON oi.order_id = o.id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY oi.product_name
            ORDER BY order_count DESC, total_quantity DESC
            LIMIT 5
        ";
        
        return Yii::$app->db->createCommand($sql)->queryAll();
    }
    
    /**
     * Получение активных логистов
     */
    private function getActiveLogists()
    {
        return User::find()
            ->where(['role' => 'logist', 'status' => 'active'])
            ->orderBy(['username' => SORT_ASC])
            ->all();
    }

    /**
     * Получение данных для графика заказов
     */
    private function getChartData($user)
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime("-$i days"));
            
            $query = Order::find()
                ->where(['>=', 'created_at', strtotime($date)])
                ->andWhere(['<', 'created_at', strtotime($date . ' +1 day')]);
                
            if ($user->isLogist()) {
                $query->andWhere(['assigned_logist' => $user->id]);
            }
            
            $count = $query->count();
            $amount = $query->sum('total_amount') ?: 0;
            
            $data[] = [
                'date' => $date,
                'day' => $dayName,
                'orders' => $count,
                'amount' => (float)$amount,
            ];
        }
        
        return $data;
    }

    /**
     * Профиль пользователя и смена пароля
     */
    public function actionProfile()
    {
        $user = $this->getCurrentUser();
        $model = new ChangePasswordForm();

        if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
            $this->flashSuccess('Пароль успешно изменен');
            return $this->refresh();
        }

        return $this->render('profile', [
            'model' => $model,
            'user' => $user,
        ]);
    }

    /**
     * Настройки системы
     * Доступ только для администраторов
     */
    public function actionSettings()
    {
        // Только админ может изменять настройки
        if (!$this->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $settings = CompanySettings::find()->orderBy(['id' => SORT_ASC])->one();
        if (!$settings) {
            $settings = new CompanySettings();
        }

        $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            
            // Сохранение реквизитов компании
            if ($settings->load($post) && $settings->validate()) {
                $settings->updated_at = time();
                $settings->save(false);
            }

            // Обновление статусов
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Обновляем существующие статусы
                $postedStatuses = $post['statuses'] ?? [];
                foreach ($postedStatuses as $key => $data) {
                    $model = OrderStatus::findOne(['key' => $key]);
                    if ($model) {
                        $model->label = trim($data['label'] ?? $model->label);
                        $model->sort = (int)($data['sort'] ?? $model->sort);
                        $model->logist_available = !empty($data['logist_available']);
                        $model->is_active = !empty($data['is_active']);
                        $model->save(false);
                    }
                }

                // Добавление нового статуса
                $newStatus = $post['new_status'] ?? [];
                if (!empty($newStatus['key']) && !empty($newStatus['label'])) {
                    if (!OrderStatus::find()->where(['key' => $newStatus['key']])->exists()) {
                        $statusModel = new OrderStatus();
                        $statusModel->key = trim($newStatus['key']);
                        $statusModel->label = trim($newStatus['label']);
                        $statusModel->sort = (int)($newStatus['sort'] ?? 999);
                        $statusModel->logist_available = !empty($newStatus['logist_available']);
                        $statusModel->is_active = 1; // Новые статусы по умолчанию активны
                        $statusModel->save(false);
                    }
                }

                $transaction->commit();
                $this->flashSuccess('Настройки сохранены');
                
                // Обновляем данные для рендера
                $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->all();
                
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error('Ошибка при сохранении настроек: ' . $e->getMessage(), 'admin');
                $this->flashError('Ошибка при сохранении: ' . $e->getMessage());
            }
        }

        return $this->render('settings', [
            'settings' => $settings,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Выход из системы
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
