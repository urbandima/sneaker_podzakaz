<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<div class="<?= Html::encode($containerClass) ?>">
    <a href="<?= Url::to(['/account/loyalty/index']) ?>" class="loyalty-balance-link text-decoration-none" title="Баллы лояльности">
        <i class="bi bi-star-fill text-warning"></i>
        <?php if (!$iconOnly): ?>
            <span class="loyalty-balance-text ms-1">
                <strong><?= Yii::$app->formatter->asInteger($balance) ?></strong>
                <?php if ($level): ?>
                    <small class="text-muted d-none d-lg-inline">(<?= Html::encode($level->name) ?>)</small>
                <?php endif; ?>
            </span>
        <?php else: ?>
            <span class="badge bg-warning text-dark"><?= $balance ?></span>
        <?php endif; ?>
    </a>
</div>

<style>
.loyalty-balance-widget {
    display: inline-block;
}

.loyalty-balance-link {
    color: inherit;
    transition: all 0.3s ease;
}

.loyalty-balance-link:hover {
    color: #ffc107;
    transform: scale(1.05);
}

.loyalty-balance-link i {
    font-size: 1.2rem;
}

.loyalty-balance-text {
    vertical-align: middle;
}

@media (max-width: 768px) {
    .loyalty-balance-text small {
        display: none;
    }
}
</style>
