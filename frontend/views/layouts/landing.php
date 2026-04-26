<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\LandingAsset;
use yii\bootstrap5\Html;

LandingAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Yii::$app->request->baseUrl ?>/favicon.ico">
    
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<!-- LANDING WITHOUT MENU -->
<main role="main" class="flex-shrink-0">
    <div class="container-fluid p-0">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 1rem;">
                <?= Yii::$app->session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 1rem;">
                <?= Yii::$app->session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('warning')): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin: 1rem;">
                <?= Html::encode(Yii::$app->session->getFlash('warning')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
