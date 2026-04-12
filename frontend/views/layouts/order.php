<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\frontend\assets\PublicAsset;
use yii\bootstrap5\Html;

PublicAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= Yii::$app->request->baseUrl ?>/favicon.ico">
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<main role="main" class="flex-shrink-0">
    <?= $content ?>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
