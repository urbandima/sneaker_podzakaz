<?php

use yii\helpers\Html;
use yii\helpers\Url;

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
    
    <!-- Admin CSS -->
    <link href="/css/admin.css?v=<?= time() ?>" rel="stylesheet">
    
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
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
