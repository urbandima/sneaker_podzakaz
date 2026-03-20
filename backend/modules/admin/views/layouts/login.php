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
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Admin CSS -->
    <link href="/css/admin.css?v=<?= time() ?>" rel="stylesheet">
    
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
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
        
        <div class="login-footer">
            <a href="/" class="login-back-link">
                <i class="bi bi-arrow-left"></i>
                Вернуться на сайт
            </a>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
