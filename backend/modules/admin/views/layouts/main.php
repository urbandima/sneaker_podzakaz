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
        
        <div class="admin-sidebar-header" style="display:flex;align-items:center;justify-content:space-between">
            <a href="<?= Url::to(['/admin']) ?>" class="admin-sidebar-logo">
                <i class="bi bi-shop"></i>
                <span><?= Html::encode($company['name']) ?></span>
            </a>
            <button class="admin-sidebar-toggle-btn" id="sidebar-toggle-btn" onclick="toggleDesktopSidebar()" title="Свернуть меню">
                <i class="bi bi-layout-sidebar-reverse" id="sidebar-toggle-icon"></i>
            </button>
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
                                ['label' => 'Доставка', 'url' => '/admin/shipping', 'ids' => ['shipping']],
                                ['label' => 'Отправка заказов', 'url' => '/admin/shipping/dispatch', 'ids' => ['dispatch'], 'indent' => true],
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
                
                // 🎟️ ПРОМО
                [
                    'label' => 'Промо',
                    'icon' => 'bi-ticket-detailed-fill',
                    'items' => [
                        ['label' => 'Купоны', 'url' => '/admin/coupon', 'ids' => ['coupon']],
                        ['label' => 'Кампании', 'url' => '/admin/marketing?tab=campaigns', 'ids' => ['marketing']]
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
                        ['label' => 'Способы оплаты', 'url' => '/admin/settings/payment', 'ids' => ['settings']],
                        ['label' => 'Статусы заказов', 'url' => '/admin/settings/statuses', 'ids' => ['settings'], 'icon' => 'bi-card-checklist'],
                        ['label' => 'SEO', 'url' => '/admin/settings/seo', 'ids' => ['settings']],
                        ['label' => 'Источники заказов', 'url' => '/admin/settings/sources', 'ids' => ['settings'], 'icon' => 'bi-funnel'],
                        ['label' => 'Сообщения директору', 'url' => '/admin/feedback', 'icon' => 'bi-envelope-heart', 'ids' => ['feedback']],
                        ['label' => 'Редактор страниц', 'url' => '/admin/page', 'ids' => ['page']],
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
                <!-- "+ Новый заказ" — скрываем на странице создания заказа -->
                <?php if (!($controllerId === 'order' && Yii::$app->controller->action->id === 'create')): ?>
                <a href="<?= \yii\helpers\Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                    <i class="bi bi-plus-circle"></i><span class="admin-new-order-text"> Новый заказ</span>
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
                            ->where(['status' => 'new'])
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
                <button class="theme-toggle" id="theme-toggle" title="Переключить тему">
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

<!-- Calculator Drawer (4 режима) -->
<div class="admin-calculator-overlay" id="calculator-overlay" onclick="closeCalculator()"></div>
<div class="admin-calculator-drawer" id="calculator-drawer">
    <div class="admin-calculator-header">
        <h3><i class="bi bi-calculator"></i> Калькулятор цены</h3>
        <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="closeCalculator()"><i class="bi bi-x-lg"></i></button>
    </div>

    <!-- Табы режимов -->
    <div style="display:flex;gap:4px;padding:0 20px 12px;border-bottom:1px solid var(--admin-border)">
        <button class="calc-tab-btn active" onclick="switchCalcMode(1)" id="calc-tab-1" title="CNY → BYN">1</button>
        <button class="calc-tab-btn" onclick="switchCalcMode(2)" id="calc-tab-2" title="Полная стоимость">2</button>
        <button class="calc-tab-btn" onclick="switchCalcMode(3)" id="calc-tab-3" title="Стоимость доставки">3</button>
        <button class="calc-tab-btn" onclick="switchCalcMode(4)" id="calc-tab-4" title="Маржа">4</button>
        <span id="calc-mode-label" style="margin-left:8px;font-size:12px;color:var(--admin-text-secondary);align-self:center">CNY → BYN</span>
    </div>

    <div class="admin-calculator-body">

        <!-- Режим 1: Простая конвертация CNY → BYN -->
        <div id="calc-mode-1">
            <div class="admin-form-group">
                <label class="admin-form-label">Цена в CNY</label>
                <input type="number" id="m1-cny" class="admin-form-input" placeholder="0" step="1" autofocus>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Курс CNY/BYN</label>
                <input type="number" id="m1-rate" class="admin-form-input" value="0.45" step="0.001">
            </div>
            <div class="admin-calculator-result">
                <div class="admin-calculator-result-row total"><span>= BYN:</span><span id="m1-result">0.00 BYN</span></div>
            </div>
        </div>

        <!-- Режим 2: Полная стоимость (закупка + доставка + таможня + маржа) -->
        <div id="calc-mode-2" style="display:none">
            <div class="admin-calculator-row">
                <div class="admin-form-group">
                    <label class="admin-form-label">Цена покупки, CNY</label>
                    <input type="number" id="m2-cny" class="admin-form-input" placeholder="0" step="1">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Курс CNY/BYN</label>
                    <input type="number" id="m2-rate" class="admin-form-input" value="0.45" step="0.001">
                </div>
            </div>
            <div class="admin-calculator-row">
                <div class="admin-form-group">
                    <label class="admin-form-label">Доставка из Китая, CNY</label>
                    <input type="number" id="m2-ship-cny" class="admin-form-input" value="15" step="1">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Таможня / сборы, BYN</label>
                    <input type="number" id="m2-customs" class="admin-form-input" value="0" step="1">
                </div>
            </div>
            <div class="admin-calculator-row">
                <div class="admin-form-group">
                    <label class="admin-form-label">Доставка по РБ, BYN</label>
                    <input type="number" id="m2-ship-byn" class="admin-form-input" value="8" step="0.5">
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">Маржа, %</label>
                    <input type="number" id="m2-margin" class="admin-form-input" value="25" step="1">
                </div>
            </div>
            <div class="admin-calculator-result">
                <div class="admin-calculator-result-row"><span>Товар (BYN):</span><span id="m2-r-prod">0</span></div>
                <div class="admin-calculator-result-row"><span>Доставка Китай:</span><span id="m2-r-ship">0</span></div>
                <div class="admin-calculator-result-row"><span>Таможня:</span><span id="m2-r-customs">0</span></div>
                <div class="admin-calculator-result-row"><span>Доставка РБ:</span><span id="m2-r-local">0</span></div>
                <div class="admin-calculator-result-row"><span>Себестоимость:</span><span id="m2-r-cost">0</span></div>
                <div class="admin-calculator-result-row"><span>Маржа:</span><span id="m2-r-margin">0</span></div>
                <div class="admin-calculator-result-row total"><span>Цена продажи:</span><span id="m2-r-total">0 BYN</span></div>
            </div>
        </div>

        <!-- Режим 3: Стоимость доставки -->
        <div id="calc-mode-3" style="display:none">
            <div class="admin-form-group">
                <label class="admin-form-label">Вес посылки, кг</label>
                <input type="number" id="m3-weight" class="admin-form-input" placeholder="0.5" step="0.1" min="0.1">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Служба доставки</label>
                <select id="m3-provider" class="admin-form-input">
                    <option value="europochta">Европочта (6.50 BYN + 0.80/кг)</option>
                    <option value="belpochta">Белпочта (4.00 BYN + 0.60/кг)</option>
                    <option value="cdek">СДЭК (7.00 BYN + 1.20/кг)</option>
                    <option value="courier">Курьер Минск (8.00 BYN фикс.)</option>
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Объявленная стоимость, BYN</label>
                <input type="number" id="m3-value" class="admin-form-input" placeholder="0" step="1">
            </div>
            <div class="admin-calculator-result">
                <div class="admin-calculator-result-row"><span>Стоимость пересылки:</span><span id="m3-r-base">0</span></div>
                <div class="admin-calculator-result-row"><span>Страховка (1%):</span><span id="m3-r-ins">0</span></div>
                <div class="admin-calculator-result-row total"><span>Итого доставка:</span><span id="m3-r-total">0 BYN</span></div>
            </div>
        </div>

        <!-- Режим 4: Маржинальность -->
        <div id="calc-mode-4" style="display:none">
            <div class="admin-form-group">
                <label class="admin-form-label">Себестоимость, BYN</label>
                <input type="number" id="m4-cost" class="admin-form-input" placeholder="0" step="1">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Цена продажи, BYN</label>
                <input type="number" id="m4-price" class="admin-form-input" placeholder="0" step="1">
            </div>
            <div class="admin-calculator-result">
                <div class="admin-calculator-result-row"><span>Прибыль:</span><span id="m4-r-profit">0 BYN</span></div>
                <div class="admin-calculator-result-row"><span>Маржа (%):</span><span id="m4-r-margin">0%</span></div>
                <div class="admin-calculator-result-row"><span>Markup (%):</span><span id="m4-r-markup">0%</span></div>
                <div class="admin-calculator-result-row total"><span>ROI:</span><span id="m4-r-roi">0%</span></div>
            </div>
        </div>

        <div style="margin-top:12px">
            <button class="admin-btn admin-btn-secondary" style="width:100%" onclick="resetCalcMode()"><i class="bi bi-arrow-counterclockwise"></i> Сбросить</button>
        </div>
    </div>
</div>

<style>
.calc-tab-btn{width:32px;height:32px;border-radius:8px;border:1.5px solid var(--admin-border);background:transparent;font-size:13px;font-weight:700;cursor:pointer;color:var(--admin-text-secondary);transition:all .15s}
.calc-tab-btn.active{background:var(--admin-primary,#2563eb);border-color:var(--admin-primary,#2563eb);color:#fff}
</style>

<script>
(function(){
    var currentMode = 1;
    var modeLabels = {1:'CNY → BYN', 2:'Полная стоимость', 3:'Стоимость доставки', 4:'Маржа'};

    window.switchCalcMode = function(mode) {
        currentMode = mode;
        [1,2,3,4].forEach(function(m) {
            var el = document.getElementById('calc-mode-'+m);
            var tab = document.getElementById('calc-tab-'+m);
            if (el) el.style.display = m === mode ? '' : 'none';
            if (tab) tab.classList.toggle('active', m === mode);
        });
        var lbl = document.getElementById('calc-mode-label');
        if (lbl) lbl.textContent = modeLabels[mode] || '';
        runCalc();
    };

    function runCalc() {
        if (currentMode === 1) calcMode1();
        else if (currentMode === 2) calcMode2();
        else if (currentMode === 3) calcMode3();
        else if (currentMode === 4) calcMode4();
    }

    function v(id) { return parseFloat(document.getElementById(id)?.value) || 0; }
    function fmt(n) { return n.toFixed(2) + ' BYN'; }
    function set(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; }

    function calcMode1() {
        var r = v('m1-cny') * (v('m1-rate') || 0.45);
        set('m1-result', fmt(r));
    }

    function calcMode2() {
        var rate = v('m2-rate') || 0.45;
        var prod = v('m2-cny') * rate;
        var ship = v('m2-ship-cny') * rate;
        var cust = v('m2-customs');
        var local = v('m2-ship-byn');
        var cost = prod + ship + cust + local;
        var margin = v('m2-margin');
        var marginAmt = cost * (margin / 100);
        var total = cost + marginAmt;
        set('m2-r-prod', fmt(prod)); set('m2-r-ship', fmt(ship));
        set('m2-r-customs', fmt(cust)); set('m2-r-local', fmt(local));
        set('m2-r-cost', fmt(cost)); set('m2-r-margin', fmt(marginAmt));
        set('m2-r-total', fmt(total));
    }

    function calcMode3() {
        var weight = v('m3-weight') || 0.5;
        var value = v('m3-value');
        var provider = document.getElementById('m3-provider')?.value || 'europochta';
        var rates = {europochta:[6.50,0.80], belpochta:[4.00,0.60], cdek:[7.00,1.20], courier:[8.00,0]};
        var r = rates[provider] || rates.europochta;
        var base = r[0] + weight * r[1];
        var ins = value * 0.01;
        set('m3-r-base', fmt(base)); set('m3-r-ins', fmt(ins));
        set('m3-r-total', fmt(base + ins));
    }

    function calcMode4() {
        var cost = v('m4-cost');
        var price = v('m4-price');
        var profit = price - cost;
        var margin = price > 0 ? profit / price * 100 : 0;
        var markup = cost > 0 ? profit / cost * 100 : 0;
        var roi = cost > 0 ? profit / cost * 100 : 0;
        set('m4-r-profit', fmt(profit));
        set('m4-r-margin', margin.toFixed(1) + '%');
        set('m4-r-markup', markup.toFixed(1) + '%');
        set('m4-r-roi', roi.toFixed(1) + '%');
    }

    // Bind inputs
    document.addEventListener('DOMContentLoaded', function() {
        ['m1-cny','m1-rate','m2-cny','m2-rate','m2-ship-cny','m2-customs','m2-ship-byn','m2-margin',
         'm3-weight','m3-value','m3-provider','m4-cost','m4-price'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', runCalc);
        });
    });

    window.openCalculator = function() {
        document.getElementById('calculator-overlay').classList.add('active');
        document.getElementById('calculator-drawer').classList.add('active');
        document.body.style.overflow = 'hidden';
        var first = document.getElementById('m1-cny');
        if (first) setTimeout(function(){ first.focus(); }, 100);
    };
    window.closeCalculator = function() {
        document.getElementById('calculator-overlay').classList.remove('active');
        document.getElementById('calculator-drawer').classList.remove('active');
        document.body.style.overflow = '';
    };
    window.resetCalcMode = function() {
        ['m1-cny','m2-cny','m2-customs','m2-ship-byn','m3-value','m4-cost','m4-price'].forEach(function(id) {
            var el = document.getElementById(id); if (el) el.value = '';
        });
        runCalc();
    };
    window.resetCalculator = window.resetCalcMode;
})();
</script>

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

function toggleSubmenu(btn) {
    var g = btn.parentElement;
    g.classList.toggle('active');
    var sub = g.querySelector('.admin-nav-submenu');
    if (sub) sub.classList.toggle('open');
}
</script>


<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>


