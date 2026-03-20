<?php

/**
 * Order Tracking - Отслеживание заказа в личном кабинете
 */

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $order */
/** @var array $trackingInfo */

$tracking = $order->deliveryTracking;
?>

<div class="order-tracking-section">
    <div class="tracking-header">
        <h2>Отслеживание заказа #<?= $order->order_number ?></h2>
        <div class="tracking-number">
            <span>Трек-номер:</span>
            <strong><?= $tracking->tracking_number ?? 'Не присвоен' ?></strong>
            <?php if ($tracking): ?>
            <button class="btn-copy" onclick="copyToClipboard('<?= $tracking->tracking_number ?>')">
                <i class="bi bi-clipboard"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($tracking): ?>
    <!-- Tracking Progress -->
    <div class="tracking-progress">
        <?php
        $statuses = [
            'pending' => ['Ожидает отправки', 'bi bi-box-seam', false],
            'picked_up' => ['Отправлен', 'bi bi-truck', false],
            'in_transit' => ['В пути', 'bi bi-airplane', false],
            'out_for_delivery' => ['На доставке', 'bi bi-house', false],
            'delivered' => ['Доставлен', 'bi bi-check-circle', false],
        ];
        
        $currentStatus = $tracking->status;
        $foundCurrent = false;
        
        foreach ($statuses as $status => $info):
            $isCompleted = !$foundCurrent && $status !== $currentStatus;
            $isActive = $status === $currentStatus;
            $foundCurrent = $foundCurrent || $isActive;
        ?>
        <div class="tracking-step <?= $isCompleted ? 'completed' : '' ?> <?= $isActive ? 'active' : '' ?>">
            <div class="step-icon">
                <i class="<?= $info[1] ?>"></i>
            </div>
            <div class="step-info">
                <span class="step-name"><?= $info[0] ?></span>
                <?php if ($isActive): ?>
                <span class="step-date"><?= Yii::$app->formatter->asDate($tracking->last_check_at, 'dd.MM.yyyy HH:mm') ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Current Status -->
    <div class="current-status">
        <div class="status-icon">
            <i class="bi bi-geo-alt-fill"></i>
        </div>
        <div class="status-info">
            <div class="status-title"><?= $tracking->status_description ?></div>
            <div class="status-location"><?= $tracking->location ?></div>
            <div class="status-carrier">
                Перевозчик: <?= $tracking->carrier ?>
                <?php if ($trackingUrl = \app\backend\modules\checkout\services\TrackingService::getTrackingUrl($tracking->tracking_number, $tracking->carrier)): ?>
                <a href="<?= $trackingUrl ?>" target="_blank" class="carrier-link">
                    Отследить на сайте <i class="bi bi-box-arrow-up-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tracking Events -->
    <div class="tracking-events">
        <h3>История перемещений</h3>
        <div class="events-timeline">
            <?php foreach ($tracking->getEvents() as $event): ?>
            <div class="event-item">
                <div class="event-marker"></div>
                <div class="event-content">
                    <div class="event-header">
                        <span class="event-status"><?= Html::encode($event['description']) ?></span>
                        <span class="event-date"><?= Yii::$app->formatter->asDate($event['timestamp'], 'dd.MM.yyyy HH:mm') ?></span>
                    </div>
                    <?php if (!empty($event['location'])): ?>
                    <div class="event-location">
                        <i class="bi bi-geo-alt"></i>
                        <?= Html::encode($event['location']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Estimated Delivery -->
    <?php if ($tracking->estimated_delivery): ?>
    <div class="estimated-delivery">
        <div class="delivery-icon">📅</div>
        <div class="delivery-info">
            <div class="delivery-label">Ожидаемая дата доставки</div>
            <div class="delivery-date"><?= Yii::$app->formatter->asDate($tracking->estimated_delivery, 'dd.MM.yyyy') ?></div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <!-- No Tracking -->
    <div class="no-tracking">
        <div class="no-tracking-icon"><i class="bi bi-box-seam"></i></div>
        <h3>Заказ готовится к отправке</h3>
        <p>Трек-номер будет присвоен после передачи заказа в службу доставки</p>
        <p class="no-tracking-note">Обычно это занимает 1-2 рабочих дня</p>
    </div>
    <?php endif; ?>
    
    <!-- Actions -->
    <div class="tracking-actions">
        <a href="/account/orders" class="btn-back">
            <i class="bi bi-arrow-left"></i> К заказам
        </a>
        <?php if ($tracking): ?>
        <button class="btn-refresh" onclick="refreshTracking(<?= $order->id ?>)">
            <i class="bi bi-arrow-clockwise"></i> Обновить данные
        </button>
        <?php endif; ?>
    </div>
</div>

<style>
.order-tracking-section {
    padding: 2rem 0;
}

.tracking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.tracking-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
}

