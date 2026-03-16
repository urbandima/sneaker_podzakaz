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
                <i class="bi bi-speedometer2"></i>
                <span>Главная</span>
            </a>
            <a href="<?= Url::to(['/admin/order']) ?>" class="nav-item <?= Yii::$app->controller->id === 'order' ? 'active' : '' ?>" data-tooltip="Заказы">
                <i class="bi bi-cart3"></i>
                <span>Заказы</span>
            </a>
            <a href="<?= Url::to(['/admin/product']) ?>" class="nav-item <?= Yii::$app->controller->id === 'product' ? 'active' : '' ?>" data-tooltip="Товары">
                <i class="bi bi-box"></i>
                <span>Товары</span>
            </a>
            <a href="<?= Url::to(['/admin/user']) ?>" class="nav-item <?= Yii::$app->controller->id === 'user' ? 'active' : '' ?>" data-tooltip="Пользователи">
                <i class="bi bi-people"></i>
                <span>Пользователи</span>
            </a>
            <a href="<?= Url::to(['/admin/settings']) ?>" class="nav-item <?= Yii::$app->controller->id === 'settings' ? 'active' : '' ?>" data-tooltip="Настройки">
                <i class="bi bi-gear"></i>
                <span>Настройки</span>
            </a>
            <a href="<?= Url::to(['/admin/logout']) ?>" class="nav-item" data-tooltip="Выйти">
                <i class="bi bi-box-arrow-right"></i>
                <span>Выйти</span>
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
