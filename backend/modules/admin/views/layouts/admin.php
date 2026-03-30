<?php

use yii\helpers\Html;
use yii\helpers\Url;

$company = Yii::$app->settings->getCompany() ?? ['name' => 'СНИКЕРХЭД'];
$controllerId = Yii::$app->controller->id;
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> — Админ</title>
    
    <!-- Admin CSS -->
    <link href="/css/admin-shopify-2026.css?v=<?= file_exists(Yii::getAlias('@webroot') . '/css/admin-shopify-2026.css') ? filemtime(Yii::getAlias('@webroot') . '/css/admin-shopify-2026.css') : time() ?>" rel="stylesheet">
    
<!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Restore theme before paint -->
    <script>
        (function(){var t=localStorage.getItem('admin-theme');if(t)document.documentElement.setAttribute('data-theme',t)})();
    </script>
    
<?php // Отключаем debug toolbar для админки ?>
    <?php if (class_exists('yii\debug\Module')): ?>
    <style>.yii-debug-toolbar{display:none!important}</style>
    <?php endif ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="<?= Url::to(['/admin']) ?>" class="admin-sidebar-logo">
                <i class="bi bi-shop"></i>
                <span><?= Html::encode($company['name']) ?></span>
            </a>
        </div>
        
        <nav class="admin-sidebar-nav">
            <?php
            $navItems = [
                ['label' => 'Главная', 'url' => '/admin', 'icon' => 'bi-grid-1x2-fill', 'ids' => ['dashboard']],
                ['label' => 'Заказы', 'url' => '/admin/order', 'icon' => 'bi-bag-check-fill', 'ids' => ['order']],
                ['label' => 'Каталог', 'url' => '/admin/catalog', 'icon' => 'bi-collection-fill', 'ids' => ['catalog', 'product']],
                ['label' => 'Клиенты', 'url' => '/admin/customer', 'icon' => 'bi-people-fill', 'ids' => ['customer']],
                ['label' => 'Купоны', 'url' => '/admin/coupon', 'icon' => 'bi-ticket-detailed-fill', 'ids' => ['coupon']],
                ['label' => 'Возвраты', 'url' => '/admin/return', 'icon' => 'bi-arrow-return-left', 'ids' => ['return']],
                ['label' => 'Отзывы', 'url' => '/admin/review', 'icon' => 'bi-star-fill', 'ids' => ['review']],
                ['label' => 'Аналитика', 'url' => '/admin/analytics', 'icon' => 'bi-bar-chart-line-fill', 'ids' => ['analytics']],
                ['label' => 'RFM сегменты', 'url' => '/admin/analytics/rfm', 'icon' => 'bi-diagram-3-fill', 'ids' => ['analytics']],
                ['label' => 'Маркетинг', 'url' => '/admin/marketing', 'icon' => 'bi-megaphone-fill', 'ids' => ['marketing']],
                ['label' => 'POS-Терминал', 'url' => '/admin/pos', 'icon' => 'bi-shop', 'ids' => ['pos']],
                ['label' => 'Плагины', 'url' => '/admin/plugin', 'icon' => 'bi-plugin', 'ids' => ['plugin']],
                ['label' => 'Настройки', 'url' => '/admin/settings', 'icon' => 'bi-gear-wide-connected', 'ids' => ['settings']],
            ];
            foreach ($navItems as $item): ?>
            <a href="<?= Url::to([$item['url']]) ?>" class="admin-nav-item <?= in_array($controllerId, $item['ids']) ? 'active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
            <?php endforeach ?>

            <div class="admin-nav-divider" style="margin-top: auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);"></div>
            <a href="<?= Url::to(['/']) ?>" class="admin-nav-item" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>На сайт</span>
            </a>
            <a href="<?= Url::to(['/admin/logout']) ?>" class="admin-nav-item" style="color: rgba(255,255,255,0.6);">
                <i class="bi bi-power"></i>
                <span>Выйти</span>
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <!-- Live Search Overlay -->
        <div class="admin-search-overlay" id="search-overlay" style="display:none">
            <div class="admin-search-modal">
                <div class="admin-search-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="admin-search-input" placeholder="Поиск по админке... (Esc — закрыть)" autocomplete="off">
                    <kbd>Ctrl+K</kbd>
                </div>
                <div class="admin-search-results" id="search-results"></div>
            </div>
        </div>

        <!-- B2.2 Global Header Bar -->
        <div class="admin-topbar" id="admin-topbar">
            <div class="admin-topbar-left">
                <button class="admin-topbar-search-btn" onclick="document.dispatchEvent(new KeyboardEvent('keydown',{key:'k',ctrlKey:true,bubbles:true}))" title="Глобальный поиск (Ctrl+K)">
                    <i class="bi bi-search"></i>
                    <span class="admin-topbar-search-hint">Поиск <kbd>Ctrl+K</kbd></span>
                </button>
            </div>
            <div class="admin-topbar-right">
                <!-- "+ Новый заказ" -->
                <a href="<?= \yii\helpers\Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                    <i class="bi bi-plus-circle"></i> Новый заказ
                </a>
                <!-- Калькулятор -->
                <button class="admin-topbar-icon-btn" id="calc-open-btn" title="Калькулятор стоимости" onclick="openCalculator()">
                    <i class="bi bi-calculator-fill"></i>
                </button>
                <!-- Уведомления -->
                <div style="position:relative">
                    <button class="admin-topbar-icon-btn admin-notif-btn" id="notif-btn" title="Уведомления" onclick="toggleNotifications()">
                        <i class="bi bi-bell-fill"></i>
                        <?php
                        $newOrdersCount = \app\backend\modules\checkout\models\Order::find()
                            ->where(['status' => 'created'])
                            ->andWhere(['>', 'created_at', time() - 86400])
                            ->count();
                        ?>
                        <?php if ($newOrdersCount > 0): ?>
                            <span class="admin-notif-badge"><?= $newOrdersCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notif-dropdown" class="admin-notif-dropdown" style="display:none">
                        <div class="admin-notif-header">
                            <h4>Уведомления</h4>
                            <span class="admin-badge admin-badge-primary"><?= $newOrdersCount ?></span>
                        </div>
                        <div class="admin-notif-list">
                            <?php
                            $newOrders = \app\backend\modules\checkout\models\Order::find()
                                ->where(['status' => 'created'])
                                ->orderBy(['created_at' => SORT_DESC])
                                ->limit(5)
                                ->all();
                            ?>
                            <?php if (!empty($newOrders)): ?>
                                <?php foreach ($newOrders as $order): ?>
                                    <a href="<?= \yii\helpers\Url::to(['/admin/order/view', 'id' => $order->id]) ?>" class="admin-notif-item">
                                        <div class="admin-notif-icon">
                                            <i class="bi bi-bag-check-fill"></i>
                                        </div>
                                        <div class="admin-notif-content">
                                            <div class="admin-notif-title">Новый заказ #<?= $order->order_number ?></div>
                                            <div class="admin-notif-text"><?= $order->client_name ?> • <?= number_format($order->total_amount, 2) ?> BYN</div>
                                            <div class="admin-notif-time"><?= \Yii::$app->formatter->asRelativeTime($order->created_at) ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                                <a href="<?= \yii\helpers\Url::to(['/admin/order', 'status' => 'created']) ?>" class="admin-notif-footer">
                                    Показать все заказы
                                </a>
                            <?php else: ?>
                                <div class="admin-notif-empty">
                                    <i class="bi bi-check-circle"></i>
                                    <p>Нет новых уведомлений</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <script>
                function toggleNotifications() {
                    const dropdown = document.getElementById('notif-dropdown');
                    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                }
                document.addEventListener('click', function(e) {
                    const notifBtn = document.getElementById('notif-btn');
                    const dropdown = document.getElementById('notif-dropdown');
                    if (!notifBtn.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.style.display = 'none';
                    }
                });
                </script>
            </div>
        </div>

        <?= $content ?>
    </main>
