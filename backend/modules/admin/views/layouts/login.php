<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="/css/core/design-tokens.css">
    <link rel="stylesheet" href="/css/admin-tokens.css">
    <link rel="stylesheet" href="/css/admin-shopify-2026.css">

    <?php $this->head() ?>
    
    <?php // Отключаем debug toolbar для страницы входа ?>
    <?php if (class_exists('yii\debug\Module')): ?>
        <style>
            .yii-debug-toolbar { display: none !important; }
        </style>
    <?php endif; ?>
    
</head>
<body>
<?php $this->beginBody() ?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">SNEAKERHEAD</div>
            <h1 class="login-title">Вход в админ-панель</h1>
            <p class="login-subtitle">Введите данные для доступа</p>
        </div>
        
        <div class="login-content">
            <?= $content ?>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
