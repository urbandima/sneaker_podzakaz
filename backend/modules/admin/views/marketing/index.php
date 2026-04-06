<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Маркетинг';
?>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="bi bi-megaphone"></i> Маркетинг
    </h1>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['marketing/abandoned-carts']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-cart-x"></i> Брошенные корзины
        </a>
        <a href="<?= Url::to(['marketing/recommendations']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-stars"></i> Рекомендации
        </a>
    </div>
</div>

<div class="marketing-grid">
    <div class="marketing-card">
        <div class="marketing-card-header">
            <div class="marketing-icon abandoned">
                <i class="bi bi-cart-x"></i>
            </div>
            <div class="marketing-stat">
                <div class="marketing-stat-value"><?= $abandonedStats['total_abandoned'] ?></div>
                <div class="marketing-stat-label">Брошенных корзин</div>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
            <div>
                <div style="font-size: 1.25rem; font-weight: 700;"><?= Yii::$app->formatter->asCurrency($abandonedStats['total_value'], 'BYN') ?></div>
                <div style="font-size: 0.75rem; color: var(--admin-text-secondary);">Общая сумма</div>
            </div>
            <div>
                <div style="font-size: 1.25rem; font-weight: 700;"><?= Yii::$app->formatter->asCurrency($abandonedStats['avg_value'], 'BYN') ?></div>
                <div style="font-size: 0.75rem; color: var(--admin-text-secondary);">Средний чек</div>
            </div>
        </div>
    </div>
    
    <div class="marketing-card">
        <div class="marketing-card-header">
            <div class="marketing-icon upsell">
                <i class="bi bi-arrow-up-circle"></i>
            </div>
            <div class="marketing-stat">
                <div class="marketing-stat-value"><?= number_format($recommendationStats['conversion_rate'], 1) ?>%</div>
                <div class="marketing-stat-label">Конверсия рекомендаций</div>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
            <div>
                <div style="font-size: 1.25rem; font-weight: 700;"><?= $recommendationStats['total_clicks'] ?></div>
                <div style="font-size: 0.75rem; color: var(--admin-text-secondary);">Кликов</div>
            </div>
            <div>
                <div style="font-size: 1.25rem; font-weight: 700;"><?= $recommendationStats['total_conversions'] ?></div>
                <div style="font-size: 0.75rem; color: var(--admin-text-secondary);">Покупок</div>
            </div>
        </div>
    </div>
    
    <div class="marketing-card">
        <div class="marketing-card-header">
            <div class="marketing-icon reviews">
                <i class="bi bi-star-fill"></i>
            </div>
            <div class="marketing-stat">
                <div class="marketing-stat-value"><?= number_format($abandonedStats['recovery_rate'], 1) ?>%</div>
                <div class="marketing-stat-label">Восстановление корзин</div>
            </div>
        </div>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
            <button type="button" class="admin-btn admin-btn-primary" style="width: 100%;" onclick="sendBulkReminders()">
                <i class="bi bi-send"></i> Отправить напоминания
            </button>
        </div>
    </div>
</div>

<div class="admin-card">
    <h3 style="margin: 0 0 1rem 0;">Последние брошенные корзины</h3>
    
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
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="cart-value"><?= Yii::$app->formatter->asCurrency($cart->total_amount, 'BYN') ?></div>
                        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" onclick="sendReminder(<?= $cart->id ?>)">
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

<script>
function sendReminder(cartId) {
    if (!confirm('Отправить напоминание клиенту?')) return;
    
    fetch('<?= Url::to(['marketing/send-reminder']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: 'cart_id=' + cartId
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    });
}

function sendBulkReminders() {
    if (!confirm('Отправить напоминания всем клиентам с брошенными корзинами?')) return;
    
    fetch('<?= Url::to(['marketing/send-bulk-reminders']) ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
    });
}
</script>
