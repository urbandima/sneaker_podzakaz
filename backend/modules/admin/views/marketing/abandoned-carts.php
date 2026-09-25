<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Брошенные корзины';

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад к маркетингу', ['marketing/index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm', 'style' => 'flex-shrink:0']),
];
?>

<!-- KPI Stats -->
<div class="admin-stats" style="margin-bottom:1.5rem">
    <div class="admin-stat-card">
        <div class="admin-stat-icon abandoned"><i class="bi bi-cart-x"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= (int)$stats['total_abandoned'] ?></div>
            <div class="admin-stat-label">Брошенных корзин</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-cash-stack"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= Yii::$app->formatter->asCurrency($stats['total_value'], 'BYN') ?></div>
            <div class="admin-stat-label">Сумма корзин</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-graph-up"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= Yii::$app->formatter->asCurrency($stats['avg_value'], 'BYN') ?></div>
            <div class="admin-stat-label">Средний чек</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-cart-x"></i> Брошенные корзины</h2>
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm"
                onclick="sendBulkReminders(this)"
                data-bulk-url="<?= Url::to(['marketing/send-bulk-reminders']) ?>"
                <?= empty($carts) ? 'disabled' : '' ?>>
            <i class="bi bi-send"></i> Напомнить всем
        </button>
    </div>
    <div class="admin-card-body">
        <?php if (!empty($carts)) : ?>
        <div class="abandoned-cart-list">
            <?php foreach ($carts as $cart) : ?>
            <div class="abandoned-cart-item">
                <div>
                    <div class="cart-customer">
                        <?= $cart->customer ? Html::encode($cart->customer->getFullName()) : 'Гость' ?>
                    </div>
                    <div class="cart-meta">
                        <?= (int)$cart->quantity ?> товаров ·
                        <?= Yii::$app->formatter->asRelativeTime($cart->updated_at) ?>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:1rem">
                    <div class="cart-value"><?= Yii::$app->formatter->asCurrency($cart->price * $cart->quantity, 'BYN') ?></div>
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm"
                            onclick="sendReminder(<?= $cart->id ?>, this)"
                            data-reminder-url="<?= Url::to(['marketing/send-reminder']) ?>">
                        <i class="bi bi-send"></i> Напомнить
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">
            <i class="bi bi-cart-check" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
            Нет брошенных корзин
        </div>
        <?php endif; ?>
    </div>
</div>
