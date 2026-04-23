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
    <?= $this->head() ?>
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
                        ['label' => 'RFM сегменты', 'url' => '/admin/analytics/rfm', 'ids' => ['rfm']]
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
                        ['label' => 'Маркетинг', 'url' => '/admin/marketing', 'ids' => ['marketing']],
                        ['label' => 'Маркетинговые кампании', 'url' => '/admin/marketing/campaigns', 'ids' => ['marketing']]
                    ]
                ],
                
                // � ИНТЕГРАЦИИ
                [
                    'label' => 'Интеграции',
                    'icon' => 'bi-plugin',
                    'items' => [
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
// Инициализация калькулятора (если не загружен admin-search.js)
(function() {
    if (!window.openCalculator) {
        const calcOverlay = document.createElement('div');
        calcOverlay.className = 'admin-calculator-overlay';
        calcOverlay.id = 'calculator-overlay';
        document.body.appendChild(calcOverlay);

        const calcDrawer = document.createElement('div');
        calcDrawer.className = 'admin-calculator-drawer';
        calcDrawer.id = 'calculator-drawer';
        calcDrawer.innerHTML = `
            <div class="admin-calculator-header">
                <h3><i class="bi bi-calculator"></i> Калькулятор цены</h3>
                <button class="admin-btn admin-btn-secondary" onclick="closeCalculator()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="admin-calculator-body">
                <div class="admin-form-group">
                    <label class="admin-form-label">Цена в CNY (юанях)</label>
                    <input type="number" id="calc-cny" class="admin-form-input" placeholder="0.00" step="0.01">
                </div>
                <div class="admin-calculator-row">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Курс CNY/BYN</label>
                        <input type="number" id="calc-rate" class="admin-form-input" value="0.45" step="0.001">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Доставка CNY</label>
                        <input type="number" id="calc-shipping" class="admin-form-input" value="15" step="1">
                    </div>
                </div>
                <div class="admin-calculator-row">
                    <div class="admin-form-group">
                        <label class="admin-form-label">Комиссия (%)</label>
                        <input type="number" id="calc-commission" class="admin-form-input" value="15" step="1">
                    </div>
                    <div class="admin-form-group">
                        <label class="admin-form-label">Доставка РБ (BYN)</label>
                        <input type="number" id="calc-local-shipping" class="admin-form-input" value="8" step="0.5">
                    </div>
                </div>
                <div class="admin-calculator-result">
                    <div class="admin-calculator-result-row">
                        <span>Цена товара:</span>
                        <span id="res-product">0 BYN</span>
                    </div>
                    <div class="admin-calculator-result-row">
                        <span>Доставка из Китая:</span>
                        <span id="res-shipping">0 BYN</span>
                    </div>
                    <div class="admin-calculator-result-row">
                        <span>Комиссия:</span>
                        <span id="res-commission">0 BYN</span>
                    </div>
                    <div class="admin-calculator-result-row">
                        <span>Доставка по РБ:</span>
                        <span id="res-local">0 BYN</span>
                    </div>
                    <div class="admin-calculator-result-row total">
                        <span>ИТОГО:</span>
                        <span id="res-total">0 BYN</span>
                    </div>
                </div>
                <div style="margin-top: 24px;">
                    <button class="admin-btn admin-btn-primary w-100" onclick="resetCalculator()">
                        <i class="bi bi-arrow-counterclockwise"></i> Сбросить
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(calcDrawer);

        window.openCalculator = function() {
            calcOverlay.classList.add('active');
            calcDrawer.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        window.closeCalculator = function() {
            calcOverlay.classList.remove('active');
            calcDrawer.classList.remove('active');
            document.body.style.overflow = '';
        };

        window.resetCalculator = function() {
            document.getElementById('calc-cny').value = '';
            document.getElementById('calc-rate').value = '0.45';
            document.getElementById('calc-shipping').value = '15';
            document.getElementById('calc-commission').value = '15';
            document.getElementById('calc-local-shipping').value = '8';
            calculatePrice();
        };

        function calculatePrice() {
            const cny = parseFloat(document.getElementById('calc-cny')?.value) || 0;
            const rate = parseFloat(document.getElementById('calc-rate')?.value) || 0.45;
            const shippingCny = parseFloat(document.getElementById('calc-shipping')?.value) || 0;
            const commissionPct = parseFloat(document.getElementById('calc-commission')?.value) || 0;
            const localShipping = parseFloat(document.getElementById('calc-local-shipping')?.value) || 0;

            const productByn = cny * rate;
            const shippingByn = shippingCny * rate;
            const subtotal = productByn + shippingByn;
            const commission = subtotal * (commissionPct / 100);
            const total = subtotal + commission + localShipping;

            document.getElementById('res-product').textContent = productByn.toFixed(2) + ' BYN';
            document.getElementById('res-shipping').textContent = shippingByn.toFixed(2) + ' BYN';
            document.getElementById('res-commission').textContent = commission.toFixed(2) + ' BYN';
            document.getElementById('res-local').textContent = localShipping.toFixed(2) + ' BYN';
            document.getElementById('res-total').textContent = total.toFixed(2) + ' BYN';
        }

        ['calc-cny', 'calc-rate', 'calc-shipping', 'calc-commission', 'calc-local-shipping'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', calculatePrice);
        });

        calcOverlay.addEventListener('click', closeCalculator);
    }
})();
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