</div>

<!-- B2.3 Calc Drawer Overlay -->
<div id="calc-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);z-index:1099;"></div>

<!-- B2.3 Calculator Drawer -->
<div id="calc-drawer" class="calc-drawer">
    <div class="calc-drawer-header">
        <h3 class="calc-drawer-title"><i class="bi bi-calculator-fill"></i> Калькулятор</h3>
        <button class="admin-topbar-icon-btn" id="calc-close-btn" title="Закрыть"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="calc-drawer-body">
        <!-- Курс CNY -->
        <div class="calc-rate-info" id="calc-rate-display">
            <span>Загрузка курса...</span>
        </div>
        <button class="admin-btn admin-btn-secondary admin-btn-sm" id="calc-refresh-rate" style="margin-bottom:1rem;width:100%">
            <i class="bi bi-arrow-clockwise"></i> Обновить курс
        </button>

        <!-- Поля расчёта -->
        <div class="admin-form-group">
            <label class="admin-form-label" for="calc-cny">Цена в CNY (юань)</label>
            <input type="number" id="calc-cny" class="admin-form-input" placeholder="0.00" min="0" step="0.01">
        </div>
        <div class="admin-form-group">
            <label class="admin-form-label" for="calc-markup">Наценка %</label>
            <input type="number" id="calc-markup" class="admin-form-input" placeholder="50" min="0" step="1" value="50">
        </div>
        <div class="admin-form-group">
            <label class="admin-form-label" for="calc-weight">Вес (кг)</label>
            <input type="number" id="calc-weight" class="admin-form-input" placeholder="0.5" min="0" step="0.01">
        </div>

        <!-- Разбивка расчёта -->
        <div id="calc-breakdown" class="calc-breakdown" style="display:none"></div>

        <!-- Итого -->
        <div class="admin-form-group">
            <label class="admin-form-label" for="calc-total">Итого BYN</label>
            <input type="text" id="calc-total" class="admin-form-input calc-total-field" placeholder="—" readonly>
        </div>

        <a id="calc-create-order" href="#" class="admin-btn admin-btn-primary" style="width:100%;justify-content:center;display:inline-flex">
            <i class="bi bi-bag-plus-fill"></i> Создать заказ
        </a>
    </div>
</div>

<!-- Admin JS -->
<script src="/js/admin.js?v=<?= file_exists(Yii::getAlias('@webroot') . '/js/admin.js') ? filemtime(Yii::getAlias('@webroot') . '/js/admin.js') : time() ?>"></script>
<script src="/js/admin-search.js?v=<?= file_exists(Yii::getAlias('@webroot') . '/js/admin-search.js') ? filemtime(Yii::getAlias('@webroot') . '/js/admin-search.js') : time() ?>"></script>
<script src="/js/dashboard.js?v=<?= file_exists(Yii::getAlias('@webroot') . '/js/dashboard.js') ? filemtime(Yii::getAlias('@webroot') . '/js/dashboard.js') : time() ?>"></script>
<script src="/js/orders.js?v=<?= file_exists(Yii::getAlias('@webroot') . '/js/orders.js') ? filemtime(Yii::getAlias('@webroot') . '/js/orders.js') : time() ?>"></script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
