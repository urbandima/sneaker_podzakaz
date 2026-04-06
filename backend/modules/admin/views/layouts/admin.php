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
    <div class="admin-sidebar-overlay" id="sidebar-overlay" onclick="closeMobileSidebar()" style="display: none;"></div>
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <!-- Mobile Close Button -->
        <button class="admin-sidebar-close" id="sidebar-close" onclick="closeMobileSidebar()" style="display: none;" title="Закрыть меню">
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
                
                // 💻 ИНТЕГРАЦИИ
                [
                    'label' => 'Интеграции',
                    'icon' => 'bi-plugin',
                    'items' => [
                        ['label' => 'POS-Терминал', 'url' => '/admin/pos', 'ids' => ['pos']],
                        ['label' => 'Плагины', 'url' => '/admin/plugin', 'ids' => ['plugin']],
                        ['label' => 'Импорт/Экспорт', 'url' => '/admin/import', 'ids' => ['import']]
                    ]
                ],
                
                // ⚙️ УПРАВЛЕНИЕ
                [
                    'label' => 'Управление',
                    'icon' => 'bi-gear-wide-connected',
                    'items' => [
                        ['label' => 'Настройки', 'url' => '/admin/settings', 'ids' => ['settings']],
                        ['label' => 'Боковое меню', 'url' => '/admin/sidebar-menu', 'ids' => ['sidebar-menu']]
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
                <button class="admin-mobile-menu-btn" id="mobile-menu-toggle" onclick="toggleMobileSidebar()" style="display: none;" title="Открыть меню">
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

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
