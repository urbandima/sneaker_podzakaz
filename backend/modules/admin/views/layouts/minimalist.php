<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\admin\assets\AdminAsset;

AdminAsset::register($this);

$company = Yii::$app->settings->getCompany();
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="admin-layout">
<?php $this->beginBody() ?>

<!-- Admin Layout -->
<div class="admin-layout">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="/admin" class="admin-logo">
                ADMIN
            </a>
        </div>
        <nav class="admin-sidebar-nav">
            <div class="admin-nav-section">
                <div class="admin-nav-title">Основное</div>
                <a href="/admin/dashboard" class="admin-nav-link">
                    <span class="admin-nav-icon">📊</span>
                    Dashboard
                </a>
                <a href="/admin/orders" class="admin-nav-link">
                    <span class="admin-nav-icon">📦</span>
                    Заказы
                </a>
                <a href="/admin/products" class="admin-nav-link">
                    <span class="admin-nav-icon">👟</span>
                    Товары
                </a>
                <a href="/admin/customers" class="admin-nav-link">
                    <span class="admin-nav-icon">👥</span>
                    Клиенты
                </a>
            </div>
            
            <div class="admin-nav-section">
                <div class="admin-nav-title">Каталог</div>
                <a href="/admin/catalog" class="admin-nav-link">
                    <span class="admin-nav-icon">📋</span>
                    Каталог
                </a>
                <a href="/admin/brands" class="admin-nav-link">
                    <span class="admin-nav-icon">🏷️</span>
                    Бренды
                </a>
                <a href="/admin/categories" class="admin-nav-link">
                    <span class="admin-nav-icon">📁</span>
                    Категории
                </a>
            </div>
            
            <div class="admin-nav-section">
                <div class="admin-nav-title">Маркетинг</div>
                <a href="/admin/coupons" class="admin-nav-link">
                    <span class="admin-nav-icon">🎫</span>
                    Купоны
                </a>
                <a href="/admin/loyalty" class="admin-nav-link">
                    <span class="admin-nav-icon">⭐</span>
                    Лояльность
                </a>
            </div>
            
            <div class="admin-nav-section">
                <div class="admin-nav-title">Сервис</div>
                <a href="/admin/analytics" class="admin-nav-link">
                    <span class="admin-nav-icon">📈</span>
                    Аналитика
                </a>
                <a href="/admin/settings" class="admin-nav-link">
                    <span class="admin-nav-icon">⚙️</span>
                    Настройки
                </a>
                <a href="/admin/import" class="admin-nav-link">
                    <span class="admin-nav-icon">📥</span>
                    Импорт
                </a>
            </div>
        </nav>
    </aside>

    <!-- Admin Main -->
    <main class="admin-main">
        <!-- Admin Header -->
        <header class="admin-header">
            <div class="admin-header-left">
                <h1 class="admin-title"><?= Html::encode($this->title) ?></h1>
                <div class="admin-breadcrumb">
                    <a href="/admin">Админка</a>
                    <span>›</span>
                    <span><?= Html::encode($this->title) ?></span>
                </div>
            </div>
            <div class="admin-header-right">
                <a href="/" class="btn btn-secondary btn-sm" target="_blank">
                    <i class="bi bi-eye"></i>
                    Просмотр сайта
                </a>
                <a href="/logout" class="btn btn-primary btn-sm">
                    <i class="bi bi-box-arrow-right"></i>
                    Выход
                </a>
            </div>
        </header>

        <!-- Admin Content -->
        <div class="admin-content">
            <?= $content ?>
        </div>
    </main>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
