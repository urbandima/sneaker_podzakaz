<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\admin\assets\AdminAsset;

AdminAsset::register($this);

$company = Yii::$app->settings->getCompany() ?? ['name' => 'СникерКультура'];
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
    
    <?php $this->head() ?>
    
    <!-- Bootstrap Icons (CDN — нет npm-пакета в проекте) -->
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
    <!-- Mobile Sidebar Overlay -->
    <div class="admin-sidebar-overlay" id="sidebar-overlay" onclick="closeMobileSidebar()"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <!-- Mobile Close Button -->
        <button class="admin-sidebar-close" id="sidebar-close" onclick="closeMobileSidebar()" title="Закрыть меню">
            <i class="bi bi-x-lg"></i>
        </button>
        
        <div class="admin-sidebar-header">
            <a href="<?= Url::to(['/admin']) ?>" class="admin-sidebar-logo">
                <i class="bi bi-shop"></i>
                <span><?= Html::encode($company['name']) ?></span>
            </a>
            <button class="admin-sidebar-toggle-btn" id="sidebar-toggle-btn" onclick="toggleDesktopSidebar()" title="Свернуть/развернуть меню" aria-label="Свернуть меню">
                <i class="bi bi-layout-sidebar-reverse" id="sidebar-toggle-icon"></i>
            </button>
        </div>
        
        <nav class="admin-sidebar-nav">
            <?php
            $navItems = [
                // 🏠 ГЛАВНАЯ
                [
                    'label' => 'Главная',
                    'icon' => 'bi-house-fill',
                    'url' => '/admin',
                    'ids' => ['dashboard'],
                    'items' => []
                ],
                
                // 📦 ПРОДАЖИ
                [
                    'label' => 'Продажи',
                    'icon' => 'bi-bag-check-fill',
                    'items' => [
                        ['label' => 'Заказы', 'url' => '/admin/order', 'ids' => ['order']],
                        ['label' => 'Возвраты', 'url' => '/admin/return', 'ids' => ['return']],
                        ['label' => 'Доставка', 'url' => '/admin/shipping', 'ids' => ['shipping']]
                    ]
                ],
                
                // 📊 АНАЛИТИКА
                [
                    'label' => 'Аналитика',
                    'icon' => 'bi-bar-chart-line-fill',
                    'items' => [
                        ['label' => 'Аналитика и отчеты', 'url' => '/admin/analytics', 'ids' => ['analytics']],
                        ['label' => 'RFM сегменты', 'url' => '/admin/analytics/rfm', 'ids' => ['rfm']],
                        ['label' => 'Маркетинг', 'url' => '/admin/marketing', 'ids' => ['marketing']]
                    ]
                ],
                
                // 🛍️ КАТАЛОГ
                [
                    'label' => 'Каталог',
                    'icon' => 'bi-collection-fill',
                    'items' => [
                        ['label' => 'Товары', 'url' => '/admin/catalog', 'ids' => ['catalog', 'product']],
                        ['label' => 'Теги', 'url' => '/admin/product-tag', 'ids' => ['product-tag']],
                        ['label' => 'Отзывы', 'url' => '/admin/review', 'ids' => ['review']]
                    ]
                ],
                
                // 👥 КЛИЕНТЫ
                [
                    'label' => 'Клиенты',
                    'icon' => 'bi-people-fill',
                    'url' => '/admin/customer',
                    'ids' => ['customer'],
                    'items' => []
                ],
                
                // 🎟️ ПРОМО
                [
                    'label' => 'Промо',
                    'icon' => 'bi-ticket-detailed-fill',
                    'items' => [
                        ['label' => 'Купоны', 'url' => '/admin/coupon', 'ids' => ['coupon']],
                        ['label' => 'Маркетинговые кампании', 'url' => '/admin/marketing/campaigns', 'ids' => ['marketing']]
                    ]
                ],
                
                // 🔌 ПЛАГИНЫ
                [
                    'label' => 'Плагины',
                    'icon' => 'bi-puzzle-fill',
                    'items' => [
                        ['label' => 'Все плагины', 'url' => '/admin/plugin', 'ids' => ['plugin']],
                        ['label' => 'POS-Терминал', 'url' => '/admin/pos', 'ids' => ['pos']],
                        ['label' => 'Импорт/Экспорт', 'url' => '/admin/import', 'ids' => ['import']]
                    ]
                ],
                
                // ⚙️ УПРАВЛЕНИЕ
                [
                    'label' => 'Управление',
                    'icon' => 'bi-gear-wide-connected',
                    'items' => [
                        ['label' => 'Настройки', 'url' => '/admin/settings', 'ids' => ['settings']],
                        ['label' => 'Настройки доставки', 'url' => '/admin/settings/shipping', 'ids' => ['settings']],
                        ['label' => 'Меню навигации', 'url' => '/admin/sidebar-menu', 'ids' => ['sidebar-menu']]
                    ]
                ]
            ];
            
            foreach ($navItems as $item): 
                $hasSubmenu = !empty($item['items']);
                $isActive = isset($item['ids']) && in_array($controllerId, $item['ids']);
                $isSubmenuActive = false;
                
                if ($hasSubmenu) {
                    foreach ($item['items'] as $subItem) {
                        if (isset($subItem['ids']) && in_array($controllerId, $subItem['ids'])) {
                            $isSubmenuActive = true;
                            break;
                        }
                    }
                }
            ?>
            
            <?php if ($hasSubmenu): ?>
                <!-- Menu item with submenu -->
                <div class="admin-nav-group <?= $isSubmenuActive ? 'active' : '' ?>">
                    <button class="admin-nav-item admin-nav-toggle" onclick="toggleSubmenu(this)">
                        <i class="bi <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                        <i class="bi bi-chevron-down admin-nav-chevron"></i>
                    </button>
                    <div class="admin-nav-submenu <?= $isSubmenuActive ? 'open' : '' ?>">
                        <?php foreach ($item['items'] as $subItem): ?>
                            <a href="<?= Url::to([$subItem['url']]) ?>" class="admin-nav-subitem <?= (isset($subItem['ids']) && in_array($controllerId, $subItem['ids'])) ? 'active' : '' ?>">
                                <?= $subItem['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Simple menu item -->
                <a href="<?= Url::to([$item['url']]) ?>" class="admin-nav-item <?= $isActive ? 'active' : '' ?>">
                    <i class="bi <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endif; endforeach ?>

            <div class="admin-nav-divider" style="margin-top: auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);"></div>
            <a href="<?= Url::to(['/']) ?>" class="admin-nav-item" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>На сайт</span>
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <!-- Live Search Overlay -->
        <div class="admin-search-overlay d-none" id="search-overlay">
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
                <!-- Mobile Burger Menu -->
                <button class="admin-mobile-menu-btn" id="mobile-menu-toggle" onclick="toggleMobileSidebar()" title="Открыть меню" aria-label="Открыть меню">
                    <i class="bi bi-list"></i>
                </button>
                <button class="admin-topbar-search-btn" onclick="document.dispatchEvent(new KeyboardEvent('keydown',{key:'k',ctrlKey:true,bubbles:true}))" title="Глобальный поиск (Ctrl+K)">
                    <i class="bi bi-search"></i>
                    <span class="admin-topbar-search-hint">Поиск <kbd>Ctrl+K</kbd></span>
                </button>
            </div>
            <div class="admin-topbar-right">
                <!-- "+ Новый заказ" -->
                <a href="<?= \yii\helpers\Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                    <i class="bi bi-plus-circle"></i><span class="admin-new-order-text"> Новый заказ</span>
                </a>
                <!-- Калькулятор -->
                <button class="admin-topbar-icon-btn" id="calc-open-btn" title="Калькулятор стоимости" onclick="openCalculator()">
                    <i class="bi bi-calculator-fill"></i>
                </button>
                <!-- Уведомления -->
                <div class="pos-relative">
                    <button class="admin-topbar-icon-btn admin-notif-btn" id="notif-btn" title="Уведомления" onclick="toggleNotifications()">
                        <i class="bi bi-bell-fill"></i>
                        <?php
                        $newOrdersCount = \app\backend\modules\checkout\models\Order::find()
                            ->where(['status' => 'new'])
                            ->andWhere(['>', 'created_at', time() - 86400])
                            ->count();
                        ?>
                        <?php if ($newOrdersCount > 0): ?>
                            <span class="admin-notif-badge"><?= $newOrdersCount ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notif-dropdown" class="admin-notif-dropdown d-none">
                        <div class="admin-notif-header">
                            <h4>Уведомления</h4>
                            <span class="admin-badge admin-badge-primary"><?= $newOrdersCount ?></span>
                        </div>
                        <div class="admin-notif-list">
                            <?php
                            $newOrders = \app\backend\modules\checkout\models\Order::find()
                                ->where(['status' => 'new'])
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
                                <a href="<?= \yii\helpers\Url::to(['/admin/order', 'status' => 'new']) ?>" class="admin-notif-footer">
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
                
                <!-- Переключатель темы -->
                <button class="theme-toggle" id="theme-toggle" title="Переключить тему" onclick="toggleTheme()">
                    <i class="bi bi-sun" id="theme-icon"></i>
                </button>

                <!-- Профиль пользователя -->
                <div class="admin-user-profile pos-relative">
                    <button class="admin-topbar-icon-btn admin-profile-btn" id="profile-btn" title="Профиль пользователя" onclick="toggleProfile()">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <div class="admin-profile-dropdown d-none" id="profile-dropdown">
                        <div class="admin-profile-header">
                            <div class="admin-profile-info">
                                <div class="admin-profile-name"><?= Html::encode($company['name'] ?? 'Admin') ?></div>
                                <div class="admin-profile-role">Администратор</div>
                            </div>
                        </div>
                        <div class="admin-profile-divider"></div>
                        <a href="<?= Url::to(['/admin/settings']) ?>" class="admin-profile-item">
                            <i class="bi bi-gear"></i> Настройки
                        </a>
                        <a href="<?= Url::to(['/']) ?>" class="admin-profile-item" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> На сайт
                        </a>
                        <div class="admin-profile-divider"></div>
                        <a href="<?= Url::to(['/admin/logout']) ?>" class="admin-profile-item admin-profile-logout">
                            <i class="bi bi-power"></i> Выйти
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?= $content ?>
    </main>
</div>

<!-- Mobile Sidebar Script -->
<script>
function toggleMobileSidebar() {
    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var isOpen  = sidebar.classList.contains('mobile-open');
    if (isOpen) {
        closeMobileSidebar();
    } else {
        sidebar.classList.add('mobile-open');
        overlay.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }
}

function closeMobileSidebar() {
    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('visible');
    document.body.style.overflow = '';
}

// Close sidebar on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMobileSidebar();
});

