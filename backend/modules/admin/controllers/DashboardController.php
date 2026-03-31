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
use app\backend\modules\admin\models\Tariff;
use app\backend\modules\returns\models\ReturnRequest;

class DashboardController extends BaseAdminController
{
    /**
     * Главная страница админ-панели с виджетами и статистикой
     */
    public function actionIndex()
    {
        $user = $this->getCurrentUser();
        $demoMode = false;
        
        // ВРЕМЕННО: Для временной авторизации всегда демо-режим
        if ($user instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            $demoMode = true;
            $orderStats = [
                'total' => 156,
                'pending' => 23,
                'processing' => 45,
                'completed' => 88,
                'today' => 12,
                'thisMonth' => 45,
                'totalAmount' => 15420.50,
            ];
            $productStats = [
                'total' => 1245,
                'active' => 1180,
                'inactive' => 65,
                'inStock' => 1120,
            ];
            $userStats = [
                'total' => 8,
                'active' => 8,
                'admins' => 2,
                'managers' => 4,
                'logists' => 2,
            ];
            $topProducts = [
                ['product_name' => 'Nike Air Max 90', 'order_count' => 45, 'total_quantity' => 67, 'avg_price' => 350.00],
                ['product_name' => 'Adidas Ultraboost 22', 'order_count' => 38, 'total_quantity' => 52, 'avg_price' => 280.00],
                ['product_name' => 'Jordan 1 Retro High', 'order_count' => 32, 'total_quantity' => 41, 'avg_price' => 420.00],
                ['product_name' => 'New Balance 550', 'order_count' => 28, 'total_quantity' => 35, 'avg_price' => 180.00],
                ['product_name' => 'Yeezy Boost 350', 'order_count' => 25, 'total_quantity' => 30, 'avg_price' => 520.00],
            ];
            $activeLogists = [];
            $chartData = [
                ['date' => '2026-03-09', 'day' => 'Mon', 'orders' => 12, 'amount' => 2100.00],
                ['date' => '2026-03-10', 'day' => 'Tue', 'orders' => 15, 'amount' => 2800.00],
                ['date' => '2026-03-11', 'day' => 'Wed', 'orders' => 18, 'amount' => 3200.00],
                ['date' => '2026-03-12', 'day' => 'Thu', 'orders' => 22, 'amount' => 3800.00],
                ['date' => '2026-03-13', 'day' => 'Fri', 'orders' => 25, 'amount' => 4500.00],
                ['date' => '2026-03-14', 'day' => 'Sat', 'orders' => 28, 'amount' => 5200.00],
                ['date' => '2026-03-15', 'day' => 'Sun', 'orders' => 20, 'amount' => 3500.00],
            ];
            $companySettings = [
                'name' => 'СНИКЕРХЭД',
                'email' => 'info@sneakerhead.by',
                'phone' => '+375 (29) 123-45-67',
            ];
            $recentOrders = [];
            $tariffs = [];
            $statusCounts = [];
            $overdueOrders = [];
            $openReturnsCount = 0;
        } else {
            // Демо-режим при отсутствии БД
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

                // Последние заказы (24 часа)
                $recentOrders = Order::find()
                    ->where(['>=', 'created_at', strtotime('-24 hours')])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->limit(8)
                    ->all();

                // Тарифы для калькулятора
                $tariffs = Tariff::find()->where(['is_active' => true])->orderBy(['sort' => SORT_ASC])->all();

                // Пайплайн статусов
                $statusCounts = $this->getPipelineStatusCounts();

                // Просроченные заказы (paid > 3 дней без перехода в ordered)
                $overdueThreshold = strtotime('-3 days');
                $overdueOrders = Order::find()
                    ->where(['status' => 'paid'])
                    ->andWhere(['<=', 'updated_at', $overdueThreshold])
                    ->limit(5)
                    ->all();

                // Открытые возвраты
                $openReturnsCount = (int)ReturnRequest::find()
                    ->where(['status' => [ReturnRequest::STATUS_PENDING, ReturnRequest::STATUS_PROCESSING]])
                    ->count();
            } catch (\Exception $e) {
                $demoMode = true;
                // Демо данные
                $orderStats = [
                    'total' => 156,
                    'pending' => 23,
                    'processing' => 45,
                    'completed' => 88,
                    'today' => 12,
                    'thisMonth' => 45,
                    'totalAmount' => 15420.50,
                ];
                $productStats = [
                    'total' => 1245,
                    'active' => 1180,
                    'inactive' => 65,
                    'inStock' => 1120,
                ];
                $userStats = [
                    'total' => 8,
                    'active' => 8,
                    'admins' => 2,
                    'managers' => 4,
                    'logists' => 2,
                ];
                $topProducts = [
                    ['product_name' => 'Nike Air Max 90', 'order_count' => 45, 'total_quantity' => 67, 'avg_price' => 350.00],
                    ['product_name' => 'Adidas Ultraboost 22', 'order_count' => 38, 'total_quantity' => 52, 'avg_price' => 280.00],
                    ['product_name' => 'Jordan 1 Retro High', 'order_count' => 32, 'total_quantity' => 41, 'avg_price' => 420.00],
                    ['product_name' => 'New Balance 550', 'order_count' => 28, 'total_quantity' => 35, 'avg_price' => 180.00],
                    ['product_name' => 'Yeezy Boost 350', 'order_count' => 25, 'total_quantity' => 30, 'avg_price' => 520.00],
                ];
                $activeLogists = [];
                $chartData = [
                    ['date' => '2026-03-09', 'day' => 'Mon', 'orders' => 12, 'amount' => 2100.00],
                    ['date' => '2026-03-10', 'day' => 'Tue', 'orders' => 15, 'amount' => 2800.00],
                    ['date' => '2026-03-11', 'day' => 'Wed', 'orders' => 18, 'amount' => 3200.00],
                    ['date' => '2026-03-12', 'day' => 'Thu', 'orders' => 22, 'amount' => 3800.00],
                    ['date' => '2026-03-13', 'day' => 'Fri', 'orders' => 25, 'amount' => 4500.00],
                    ['date' => '2026-03-14', 'day' => 'Sat', 'orders' => 28, 'amount' => 5200.00],
                    ['date' => '2026-03-15', 'day' => 'Sun', 'orders' => 20, 'amount' => 3500.00],
                ];
                $companySettings = [
                    'name' => 'СНИКЕРХЭД',
                    'email' => 'info@sneakerhead.by',
                    'phone' => '+375 (29) 123-45-67',
                ];
                $recentOrders = [];
                $tariffs = [];
                $statusCounts = [];
                $overdueOrders = [];
                $openReturnsCount = 0;
            }
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
            'recentOrders' => $recentOrders ?? [],
            'tariffs' => $tariffs ?? [],
            'pipelineStatusCounts' => $statusCounts ?? [],
            'overdueOrders' => $overdueOrders ?? [],
            'openReturnsCount' => $openReturnsCount ?? 0,
        ]);
    }
    
    /**
     * Получение статистики заказов
     */
    private function getOrderStats($user)
    {
        // ВРЕМЕННО: Для временной авторизации возвращаем демо-данные
        if ($user instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            return [
                'total' => 156,
                'today' => 12,
                'thisMonth' => 45,
                'totalAmount' => 15420.50,
                'pending' => 23,
                'completed' => 88,
            ];
        }
        
        $query = Order::find();
        
        if ($user->isLogist()) {
            $query->andWhere(['assigned_logist' => $user->id]);
        }
        
        return [
            'total' => (int)$query->count(),
            'today' => (int)$query->andWhere(['>=', 'created_at', strtotime('today')])->count(),
            'thisMonth' => (int)$query->andWhere(['>=', 'created_at', strtotime('first day of this month')])->count(),
            'totalAmount' => $query->sum('total_amount') ?: 0,
            'pending' => (int)Order::find()->where(['status' => 'created'])->count(),
            'completed' => (int)Order::find()->where(['status' => 'delivered'])->count(),
        ];
    }
    
    /**
     * Получение статистики товаров
     */
    private function getProductStats()
    {
        // ВРЕМЕННО: Для временной авторизации возвращаем демо-данные
        if (Yii::$app->user->identity instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            return [
                'total' => 1245,
                'active' => 1180,
                'inStock' => 1120,
                'outOfStock' => 125,
            ];
        }
        
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
        // ВРЕМЕННО: Для временной авторизации возвращаем демо-данные
        if (Yii::$app->user->identity instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            return [
                'total' => 8,
                'active' => 8,
                'admins' => 2,
                'logists' => 2,
                'managers' => 4,
            ];
        }
        
        try {
            return [
                'total' => (int)User::find()->count(),
                'active' => (int)User::find()->where(['status' => 'active'])->count(),
                'admins' => (int)User::find()->where(['role' => 'admin'])->count(),
                'logists' => (int)User::find()->where(['role' => 'logist'])->count(),
                'managers' => (int)User::find()->where(['role' => 'manager'])->count(),
            ];
        } catch (\Exception $e) {
            // Демо-данные при отсутствии БД
            return [
                'total' => 8,
                'active' => 8,
                'admins' => 2,
                'logists' => 2,
                'managers' => 4,
            ];
        }
    }
    
    /**
     * Получение топ товаров
     */
    private function getTopProducts()
    {
        // ВРЕМЕННО: Для временной авторизации возвращаем демо-данные
        if (Yii::$app->user->identity instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            return [
                ['product_name' => 'Nike Air Max 90', 'order_count' => 45, 'total_quantity' => 67, 'avg_price' => 350.00],
                ['product_name' => 'Adidas Ultraboost 22', 'order_count' => 38, 'total_quantity' => 52, 'avg_price' => 280.00],
                ['product_name' => 'Jordan 1 Retro High', 'order_count' => 32, 'total_quantity' => 41, 'avg_price' => 420.00],
                ['product_name' => 'New Balance 550', 'order_count' => 28, 'total_quantity' => 35, 'avg_price' => 180.00],
                ['product_name' => 'Yeezy Boost 350', 'order_count' => 25, 'total_quantity' => 30, 'avg_price' => 520.00],
            ];
        }
        
        // Топ товаров по количеству заказов
        // Используем product_name так как в order_item нет product_id
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
        // ВРЕМЕННО: Для временной авторизации возвращаем демо-данные
        if (Yii::$app->user->identity instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            return [];
        }
        
        try {
            return User::find()
                ->where(['role' => 'logist', 'status' => 'active'])
                ->orderBy(['username' => SORT_ASC])
                ->all();
        } catch (\Exception $e) {
            // Демо-данные при отсутствии БД
            return [];
        }
    }

    /**
     * Получение данных для графика заказов
     */
    private function getChartData($user)
    {
        // ВРЕМЕННО: Для временной авторизации возвращаем демо-данные
        if ($user instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            return [
                ['date' => '2026-03-09', 'day' => 'Mon', 'orders' => 12, 'amount' => 2100.00],
                ['date' => '2026-03-10', 'day' => 'Tue', 'orders' => 15, 'amount' => 2800.00],
                ['date' => '2026-03-11', 'day' => 'Wed', 'orders' => 18, 'amount' => 3200.00],
                ['date' => '2026-03-12', 'day' => 'Thu', 'orders' => 22, 'amount' => 3800.00],
                ['date' => '2026-03-13', 'day' => 'Fri', 'orders' => 25, 'amount' => 4500.00],
                ['date' => '2026-03-14', 'day' => 'Sat', 'orders' => 28, 'amount' => 5200.00],
                ['date' => '2026-03-15', 'day' => 'Sun', 'orders' => 20, 'amount' => 3500.00],
            ];
        }
        
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
     * Подсчёт заказов по статусам для пайплайна на дашборде
     */
    private function getPipelineStatusCounts(): array
    {
        $statuses = ['created', 'confirmed', 'paid', 'ordered', 'shipped', 'delivered'];
        $counts = [];
        foreach ($statuses as $status) {
            $counts[$status] = (int)Order::find()->where(['status' => $status])->count();
        }
        return $counts;
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
