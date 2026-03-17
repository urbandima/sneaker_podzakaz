<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\admin\assets\AdminAsset;

AdminAsset::register($this);

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
    
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Preload критических ресурсов -->
    <link rel="preload" href="/css/dist/admin-bundle.min.css" as="style">
    <link rel="preload" href="/css/pages/admin.css" as="style">
    
    <!-- DNS prefetch для внешних ресурсов -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    
    <!-- Preconnect для шрифтов -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <!-- Стили для сайдбара -->
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 80px !important;
            background: #000000 !important;
            border-right: 1px solid #333333 !important;
            padding: 1.5rem 0.5rem !important;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .admin-sidebar .logo {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .admin-sidebar .logo h1 {
            font-size: 0.75rem !important;
            font-weight: 700;
            color: #ffffff !important;
            margin: 0;
            line-height: 1.2;
        }
        
        .admin-sidebar .logo p {
            font-size: 0.625rem !important;
            color: #cccccc !important;
            margin: 0.25rem 0 0;
            line-height: 1.2;
        }
        
        /* Переопределяем стили из admin.css для вертикального сайдбара */
        .admin-sidebar .admin-nav {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.5rem !important;
        }
        
        .admin-sidebar .nav-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 0.25rem !important;
            padding: 0.75rem 0.5rem !important;
            text-decoration: none !important;
            color: #ffffff !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
            position: relative;
        }
        
        .admin-sidebar .nav-item:hover {
            background: #333333 !important;
        }
        
        .admin-sidebar .nav-item:hover span {
            display: none !important;
        }
        
        .admin-sidebar .nav-item.active {
            background: #3b82f6 !important;
            color: #ffffff !important;
        }
        
        .admin-sidebar .nav-item.active span {
            display: none !important;
        }
        
        .admin-sidebar .nav-item i {
            font-size: 1.25rem !important;
            color: #ffffff !important;
        }
        
        .admin-sidebar .nav-item span {
            font-size: 0.625rem !important;
            font-weight: 500 !important;
            color: #ffffff !important;
            line-height: 1.1;
            text-align: center;
        }
        
        .admin-main {
            margin-left: 80px !important;
            flex: 1;
            background: #f8fafc;
            min-height: 100vh;
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="logo">
            <h1><?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?></h1>
            <p>Админ-панель</p>
        </div>
        
        <nav class="admin-nav">
            <a href="<?= Url::to(['/admin']) ?>" class="nav-item <?= Yii::$app->controller->id === 'dashboard' ? 'active' : '' ?>" data-tooltip="Главная">
                <span>Главная</span>
                <i class="bi bi-speedometer2"></i>
            </a>
            <a href="<?= Url::to(['/admin/order']) ?>" class="nav-item <?= Yii::$app->controller->id === 'order' ? 'active' : '' ?>" data-tooltip="Заказы">
                <span>Заказы</span>
                <i class="bi bi-cart3"></i>
            </a>
            <a href="<?= Url::to(['/admin/product']) ?>" class="nav-item <?= Yii::$app->controller->id === 'product' ? 'active' : '' ?>" data-tooltip="Товары">
                <span>Товары</span>
                <i class="bi bi-box"></i>
            </a>
            <a href="<?= Url::to(['/admin/user']) ?>" class="nav-item <?= Yii::$app->controller->id === 'user' ? 'active' : '' ?>" data-tooltip="Пользователи">
                <span>Пользователи</span>
                <i class="bi bi-people"></i>
            </a>
            <a href="<?= Url::to(['/admin/settings']) ?>" class="nav-item <?= Yii::$app->controller->id === 'settings' ? 'active' : '' ?>" data-tooltip="Настройки">
                <span>Настройки</span>
                <i class="bi bi-gear"></i>
            </a>
            <a href="<?= Url::to(['/admin/logout']) ?>" class="nav-item" data-tooltip="Выйти">
                <span>Выйти</span>
                <i class="bi bi-box-arrow-right"></i>
            </a>
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
