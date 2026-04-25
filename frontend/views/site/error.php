<?php
/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
use yii\helpers\Url;

$statusCode = Yii::$app->response->statusCode;

$ruTitles = [
    400 => 'Некорректный запрос',
    403 => 'Доступ запрещён',
    404 => 'Страница не найдена',
    405 => 'Метод не поддерживается',
    410 => 'Страница удалена',
    422 => 'Ошибка данных',
    429 => 'Слишком много запросов',
    500 => 'Ошибка сервера',
    503 => 'Сервис недоступен',
];
$ruTitle = $ruTitles[$statusCode] ?? 'Произошла ошибка';
$this->title = $ruTitle;

$this->registerCssFile('@web/css/page-404.css', ['depends' => [\app\frontend\assets\AppAsset::class]]);

$company = Yii::$app->settings->getCompany() ?? [];
$phone   = $company['phone'] ?? '+375 (29) 123-45-67';
$email   = $company['email'] ?? 'info@sneakerhead.by';
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

        <h1 class="error-title"><?= Html::encode($ruTitle) ?></h1>

        <p class="error-description">
            <?php if (!empty($message)): ?>
                <?= nl2br(Html::encode($message)) ?>
            <?php elseif ($statusCode == 404): ?>
                Страница не существует или была перемещена.
            <?php elseif ($statusCode == 403): ?>
                У вас нет доступа к этой странице.
            <?php else: ?>
                Произошла ошибка. Мы уже работаем над устранением.
            <?php endif; ?>
        </p>

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
            <a href="tel:<?= preg_replace('/[^+\d]/', '', $phone) ?>"><i class="bi bi-telephone"></i> <?= Html::encode($phone) ?></a>
            <a href="mailto:<?= Html::encode($email) ?>"><i class="bi bi-envelope"></i> <?= Html::encode($email) ?></a>
        </div>

    </div>
</div>
