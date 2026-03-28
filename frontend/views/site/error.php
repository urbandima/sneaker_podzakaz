<?php
/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $name;
$statusCode = Yii::$app->response->statusCode;
?>

<div class="error-page-wrapper">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="error-content text-center">
                    
                    <!-- Иконка ошибки -->
                    <div class="error-icon mb-4">
                        <?php if ($statusCode == 404): ?>
                            <i class="bi bi-search" style="font-size: 5rem; color: #8b5cf6;"></i>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle" style="font-size: 5rem; color: #ef4444;"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Код ошибки -->
                    <h1 class="error-code display-1 fw-bold mb-3" style="color: #1a1a1a;">
                        <?= $statusCode ?>
                    </h1>

                    <!-- Заголовок -->
                    <h2 class="error-title h3 mb-3" style="color: #374151;">
                        <?= Html::encode($name) ?>
                    </h2>

                    <!-- Сообщение -->
                    <?php if (YII_ENV_DEV && !empty($message)): ?>
                        <div class="alert alert-danger text-start mb-4" style="border-radius: 12px;">
                            <strong>Детали ошибки (только в dev режиме):</strong><br>
                            <?= nl2br(Html::encode($message)) ?>
                        </div>
                    <?php else: ?>
                        <p class="error-description text-muted mb-4">
                            <?php if ($statusCode == 404): ?>
                                Страница, которую вы ищете, не существует или была перемещена.
                            <?php elseif ($statusCode == 403): ?>
                                У вас нет доступа к этой странице.
                            <?php else: ?>
                                Произошла внутренняя ошибка сервера. Мы уже работаем над её устранением.
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <!-- Действия -->
                    <div class="error-actions d-flex flex-column flex-sm-row gap-3 justify-content-center mb-4">
                        <a href="<?= Url::to(['/']) ?>" class="btn btn-primary btn-lg" style="border-radius: 12px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border: none;">
                            <i class="bi bi-house-door me-2"></i>
                            На главную
                        </a>
                        <a href="<?= Url::to(['/catalog']) ?>" class="btn btn-outline-secondary btn-lg" style="border-radius: 12px;">
                            <i class="bi bi-grid me-2"></i>
                            Каталог
                        </a>
                    </div>

                    <!-- Полезные ссылки -->
                    <div class="error-links mt-5 pt-4 border-top">
                        <p class="text-muted mb-3">Возможно, вам будет полезно:</p>
                        <div class="d-flex flex-wrap gap-3 justify-content-center">
                            <a href="<?= Url::to(['/page/contacts']) ?>" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i> Связаться с нами
                            </a>
                            <a href="<?= Url::to(['/page/delivery-terms']) ?>" class="text-decoration-none">
                                <i class="bi bi-truck me-1"></i> Доставка
                            </a>
                            <a href="<?= Url::to(['/page/return-policy']) ?>" class="text-decoration-none">
                                <i class="bi bi-arrow-return-left me-1"></i> Возврат
                            </a>
                        </div>
                    </div>

                    <!-- Контакты -->
                    <div class="error-contact mt-4 p-4" style="background: #f9fafb; border-radius: 12px;">
                        <p class="mb-2 fw-semibold">Нужна помощь?</p>
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-center">
                            <a href="tel:+375291234567" class="text-decoration-none">
                                <i class="bi bi-telephone me-1"></i> +375 (29) 123-45-67
                            </a>
                            <a href="mailto:info@sneakerhead.by" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i> info@sneakerhead.by
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page-wrapper {
    min-height: 60vh;
    display: flex;
    align-items: center;
}

.error-content {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.error-actions .btn:hover {
    transform: translateY(-2px);
    transition: all 0.2s ease;
}

.error-links a {
    color: #6b7280;
    transition: color 0.2s ease;
}

.error-links a:hover {
    color: #8b5cf6;
}

.error-contact a {
    color: #374151;
    font-weight: 500;
}

.error-contact a:hover {
    color: #8b5cf6;
}
</style>