// Desktop sidebar collapse — persists via localStorage
(function() {
    var STORAGE_KEY = 'admin-sidebar-collapsed';
    var sidebar  = document.getElementById('admin-sidebar');
    var layout   = document.querySelector('.admin-layout');
    var icon     = document.getElementById('sidebar-toggle-icon');

    function applyCollapsed(collapsed) {
        if (!sidebar) return;
        if (collapsed) {
            sidebar.classList.add('collapsed');
            if (layout) layout.classList.add('sidebar-collapsed');
            if (icon) icon.className = 'bi bi-layout-sidebar';
        } else {
            sidebar.classList.remove('collapsed');
            if (layout) layout.classList.remove('sidebar-collapsed');
            if (icon) icon.className = 'bi bi-layout-sidebar-reverse';
        }
        // Clear any legacy inline style so CSS variable takes effect
        var main = document.querySelector('.admin-main');
        if (main) main.style.marginLeft = '';
    }

    // Restore saved state
    if (localStorage.getItem(STORAGE_KEY) === '1') applyCollapsed(true);

    window.toggleDesktopSidebar = function() {
        var next = !sidebar.classList.contains('collapsed');
        applyCollapsed(next);
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
    };
})();
</script>

<!-- Theme Toggle Script -->
<script>
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';

    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('admin-theme', newTheme);

    // Обновляем иконку
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.className = newTheme === 'light' ? 'bi bi-sun' : 'bi bi-moon';
    }
}

// Инициализация темы при загрузке
(function() {
    const savedTheme = localStorage.getItem('admin-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    // Обновляем иконку
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.className = savedTheme === 'light' ? 'bi bi-sun' : 'bi bi-moon';
    }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>


