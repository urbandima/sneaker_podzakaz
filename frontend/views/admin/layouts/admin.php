<?php

use yii\helpers\Html;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

$company = Yii::$app->settings->getCompany();
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <?php // Preload критических ресурсов ?>
    <link rel="preload" href="/css/dist/admin-bundle.min.css" as="style">
    <link rel="preload" href="/css/pages/admin.css" as="style">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" as="style">
    
    <?php // Bootstrap Icons CSS ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <?php // DNS prefetch для внешних ресурсов ?>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    
    <?php // Preconnect для шрифтов ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <?php // Оптимизация Bootstrap Icons с font-display: swap ?>
    <style>
        @font-face {
            font-family: 'bootstrap-icons';
            src: url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/fonts/bootstrap-icons.woff2') format('woff2');
            font-display: swap;
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>

<!-- Skip to main content (a11y) -->
<a href="#main-content" class="skip-link">Перейти к основному содержимому</a>

<!-- Admin Header -->
<header class="admin-header">
    <div class="admin-container">
        <div class="admin-header-content">
            <!-- Logo -->
            <a href="/admin" class="admin-logo">
                <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
                <span class="admin-logo-text">Панель управления</span>
            </a>
            
            <!-- Admin Navigation -->
            <nav class="admin-nav" role="navigation">
                <ul class="admin-nav-menu">
                    <li><a href="/admin" class="<?php if (Yii::$app->controller->id === 'dashboard') echo 'active'; ?>">📊 Dashboard</a></li>
                    <li><a href="/admin/orders" class="<?php if (Yii::$app->controller->id === 'order') echo 'active'; ?>">📦 Заказы</a></li>
                    <li><a href="/admin/products" class="<?php if (Yii::$app->controller->id === 'product') echo 'active'; ?>">👟 Товары</a></li>
                    <li><a href="/admin/users" class="<?php if (Yii::$app->controller->id === 'user') echo 'active'; ?>">👤 Пользователи</a></li>
                    <li><a href="/admin/settings" class="<?php if (Yii::$app->controller->id === 'setting') echo 'active'; ?>">⚙️ Настройки</a></li>
                </ul>
            </nav>
            
            <!-- Actions -->
            <div class="admin-header-actions">
                <a href="/" class="btn btn-secondary" target="_blank">
                    <i class="bi bi-globe" aria-hidden="true"></i>
                    Сайт
                </a>
                <button class="btn btn-accent" onclick="toggleDarkMode()" aria-label="Переключить тему">
                    <i class="bi bi-moon" id="theme-icon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<main id="main-content" class="admin-main">
    <?= $content ?>
</main>

<!-- Подключаем Dark Mode JS -->
<?= Html::jsFile('@web/js/dark-mode.js', ['position' => \yii\web\View::POS_END]) ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
