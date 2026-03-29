<?php

use yii\helpers\Html;
use yii\helpers\Url;

$company = Yii::$app->settings->getCompany() ?? ['name' => 'СНИКЕРХЭД'];
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Admin CSS -->
    <link href="/css/admin.css?v=<?= time() ?>" rel="stylesheet">
    
    <?php // Отключаем debug toolbar для админки ?>
    <?php if (class_exists('yii\debug\Module')): ?>
        <style>
            .yii-debug-toolbar { display: none !important; }
        </style>
    <?php endif; ?>
    
    </head>
<body>
<?php $this->beginBody() ?>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="<?= Url::to(['/admin']) ?>" class="admin-sidebar-logo">
                <?= Html::encode($company['name']) ?>
            </a>
        </div>
        
        <nav class="admin-sidebar-nav">
            <a href="<?= Url::to(['/admin']) ?>" class="admin-nav-item <?= Yii::$app->controller->id === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Главная</span>
            </a>
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
        </nav>
    </aside>

    <!-- Admin Main Content -->
    <main class="admin-main">
        <?= $content ?>
    </main>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
