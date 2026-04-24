<?php
/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $name;
$statusCode = Yii::$app->response->statusCode;

$this->registerCssFile('@web/css/page-404.css', ['depends' => [\app\frontend\assets\AppAsset::class]]);
?>

<div class="error-page">
    <div class="error-inner">

        <div class="error-icon-wrap">
            <?php if ($statusCode == 404): ?>
                <i class="bi bi-search error-icon--404"></i>
            <?php elseif ($statusCode == 403): ?>
                <i class="bi bi-lock error-icon--403"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-triangle error-icon--default"></i>
            <?php endif; ?>
        </div>

        <div class="error-code"><?= $statusCode ?></div>

        <h1 class="error-title"><?= Html::encode($name) ?></h1>

        <?php if (YII_ENV_DEV && !empty($message)): ?>
            <div class="error-dev-message">
                <strong>Детали (dev):</strong><br>
                <?= nl2br(Html::encode($message)) ?>
            </div>
        <?php else: ?>
            <p class="error-description">
                <?php if ($statusCode == 404): ?>
                    Страница не существует или была перемещена.
                <?php elseif ($statusCode == 403): ?>
                    У вас нет доступа к этой странице.
                <?php else: ?>
                    Внутренняя ошибка сервера. Мы уже работаем над устранением.
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <div class="error-actions">
            <a href="<?= Url::to(['/']) ?>" class="error-btn error-btn--primary">
                <i class="bi bi-house-door"></i> На главную
            </a>
            <a href="<?= Url::to(['/catalog']) ?>" class="error-btn error-btn--secondary">
                <i class="bi bi-grid"></i> Каталог
            </a>
        </div>

        <div class="error-links">
            <a href="<?= Url::to(['/page/contacts']) ?>"><i class="bi bi-envelope"></i> Контакты</a>
            <a href="<?= Url::to(['/page/delivery-terms']) ?>"><i class="bi bi-truck"></i> Доставка</a>
            <a href="<?= Url::to(['/page/return-policy']) ?>"><i class="bi bi-arrow-return-left"></i> Возврат</a>
        </div>

        <div class="error-contact">
            <span class="error-contact__label">Нужна помощь?</span>
            <a href="tel:+375291234567"><i class="bi bi-telephone"></i> +375 (29) 123-45-67</a>
            <a href="mailto:info@sneakerhead.by"><i class="bi bi-envelope"></i> info@sneakerhead.by</a>
        </div>

    </div>
</div>