.tracking-number {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8fafc;
    padding: 0.75rem 1rem;
    border-radius: 10px;
}

.tracking-number span {
    color: #64748b;
    font-size: 0.875rem;
}

.tracking-number strong {
    color: #0f172a;
}

.btn-copy {
    background: none;
    border: none;
    cursor: pointer;
    color: #64748b;
    padding: 0.25rem;
}

.btn-copy:hover {
    color: #6366f1;
}

/* Tracking Progress */
.tracking-progress {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    position: relative;
}

.tracking-progress::before {
    content: '';
    position: absolute;
    top: 30px;
    left: 50px;
    right: 50px;
    height: 4px;
    background: #e2e8f0;
    z-index: 0;
}

.tracking-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.step-icon {
    width: 60px;
    height: 60px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 0.75rem;
    transition: all 0.3s;
}

.tracking-step.completed .step-icon {
    background: #10b981;
}

.tracking-step.active .step-icon {
    background: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
    animation: pulse 2s ease-in-out infinite;
}

.step-info {
    text-align: center;
}

.step-name {
    display: block;
    font-weight: 600;
    color: #0f172a;
    font-size: 0.875rem;
}

.step-date {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Current Status */
.current-status {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
    border-radius: 16px;
    margin-bottom: 2rem;
}

.status-icon {
    width: 60px;
    height: 60px;
    background: #6366f1;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.status-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.25rem;
}

.status-location {
    color: #64748b;
    margin-bottom: 0.5rem;
}

.status-carrier {
    font-size: 0.875rem;
    color: #64748b;
}

.carrier-link {
    color: #6366f1;
    text-decoration: none;
    margin-left: 0.5rem;
}

.carrier-link:hover {
    text-decoration: underline;
}

/* Tracking Events */
.tracking-events {
    margin-bottom: 2rem;
}

.tracking-events h3 {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.events-timeline {
    position: relative;
    padding-left: 2rem;
}

.events-timeline::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
}

.event-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.event-marker {
    position: absolute;
    left: -2rem;
    top: 0;
    width: 16px;
    height: 16px;
    background: #6366f1;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #6366f1;
}

.event-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.event-status {
    font-weight: 600;
    color: #0f172a;
}

.event-date {
    font-size: 0.875rem;
    color: #64748b;
}

.event-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
}

/* Estimated Delivery */
.estimated-delivery {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: #fef3c7;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.delivery-icon {
    font-size: 2rem;
}

.delivery-label {
    font-size: 0.875rem;
    color: #92400e;
}

.delivery-date {
    font-size: 1.25rem;
    font-weight: 700;
    color: #92400e;
}

/* No Tracking */
.no-tracking {
    text-align: center;
    padding: 3rem;
    background: #f8fafc;
    border-radius: 16px;
    margin-bottom: 2rem;
}

.no-tracking-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.no-tracking h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.no-tracking p {
    color: #64748b;
}

.no-tracking-note {
    font-size: 0.875rem;
    margin-top: 0.5rem;
    color: #94a3b8;
}

/* Actions */
.tracking-actions {
    display: flex;
    gap: 1rem;
}

.btn-back, .btn-refresh {
    padding: 0.875rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-back {
    background: #f1f5f9;
    color: #0f172a;
    border: none;
}

.btn-refresh {
    background: #6366f1;
    color: white;
    border: none;
}

.btn-refresh:hover {
    background: #4f46e5;
}

/* Responsive */
@media (max-width: 768px) {
    .tracking-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .tracking-progress {
        flex-direction: column;
        gap: 1rem;
    }
    
    .tracking-progress::before {
        display: none;
    }
    
    .tracking-step {
        flex-direction: row;
        justify-content: flex-start;
    }
    
    .step-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        margin-bottom: 0;
        margin-right: 1rem;
    }
    
    .step-info {
        text-align: left;
    }
}
</style>

<script>
function refreshTracking(orderId) {
    const btn = document.querySelector('.btn-refresh');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновление...';
    
    fetch(`/api/v1/tracking/refresh/${orderId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновить данные';
        });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    showNotification('Трек-номер скопирован', 'success');
}
</script>
