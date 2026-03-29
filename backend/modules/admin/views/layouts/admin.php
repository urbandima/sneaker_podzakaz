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
    <link href="/css/admin.css?v=<?= file_exists(Yii::getAlias('@webroot') . '/css/admin.css') ? filemtime(Yii::getAlias('@webroot') . '/css/admin.css') : time() ?>" rel="stylesheet">
    
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
                ['label' => 'Аналитика', 'url' => '/admin/statistics', 'icon' => 'bi-bar-chart-line-fill', 'ids' => ['statistics']],
                ['label' => 'Маркетинг', 'url' => '/admin/marketing', 'icon' => 'bi-megaphone-fill', 'ids' => ['marketing']],
                ['label' => 'POS-Терминал', 'url' => '/admin/pos', 'icon' => 'bi-shop', 'ids' => ['pos']],
                ['label' => 'Плагины', 'url' => '/admin/plugin', 'icon' => 'bi-plugin', 'ids' => ['plugin']],
                ['label' => 'Настройки', 'url' => '/admin/settings', 'icon' => 'bi-gear-wide-connected', 'ids' => ['settings']],
                ['label' => 'SEO', 'url' => '/admin/seo', 'icon' => 'bi-search-heart', 'ids' => ['seo']],
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
                <button class="admin-topbar-icon-btn" id="calc-open-btn" title="Калькулятор стоимости">
                    <i class="bi bi-calculator-fill"></i>
                </button>
                <!-- Уведомления -->
                <button class="admin-topbar-icon-btn admin-notif-btn" id="notif-btn" title="Уведомления" onclick="window.location.href='/admin/order?status=created'">
                    <i class="bi bi-bell-fill"></i>
                    <span class="admin-notif-badge" id="notif-badge" style="display:none"></span>
                </button>
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

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
