<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Маркетинг';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-cart-x"></i> Брошенные корзины', ['marketing/abandoned-carts'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    Html::a('<i class="bi bi-stars"></i> Рекомендации', ['marketing/recommendations'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<div class="admin-stats mb-5">
    <div class="admin-stat-card">
        <div class="admin-stat-icon abandoned"><i class="bi bi-cart-x"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $abandonedStats['total_abandoned'] ?></div>
            <div class="admin-stat-label">Брошенных корзин</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon upsell"><i class="bi bi-arrow-up-circle"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($recommendationStats['conversion_rate'], 1) ?>%</div>
            <div class="admin-stat-label">Конверсия рекомендаций</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon reviews"><i class="bi bi-star-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($abandonedStats['recovery_rate'], 1) ?>%</div>
            <div class="admin-stat-label">Восстановление корзин</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Последние брошенные корзины</h2>
    </div>
    <div class="admin-card-body">
    
    <?php if (!empty($abandonedCarts)): ?>
        <div class="abandoned-cart-list">
            <?php foreach ($abandonedCarts as $cart): ?>
                <div class="abandoned-cart-item">
                    <div>
                        <div class="cart-customer">
                            <?= $cart->customer ? Html::encode($cart->customer->getFullName()) : 'Гость' ?>
                        </div>
                        <div class="cart-meta">
                            <?= $cart->items_count ?> товаров • 
                            <?= Yii::$app->formatter->asRelativeTime($cart->updated_at) ?>
                        </div>
                    </div>
                    <div class="d-flex align-center gap-4">
                        <div class="cart-value"><?= Yii::$app->formatter->asCurrency($cart->total_amount, 'BYN') ?></div>
                        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm"
                                onclick="sendReminder(<?= $cart->id ?>, this)"
                                data-reminder-url="<?= Url::to(['marketing/send-reminder']) ?>">
                            <i class="bi bi-send"></i> Напомнить
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 2rem; color: var(--admin-text-secondary);">
            <i class="bi bi-cart-check" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
            Нет брошенных корзин
        </div>
    <?php endif; ?>
    
    <?php if (count($abandonedCarts) >= 10): ?>
        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="<?= Url::to(['marketing/abandoned-carts']) ?>" class="admin-btn admin-btn-secondary">
                Показать все
            </a>
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- Кнопка массовой отправки напоминаний -->
<div class="admin-card mt-5">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Массовая отправка напоминаний</h2>
    </div>
    <div class="admin-card-body">
        <button type="button" class="admin-btn admin-btn-primary"
                onclick="sendBulkReminders(this)"
                data-bulk-url="<?= Url::to(['marketing/send-bulk-reminders']) ?>">
            <i class="bi bi-send"></i> Отправить напоминания всем
        </button>
    </div>
</div>
