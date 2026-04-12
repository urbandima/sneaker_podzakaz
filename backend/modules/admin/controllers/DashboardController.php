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
                'outOfStock' => 125,
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
            }
        }
        
        // B3.1 Operational stats
        if ($demoMode) {
            $operationalStats = [
                'unprocessed2h' => 3,
                'delayed3d' => 2,
                'awaitingPoizon' => 7,
            ];
        } else {
            try {
                $operationalStats = $this->getOperationalStats();
            } catch (\Exception $e) {
                $operationalStats = ['unprocessed2h' => 0, 'delayed3d' => 0, 'awaitingPoizon' => 0];
            }
        }

        // B3.3 Conversion funnel
        if ($demoMode) {
            $funnelData = ['views' => 1240, 'carts' => 310, 'orders' => 88];
        } else {
            try {
                $funnelData = $this->getFunnelData();
            } catch (\Exception $e) {
                $funnelData = ['views' => 0, 'carts' => 0, 'orders' => 0];
            }
        }

        // B3.4 CNY rate info
        try {
            $currencyInfo = Yii::$app->has('currency') ? Yii::$app->currency->getCurrencyInfo() : ['rate' => 0.45, 'updated_at' => null, 'source' => 'default'];
        } catch (\Exception $e) {
            $currencyInfo = ['rate' => 0.45, 'updated_at' => null, 'source' => 'default'];
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
            'operationalStats' => $operationalStats,
            'funnelData' => $funnelData,
            'currencyInfo' => $currencyInfo,
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
        
        $baseQuery = Order::find();
        
        if ($user->isLogist()) {
            $baseQuery->andWhere(['assigned_logist' => $user->id]);
        }
        
        return [
            'total' => (int)(clone $baseQuery)->count(),
            'today' => (int)(clone $baseQuery)->andWhere(['>=', 'created_at', strtotime('today')])->count(),
            'thisMonth' => (int)(clone $baseQuery)->andWhere(['>=', 'created_at', strtotime('first day of this month')])->count(),
            'totalAmount' => (float)((clone $baseQuery)->sum('total_amount') ?: 0),
            'pending' => (int)(clone $baseQuery)->andWhere(['status' => 'new'])->count(),
            'processing' => (int)(clone $baseQuery)->andWhere(['status' => ['confirmed_and_paid', 'paid', 'ordered']])->count(),
            'completed' => (int)(clone $baseQuery)->andWhere(['status' => 'delivered'])->count(),
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
     * Получение данных для графика заказов (30 дней + сравнение с предыдущим периодом)
     * B3.2: Returns current 30 days and prev 30 days side-by-side
     */
    private function getChartData($user)
    {
        // ВРЕМЕННО: Для временной авторизации возвращаем демо-данные
        if ($user instanceof \app\backend\modules\admin\models\TemporaryAdminIdentity) {
            $demo = [];
            for ($i = 29; $i >= 0; $i--) {
                $ts = strtotime("-$i days");
                $demo[] = [
                    'date'         => date('Y-m-d', $ts),
                    'day'          => date('d.m', $ts),
                    'orders'       => rand(5, 30),
                    'amount'       => round(rand(800, 5500) * 1.0, 2),
                    'prev_orders'  => rand(3, 25),
                    'prev_amount'  => round(rand(600, 4800) * 1.0, 2),
                ];
            }
            return $demo;
        }

        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date    = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('d.m', strtotime("-$i days"));

            // Текущий период
            $qCur = Order::find()
                ->where(['>=', 'created_at', strtotime($date)])
                ->andWhere(['<', 'created_at', strtotime($date . ' +1 day')]);
            if ($user->isLogist()) {
                $qCur->andWhere(['assigned_logist' => $user->id]);
            }
            $curCount  = (int)$qCur->count();
            $curAmount = (float)($qCur->sum('total_amount') ?: 0);

            // Предыдущий период (те же дни, -30 дней)
            $prevDate = date('Y-m-d', strtotime("-" . ($i + 30) . " days"));
            $qPrev = Order::find()
                ->where(['>=', 'created_at', strtotime($prevDate)])
                ->andWhere(['<', 'created_at', strtotime($prevDate . ' +1 day')]);
            if ($user->isLogist()) {
                $qPrev->andWhere(['assigned_logist' => $user->id]);
            }
            $prevCount  = (int)$qPrev->count();
            $prevAmount = (float)($qPrev->sum('total_amount') ?: 0);

            $data[] = [
                'date'        => $date,
                'day'         => $dayName,
                'orders'      => $curCount,
                'amount'      => $curAmount,
                'prev_orders' => $prevCount,
                'prev_amount' => $prevAmount,
            ];
        }

        return $data;
    }

    /**
     * B3.1: Операционная статистика
     */
    private function getOperationalStats()
    {
        $twoHoursAgo = time() - 7200;
        $threeDaysAgo = time() - 259200;

        $unprocessed2h = (int)Order::find()
            ->where(['status' => 'new'])
            ->andWhere(['<', 'created_at', $twoHoursAgo])
            ->count();

        // Заказы без изменения статуса более 3 дней
        // Используем updated_at; если нет такого поля — считаем по created_at
        try {
            $delayed3d = (int)Yii::$app->db->createCommand("
                SELECT COUNT(*) FROM `order`
                WHERE updated_at < :ts
                  AND status NOT IN ('delivered','canceled')
            ", [':ts' => $threeDaysAgo])->queryScalar();
        } catch (\Exception $e) {
            $delayed3d = 0;
        }

        $awaitingPoizon = (int)Order::find()
            ->where(['status' => 'paid'])
            ->count();

        return [
            'unprocessed2h' => $unprocessed2h,
            'delayed3d'     => $delayed3d,
            'awaitingPoizon' => $awaitingPoizon,
        ];
    }

    /**
     * B3.3: Данные воронки конверсии из analytics_events
     */
    private function getFunnelData()
    {
        try {
            $db = Yii::$app->db;
            $views  = (int)$db->createCommand("SELECT COUNT(*) FROM analytics_event WHERE event_type='view'")->queryScalar();
            $carts  = (int)$db->createCommand("SELECT COUNT(*) FROM analytics_event WHERE event_type='add_to_cart'")->queryScalar();
            $orders = (int)$db->createCommand("SELECT COUNT(*) FROM analytics_event WHERE event_type='order'")->queryScalar();
            return ['views' => $views, 'carts' => $carts, 'orders' => $orders];
        } catch (\Exception $e) {
            return ['views' => 0, 'carts' => 0, 'orders' => 0];
        }
    }

    /**
     * B3.4: AJAX — вернуть текущий курс CNY
     */
    public function actionCnyRate()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        try {
            $info = Yii::$app->currency->getCurrencyInfo();
            return $info;
        } catch (\Exception $e) {
            return ['rate' => 0.45, 'updated_at' => null, 'source' => 'fallback'];
        }
    }

    /**
     * B3.4: AJAX — обновить курс CNY через NBRB API
     */
    public function actionUpdateCnyRate()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!Yii::$app->request->isAjax && !Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Неверный метод запроса'];
        }
        try {
            /** @var \app\backend\shared\components\CurrencyService $currency */
            $currency = Yii::$app->currency;
            // Сбрасываем кэш чтобы форсировать обновление
            $currency->clearCache();
            // Получаем свежий курс (логика получения через API — внутри getCurrencyInfo)
            $rate = $currency->getCnyToBynRate();
            $info = $currency->getCurrencyInfo();
            return ['success' => true, 'rate' => $rate, 'updated_at' => $info['updated_at'], 'source' => $info['source']];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
