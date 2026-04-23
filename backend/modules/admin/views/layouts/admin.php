<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\admin\assets\AdminAsset;

AdminAsset::register($this);

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
    
    <!-- Bootstrap Icons (CDN — нет npm-пакета в проекте) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Minimal Bootstrap modal CSS (без ресетов Bootstrap, только для .modal.fade скрытия и вёрстки) -->
    <style>
    .modal{display:none;position:fixed;top:0;left:0;z-index:1055;width:100%;height:100%;overflow-x:hidden;overflow-y:auto;outline:0}
    .modal.show{display:block}
    .modal-dialog{position:relative;width:auto;margin:.5rem;pointer-events:none}
    @media(min-width:576px){.modal-dialog{max-width:500px;margin:1.75rem auto}}
    .modal-dialog-centered{display:flex;align-items:center;min-height:calc(100% - 1rem)}
    .modal-lg{max-width:800px}
    .modal-content{position:relative;display:flex;flex-direction:column;width:100%;pointer-events:auto;background-color:var(--admin-surface,#fff);border:1px solid var(--admin-border,#dee2e6);border-radius:8px;box-shadow:0 4px 32px rgba(0,0,0,.18)}
    .modal-header{display:flex;align-items:center;justify-content:space-between;padding:1rem;border-bottom:1px solid var(--admin-border,#dee2e6);gap:.5rem}
    .modal-title{margin-bottom:0;font-size:1rem;font-weight:600;color:var(--admin-text-primary,#111)}
    .modal-body{position:relative;flex:1 1 auto;padding:1rem}
    .modal-footer{display:flex;justify-content:flex-end;padding:.75rem 1rem;border-top:1px solid var(--admin-border,#dee2e6);gap:.5rem}
    .modal-backdrop{position:fixed;top:0;left:0;z-index:1050;width:100vw;height:100vh;background-color:#000;opacity:0;transition:opacity .15s linear}
    .modal-backdrop.show{opacity:.5}
    .btn-close{background:transparent;border:0;font-size:1.2rem;cursor:pointer;opacity:.7;padding:.5rem}
    .btn-close:hover{opacity:1}
    .btn-close::before{content:'×'}
    /* Bootstrap grid helpers used in product size/image forms */
    .row{display:flex;flex-wrap:wrap;margin-right:-.375rem;margin-left:-.375rem}
    .col-md-3,.col-md-4{padding:.375rem;box-sizing:border-box}
    @media(min-width:768px){.col-md-3{width:25%}.col-md-4{width:33.333%}}
    .mb-3{margin-bottom:1rem!important}
    .form-label{display:block;margin-bottom:.4rem;font-size:.8125rem;font-weight:600;color:var(--admin-text-primary,#111)}
    .form-control{display:block;width:100%;padding:.375rem .75rem;font-size:.875rem;border:1px solid var(--admin-border,#ced4da);border-radius:6px;background:var(--admin-surface,#fff);color:var(--admin-text-primary,#111);box-sizing:border-box}
    .form-control:focus{outline:none;border-color:var(--admin-accent,#111);box-shadow:0 0 0 2px rgba(0,0,0,.06)}
    </style>

    <!-- Restore theme before paint -->
    <script>
        (function(){var t=localStorage.getItem('admin-theme');if(t)document.documentElement.setAttribute('data-theme',t)})();
    </script>

<?php // Отключаем debug toolbar для админки ?>
    <?php if (class_exists('yii\debug\Module')): ?>
    <style>.yii-debug-toolbar{display:none!important}</style>
    <?php endif ?>
    <?= $this->head() ?>
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
        </div>
        
        <nav class="admin-sidebar-nav">
            <?php
            // Order counts by status for sidebar badges
            $orderCounts = [];
            try {
                $counts = \Yii::$app->db->createCommand("
                    SELECT status, COUNT(*) as cnt FROM `order` GROUP BY status
                ")->queryAll();
                foreach ($counts as $row) {
                    $orderCounts[$row['status']] = (int)$row['cnt'];
                }
            } catch (\Exception $e) { $orderCounts = []; }
            $totalOrders = array_sum($orderCounts);

            // Load real statuses from order_status table
            $_sidebarStatuses = [];
            try {
                $_sidebarStatuses = \app\backend\modules\checkout\models\OrderStatus::find()
                    ->where(['is_active' => true])
                    ->orderBy(['sort' => SORT_ASC])
                    ->asArray()->all();
            } catch (\Exception $e) {}

            // Hex colors (order_status.color stores Bootstrap class names, not hex)
            $_statusColors = [
                'new' => '#3b82f6', 'created' => '#3b82f6',
                'paid' => '#22c55e', 'confirmed_and_paid' => '#22c55e',
                'ordered' => '#f59e0b', 'awaiting_warehouse' => '#f59e0b',
                'at_warehouse' => '#f59e0b', 'processing' => '#f59e0b',
                'international_delivery' => '#8b5cf6', 'local_delivery' => '#8b5cf6',
                'shipped' => '#8b5cf6', 'in_transit' => '#8b5cf6',
                'delivered' => '#10b981',
                'canceled' => '#ef4444', 'refunded' => '#ef4444', 'returned' => '#ef4444',
                'imported' => '#6b7280',
            ];

            // Append 'imported' if it has orders but isn't in order_status table
            if (!empty($orderCounts['imported']) && !in_array('imported', array_column($_sidebarStatuses, 'key'))) {
                $_sidebarStatuses[] = ['key' => 'imported', 'label' => 'Импортированы'];
            }

            // Build per-status nav items — only statuses with ≥1 order
            $_statusNavItems = [];
            foreach ($_sidebarStatuses as $_st) {
                $_cnt = $orderCounts[$_st['key']] ?? 0;
                if ($_cnt > 0) {
                    $_statusNavItems[] = [
                        'label' => $_st['label'],
                        'url' => '/admin/order?status=' . $_st['key'],
                        'ids' => [],
                        'badge' => $_cnt,
                        'indent' => true,
                        'dot' => $_statusColors[$_st['key']] ?? '#6b7280',
                    ];
                }
            }

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
                        ...array_merge(
                            [['label' => 'Все заказы', 'url' => '/admin/order', 'ids' => ['order'], 'badge' => $totalOrders ?: null]],
                            $_statusNavItems,
                            [
                                ['label' => 'Возвраты', 'url' => '/admin/return', 'ids' => ['return']],
                                ['label' => 'Отправка заказов', 'url' => '/admin/shipping/dispatch', 'ids' => ['dispatch']],
                            ]
                        )
                    ]
                ],

                // 📊 АНАЛИТИКА
                [
                    'label' => 'Аналитика',
                    'icon' => 'bi-bar-chart-line-fill',
                    'items' => [
                        ['label' => 'Аналитика и отчеты', 'url' => '/admin/analytics', 'ids' => ['analytics']],
                        ['label' => 'RFM сегменты', 'url' => '/admin/analytics/rfm', 'ids' => ['rfm']]
                    ]
                ],
                
                // 🛍️ КАТАЛОГ
                [
                    'label' => 'Каталог',
                    'icon' => 'bi-collection-fill',
                    'items' => [
                        ['label' => 'Товары', 'url' => '/admin/catalog', 'ids' => ['catalog', 'product']],
                        ['label' => 'Характеристики', 'url' => '/admin/characteristic', 'ids' => ['characteristic']],
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
                
                // 💰 ФИНАНСЫ
                [
                    'label' => 'Финансы',
                    'icon' => 'bi-cash-coin',
                    'items' => [
                        ['label' => 'Платежи',        'url' => '/admin/finance/payments', 'ids' => ['finance'], 'icon' => 'bi-credit-card'],
                        ['label' => 'Расходы',         'url' => '/admin/finance/expenses', 'ids' => ['finance'], 'icon' => 'bi-receipt'],
                        ['label' => 'P&L',             'url' => '/admin/finance/pnl',      'ids' => ['finance'], 'icon' => 'bi-graph-up'],
                        ['label' => 'Маржинальность',  'url' => '/admin/finance/margin',   'ids' => ['finance'], 'icon' => 'bi-percent'],
                    ]
                ],

                // 📦 ЗАКУПКИ
                [
                    'label' => 'Закупки',
                    'icon' => 'bi-box-seam',
                    'items' => [
                        ['label' => 'Поставщики',         'url' => '/admin/procurement/suppliers', 'ids' => ['procurement'], 'icon' => 'bi-building'],
                        ['label' => 'Закупки',             'url' => '/admin/procurement',           'ids' => ['procurement'], 'icon' => 'bi-clipboard-data'],
                        ['label' => 'Приёмка',             'url' => '/admin/procurement/receiving', 'ids' => ['procurement'], 'icon' => 'bi-box-arrow-in-down'],
                        ['label' => 'Возвраты поставщику', 'url' => '/admin/procurement/returns',   'ids' => ['procurement'], 'icon' => 'bi-arrow-return-left'],
                    ]
                ],

                // 🎟️ ПРОМО
                [
                    'label' => 'Промо',
                    'icon' => 'bi-ticket-detailed-fill',
                    'items' => [
                        ['label' => 'Купоны', 'url' => '/admin/coupon', 'ids' => ['coupon']],
                        ['label' => 'Маркетинг', 'url' => '/admin/marketing', 'ids' => ['marketing']],
                        ['label' => 'Кампании', 'url' => '/admin/marketing?tab=campaigns', 'ids' => ['marketing']]
                    ]
                ],
                
                // � ИНТЕГРАЦИИ
                [
                    'label' => 'Интеграции',
                    'icon' => 'bi-plugin',
                    'items' => [
                        ['label' => 'Плагины', 'url' => '/admin/plugin', 'ids' => ['plugin']],
                        ['label' => 'Импорт/Экспорт', 'url' => '/admin/import', 'ids' => ['import']],
                        ['label' => 'AmoCRM', 'url' => '/admin/plugin/amocrm', 'ids' => ['plugin'], 'icon' => 'bi-diagram-3'],
                        ['label' => 'Lamoda Parser', 'url' => '/admin/plugin/lamoda', 'ids' => ['plugin'], 'icon' => 'bi-cloud-download'],
                    ]
                ],
                
                // ⚙️ УПРАВЛЕНИЕ
                [
                    'label' => 'Управление',
                    'icon' => 'bi-gear-wide-connected',
                    'items' => [
                        ['label' => 'Настройки', 'url' => '/admin/settings', 'ids' => ['settings']],
                        ['label' => 'Настройки доставки', 'url' => '/admin/settings/shipping', 'ids' => ['settings']],
                        ['label' => 'Способы оплаты', 'url' => '/admin/settings/payment', 'ids' => ['settings']],
                        ['label' => 'Статусы заказов', 'url' => '/admin/settings/statuses', 'ids' => ['settings'], 'icon' => 'bi-card-checklist'],
                        ['label' => 'SEO', 'url' => '/admin/settings/seo', 'ids' => ['settings']],
                        ['label' => 'Источники заказов', 'url' => '/admin/settings/sources', 'ids' => ['settings'], 'icon' => 'bi-funnel'],
                        ['label' => 'Сообщения директору', 'url' => '/admin/feedback', 'icon' => 'bi-envelope-heart', 'ids' => ['feedback']],
                        ['label' => 'Редактор страниц', 'url' => '/admin/page', 'ids' => ['page']],
                        ['label' => 'Меню навигации', 'url' => '/admin/sidebar-menu', 'ids' => ['sidebar-menu']],
                        ['label' => 'Триггеры', 'url' => '/admin/settings/triggers', 'ids' => ['automation'], 'icon' => 'bi-lightning-charge'],
                        ['label' => 'Журнал действий', 'url' => '/admin/activity-log', 'ids' => ['activity-log'], 'icon' => 'bi-journal-text']
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
                    <button class="admin-nav-item admin-nav-toggle" onclick="toggleSubmenu(this)" data-label="<?= Html::encode($item['label']) ?>">
                        <i class="bi <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                        <i class="bi bi-chevron-down admin-nav-chevron"></i>
                    </button>
                    <div class="admin-nav-submenu <?= $isSubmenuActive ? 'open' : '' ?>">
                        <?php foreach ($item['items'] as $subItem): ?>
                            <a href="<?= Url::to([$subItem['url']]) ?>" class="admin-nav-subitem <?= (isset($subItem['ids']) && in_array($controllerId, $subItem['ids'])) ? 'active' : '' ?><?= !empty($subItem['indent']) ? ' admin-nav-subitem--indent' : '' ?>">
                                <?php if (!empty($subItem['dot'])): ?>
                                    <span class="admin-status-dot" style="background:<?= $subItem['dot'] ?>"></span>
                                <?php endif; ?>
                                <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $subItem['label'] ?></span>
                                <?php if (!empty($subItem['badge'])): ?>
                                    <span class="admin-status-count"><?= $subItem['badge'] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Simple menu item -->
                <a href="<?= Url::to([$item['url']]) ?>" class="admin-nav-item <?= $isActive ? 'active' : '' ?>" data-label="<?= Html::encode($item['label']) ?>">
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
                <!-- Mobile Menu Toggle -->
                <button class="admin-mobile-menu-btn" id="mobile-menu-toggle" onclick="toggleMobileSidebar()" title="Открыть меню" aria-label="Открыть меню">
                    <i class="bi bi-list"></i>
                </button>
                <!-- Desktop Sidebar Toggle -->
                <button class="admin-sidebar-toggle-btn" id="sidebar-toggle-btn" onclick="toggleDesktopSidebar()" title="Свернуть/развернуть меню" aria-label="Свернуть меню">
                    <i class="bi bi-layout-sidebar-reverse" id="sidebar-toggle-icon"></i>
                </button>
                <!-- Page Header Content (moved from admin-header) -->
                <div class="admin-topbar-page-header" id="page-header-content">
                    <h1 class="admin-topbar-page-title"><?= Html::encode($this->title) ?></h1>
                    <?php if (isset($this->params['headerActions']) && !empty($this->params['headerActions'])): ?>
                    <div class="admin-topbar-page-actions">
                        <?= implode('', $this->params['headerActions']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <button class="admin-topbar-search-btn" onclick="document.dispatchEvent(new KeyboardEvent('keydown',{key:'k',ctrlKey:true,bubbles:true}))" title="Глобальный поиск (Ctrl+K)">
                    <i class="bi bi-search"></i>
                    <span class="admin-topbar-search-hint">Поиск <kbd>Ctrl+K</kbd></span>
                </button>
            </div>
            <div class="admin-topbar-right">
                <!-- "+ Новый заказ" (скрыть на странице создания заказа) -->
                <?php if (!(Yii::$app->controller->id === 'order' && Yii::$app->controller->action->id === 'create')): ?>
                <a href="<?= \yii\helpers\Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                    <i class="bi bi-plus-circle"></i> Новый заказ
                </a>
                <?php endif; ?>
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
                
                <!-- Переключатель темы -->
                <button class="theme-toggle" id="theme-toggle" title="Переключить тему (Ctrl+D)">
                    <i class="bi bi-sun" id="theme-icon"></i>
                </button>

                <!-- Профиль пользователя -->
                <div class="admin-user-profile" style="position:relative">
                    <button class="admin-topbar-icon-btn admin-profile-btn" id="profile-btn" title="Профиль пользователя" onclick="toggleProfile()">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <div class="admin-profile-dropdown" id="profile-dropdown" style="display:none;">
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

<script>
// Калькулятор 4-режимный — idempotent init
(function() {
    // Use existing DOM elements if already present (another layout may have rendered them),
    // otherwise create them. Always (re)define the open/close functions so they
    // reference the actual live DOM nodes.
    var overlay = document.getElementById('calculator-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'admin-calculator-overlay'; overlay.id = 'calculator-overlay';
        overlay.addEventListener('click', function(){ window.closeCalculator && window.closeCalculator(); });
        document.body.appendChild(overlay);
    }

    var drawer = document.getElementById('calculator-drawer');
    var drawerIsNew = !drawer;
    if (!drawer) {
        drawer = document.createElement('div');
        drawer.className = 'admin-calculator-drawer'; drawer.id = 'calculator-drawer';
    drawer.innerHTML =
        '<div class="admin-calculator-header">' +
            '<h3><i class="bi bi-calculator"></i> Калькулятор цены</h3>' +
            '<button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="closeCalculator()"><i class="bi bi-x-lg"></i></button>' +
        '</div>' +
        '<div style="display:flex;gap:4px;padding:0 20px 12px;border-bottom:1px solid var(--admin-border)">' +
            '<button class="calc-tab-btn active" id="calc-tab-1" onclick="switchCalcMode(1)" title="CNY→BYN">1</button>' +
            '<button class="calc-tab-btn" id="calc-tab-2" onclick="switchCalcMode(2)" title="Полная стоимость">2</button>' +
            '<button class="calc-tab-btn" id="calc-tab-3" onclick="switchCalcMode(3)" title="Доставка">3</button>' +
            '<button class="calc-tab-btn" id="calc-tab-4" onclick="switchCalcMode(4)" title="Маржа">4</button>' +
            '<span id="calc-mode-label" style="margin-left:8px;font-size:12px;color:var(--admin-text-secondary);align-self:center">CNY → BYN</span>' +
        '</div>' +
        '<div class="admin-calculator-body">' +
          '<div id="calc-mode-1">' +
            '<div class="admin-form-group"><label class="admin-form-label">Цена CNY</label><input type="number" id="m1-cny" class="admin-form-input" placeholder="0" step="1"></div>' +
            '<div class="admin-form-group"><label class="admin-form-label">Курс CNY/BYN</label><input type="number" id="m1-rate" class="admin-form-input" value="0.45" step="0.001"></div>' +
            '<div class="admin-calculator-result"><div class="admin-calculator-result-row total"><span>= BYN:</span><span id="m1-result">0.00 BYN</span></div></div>' +
          '</div>' +
          '<div id="calc-mode-2" style="display:none">' +
            '<div class="admin-calculator-row"><div class="admin-form-group"><label class="admin-form-label">Цена покупки, CNY</label><input type="number" id="m2-cny" class="admin-form-input" placeholder="0" step="1"></div><div class="admin-form-group"><label class="admin-form-label">Курс</label><input type="number" id="m2-rate" class="admin-form-input" value="0.45" step="0.001"></div></div>' +
            '<div class="admin-calculator-row"><div class="admin-form-group"><label class="admin-form-label">Доставка Китай, CNY</label><input type="number" id="m2-ship-cny" class="admin-form-input" value="15" step="1"></div><div class="admin-form-group"><label class="admin-form-label">Таможня, BYN</label><input type="number" id="m2-customs" class="admin-form-input" value="0" step="1"></div></div>' +
            '<div class="admin-calculator-row"><div class="admin-form-group"><label class="admin-form-label">Доставка РБ, BYN</label><input type="number" id="m2-ship-byn" class="admin-form-input" value="8" step="0.5"></div><div class="admin-form-group"><label class="admin-form-label">Маржа, %</label><input type="number" id="m2-margin" class="admin-form-input" value="25" step="1"></div></div>' +
            '<div class="admin-calculator-result">' +
              '<div class="admin-calculator-result-row"><span>Товар:</span><span id="m2-r-prod">0</span></div>' +
              '<div class="admin-calculator-result-row"><span>Себестоимость:</span><span id="m2-r-cost">0</span></div>' +
              '<div class="admin-calculator-result-row"><span>Маржа:</span><span id="m2-r-margin">0</span></div>' +
              '<div class="admin-calculator-result-row total"><span>Цена продажи:</span><span id="m2-r-total">0 BYN</span></div>' +
            '</div>' +
          '</div>' +
          '<div id="calc-mode-3" style="display:none">' +
            '<div class="admin-form-group"><label class="admin-form-label">Вес, кг</label><input type="number" id="m3-weight" class="admin-form-input" placeholder="0.5" step="0.1"></div>' +
            '<div class="admin-form-group"><label class="admin-form-label">Служба доставки</label><select id="m3-provider" class="admin-form-input"><option value="europochta">Европочта (6.50+0.80/кг)</option><option value="belpochta">Белпочта (4.00+0.60/кг)</option><option value="cdek">СДЭК (7.00+1.20/кг)</option><option value="courier">Курьер Минск (8.00)</option></select></div>' +
            '<div class="admin-form-group"><label class="admin-form-label">Стоимость для страховки, BYN</label><input type="number" id="m3-value" class="admin-form-input" placeholder="0" step="1"></div>' +
            '<div class="admin-calculator-result">' +
              '<div class="admin-calculator-result-row"><span>Пересылка:</span><span id="m3-r-base">0</span></div>' +
              '<div class="admin-calculator-result-row"><span>Страховка:</span><span id="m3-r-ins">0</span></div>' +
              '<div class="admin-calculator-result-row total"><span>Итого доставка:</span><span id="m3-r-total">0 BYN</span></div>' +
            '</div>' +
          '</div>' +
          '<div id="calc-mode-4" style="display:none">' +
            '<div class="admin-form-group"><label class="admin-form-label">Себестоимость, BYN</label><input type="number" id="m4-cost" class="admin-form-input" placeholder="0" step="1"></div>' +
            '<div class="admin-form-group"><label class="admin-form-label">Цена продажи, BYN</label><input type="number" id="m4-price" class="admin-form-input" placeholder="0" step="1"></div>' +
            '<div class="admin-calculator-result">' +
              '<div class="admin-calculator-result-row"><span>Прибыль:</span><span id="m4-r-profit">0 BYN</span></div>' +
              '<div class="admin-calculator-result-row"><span>Маржа:</span><span id="m4-r-margin">0%</span></div>' +
              '<div class="admin-calculator-result-row total"><span>ROI:</span><span id="m4-r-roi">0%</span></div>' +
            '</div>' +
          '</div>' +
          '<div style="margin-top:12px"><button class="admin-btn admin-btn-secondary" style="width:100%" onclick="resetCalcMode()"><i class="bi bi-arrow-counterclockwise"></i> Сбросить</button></div>' +
        '</div>';
        document.body.appendChild(drawer);
    } // end if(!drawer)

    var currentMode = 1;
    var modeLabels = {1:'CNY → BYN',2:'Полная стоимость',3:'Стоимость доставки',4:'Маржа'};
    function v(id){return parseFloat(document.getElementById(id)&&document.getElementById(id).value)||0;}
    function fmt(n){return n.toFixed(2)+' BYN';}
    function set(id,val){var el=document.getElementById(id);if(el)el.textContent=val;}
    function runCalc(){
        if(currentMode===1){set('m1-result',fmt(v('m1-cny')*(v('m1-rate')||0.45)));}
        else if(currentMode===2){
            var rate=v('m2-rate')||0.45,prod=v('m2-cny')*rate,ship=v('m2-ship-cny')*rate,cust=v('m2-customs'),local=v('m2-ship-byn'),cost=prod+ship+cust+local,marginAmt=cost*(v('m2-margin')/100);
            set('m2-r-prod',fmt(prod));set('m2-r-cost',fmt(cost));set('m2-r-margin',fmt(marginAmt));set('m2-r-total',fmt(cost+marginAmt));
        } else if(currentMode===3){
            var w=v('m3-weight')||0.5,val=v('m3-value'),p=document.getElementById('m3-provider')&&document.getElementById('m3-provider').value||'europochta';
            var rates={europochta:[6.50,0.80],belpochta:[4.00,0.60],cdek:[7.00,1.20],courier:[8.00,0]};
            var r=rates[p]||rates.europochta,base=r[0]+w*r[1],ins=val*0.01;
            set('m3-r-base',fmt(base));set('m3-r-ins',fmt(ins));set('m3-r-total',fmt(base+ins));
        } else {
            var cost=v('m4-cost'),price=v('m4-price'),profit=price-cost;
            set('m4-r-profit',fmt(profit));set('m4-r-margin',(price>0?profit/price*100:0).toFixed(1)+'%');set('m4-r-roi',(cost>0?profit/cost*100:0).toFixed(1)+'%');
        }
    }

    window.switchCalcMode = function(mode) {
        currentMode=mode;
        [1,2,3,4].forEach(function(m){var el=document.getElementById('calc-mode-'+m),tab=document.getElementById('calc-tab-'+m);if(el)el.style.display=m===mode?'':'none';if(tab)tab.classList.toggle('active',m===mode);});
        var lbl=document.getElementById('calc-mode-label');if(lbl)lbl.textContent=modeLabels[mode]||'';
        runCalc();
    };
    // Always (re)define open/close using getElementById so they work regardless of
    // which layout created the elements.
    window.openCalculator = function() {
        var ov=document.getElementById('calculator-overlay'), dr=document.getElementById('calculator-drawer');
        if(ov) ov.classList.add('active');
        if(dr) dr.classList.add('active');
        document.body.style.overflow='hidden';
        var f=document.getElementById('m1-cny'); if(f) setTimeout(function(){f.focus();},100);
    };
    window.closeCalculator = function() {
        var ov=document.getElementById('calculator-overlay'), dr=document.getElementById('calculator-drawer');
        if(ov) ov.classList.remove('active');
        if(dr) dr.classList.remove('active');
        document.body.style.overflow='';
    };
    window.resetCalcMode=window.resetCalculator=function(){['m1-cny','m2-cny','m2-customs','m2-ship-byn','m3-value','m4-cost','m4-price'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});runCalc();};

    if(drawerIsNew) {
        ['m1-cny','m1-rate','m2-cny','m2-rate','m2-ship-cny','m2-customs','m2-ship-byn','m2-margin','m3-weight','m3-value','m3-provider','m4-cost','m4-price'].forEach(function(id){
            var el=document.getElementById(id);if(el)el.addEventListener('input',runCalc);
        });
    }

    if(!document.querySelector('.calc-tab-btn')){var st=document.createElement('style');st.textContent='.calc-tab-btn{width:32px;height:32px;border-radius:8px;border:1.5px solid var(--admin-border);background:transparent;font-size:13px;font-weight:700;cursor:pointer;color:var(--admin-text-secondary);transition:all .15s}.calc-tab-btn.active{background:var(--admin-primary,#2563eb);border-color:var(--admin-primary,#2563eb);color:#fff}';document.head.appendChild(st);}
})();
</script>
<script>
function toggleMobileSidebar(){var s=document.getElementById('admin-sidebar'),o=document.getElementById('sidebar-overlay');if(s.classList.contains('mobile-open')){closeMobileSidebar();}else{s.classList.add('mobile-open');o.classList.add('visible');document.body.style.overflow='hidden';}}
function closeMobileSidebar(){var s=document.getElementById('admin-sidebar'),o=document.getElementById('sidebar-overlay');s.classList.remove('mobile-open');o.classList.remove('visible');document.body.style.overflow='';}
function toggleSubmenu(btn){var g=btn.parentElement;g.classList.toggle('active');var sub=g.querySelector('.admin-nav-submenu');if(sub)sub.classList.toggle('open');}
function toggleNotifications(){var d=document.getElementById('notif-dropdown');d.style.display=d.style.display==='none'?'block':'none';}
function toggleProfile(){var d=document.getElementById('profile-dropdown');d.style.display=d.style.display==='none'?'flex':'none';}

// Desktop sidebar collapse
(function(){
    var STORAGE_KEY = 'admin-sidebar-collapsed';
    var sidebar = document.getElementById('admin-sidebar');
    var layout  = document.querySelector('.admin-layout');
    var main    = document.querySelector('.admin-main');
    var icon    = document.getElementById('sidebar-toggle-icon');

    function applyCollapsed(collapsed) {
        if (!sidebar) return;
        if (collapsed) {
            sidebar.classList.add('collapsed');
            if (layout) layout.classList.add('sidebar-collapsed');
            if (icon) { icon.className = 'bi bi-layout-sidebar'; }
        } else {
            sidebar.classList.remove('collapsed');
            if (layout) layout.classList.remove('sidebar-collapsed');
            if (icon) { icon.className = 'bi bi-layout-sidebar-reverse'; }
        }
        // Clear legacy inline marginLeft so CSS variable takes effect
        if (main) main.style.marginLeft = '';
    }

    var saved = localStorage.getItem(STORAGE_KEY);
    if (saved === '1') applyCollapsed(true);

    window.toggleDesktopSidebar = function() {
        var isCollapsed = sidebar && sidebar.classList.contains('collapsed');
        var next = !isCollapsed;
        applyCollapsed(next);
        localStorage.setItem(STORAGE_KEY, next ? '1' : '0');
    };
})();

document.addEventListener('click',function(e){
    if(!e.target.closest('#notif-btn')&&!e.target.closest('#notif-dropdown')){var nd=document.getElementById('notif-dropdown');if(nd)nd.style.display='none';}
    if(!e.target.closest('#profile-btn')&&!e.target.closest('#profile-dropdown')){var pd=document.getElementById('profile-dropdown');if(pd)pd.style.display='none';}
});
document.addEventListener('keydown',function(e){
    if(e.ctrlKey&&e.key==='d'){e.preventDefault();var btn=document.getElementById('theme-toggle');if(btn)btn.click();}
    if(e.key==='Escape'){closeMobileSidebar();}
    if(e.ctrlKey&&e.key==='k'){e.preventDefault();var o=document.getElementById('search-overlay');if(o)o.style.display=o.style.display==='none'?'flex':'none';var inp=document.getElementById('admin-search-input');if(inp)inp.focus();}
});
</script>

<!-- Bootstrap 5 JS bundle (для модальных окон) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
