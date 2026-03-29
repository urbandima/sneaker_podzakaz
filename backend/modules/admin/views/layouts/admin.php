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
<<<<<<< HEAD
            <?php endforeach ?>

            <div class="admin-nav-divider"></div>
            <a href="<?= Url::to(['/']) ?>" class="admin-nav-item admin-nav-muted" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>На сайт</span>
            </a>
            <a href="<?= Url::to(['/admin/logout']) ?>" class="admin-nav-item admin-nav-danger">
                <i class="bi bi-power"></i>
                <span>Выйти</span>
            </a>
=======
            <a href="<?= Url::to(['/admin/order']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'order' ? 'active' : '' ?>">
                <i class="bi bi-cart"></i>
                <span>Заказы</span>
            </a>
            <a href="<?= Url::to(['/admin/catalog']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'catalog' || Yii::$app->controller->id === 'product' ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                <span>Каталог</span>
            </a>
            <a href="<?= Url::to(['/admin/customer']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'customer' ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Клиенты</span>
            </a>
            <a href="<?= Url::to(['/admin/coupon']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'coupon' ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                <span>Купоны</span>
            </a>
            <a href="<?= Url::to(['/admin/return']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'return' ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                <span>Возвраты</span>
            </a>
            <a href="<?= Url::to(['/admin/statistics']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'statistics' ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i>
                <span>Статистика</span>
            </a>
            <a href="<?= Url::to(['/admin/seo']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'seo' ? 'active' : '' ?>">
                <i class="bi bi-globe"></i>
                <span>SEO</span>
            </a>
            <a href="<?= Url::to(['/admin/settings']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'settings' ? 'active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span>Настройки</span>
            </a>
            
            <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid var(--admin-primary-soft);">
                <a href="<?= Url::to(['/']) ?>" class="admin-nav-item" style="color: var(--admin-text-secondary);">
                    <i class="bi bi-box"></i>
                    <span>На сайт</span>
                </a>
                <a href="<?= Url::to(['/admin/logout']) ?>" class="admin-nav-item" style="color: var(--admin-danger);">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Выйти</span>
                </a>
            </div>
>>>>>>> ec865664631629b4456c8e8f342b0b9945f48065
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

        <?= $content ?>
    </main>
</div>



<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
