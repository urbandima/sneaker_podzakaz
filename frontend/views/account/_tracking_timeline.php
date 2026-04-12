<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $order */

use yii\helpers\Html;

/**
 * DP status color coding:
 * green = delivered
 * blue  = in transit
 * yellow = customs / pending
 * red   = problem / rejected
 */
$dpStatusMap = [
    'created'            => ['label' => 'Создан в ДП',          'color' => 'blue'],
    'accepted'           => ['label' => 'Принят',               'color' => 'blue'],
    'in_transit'         => ['label' => 'В пути',               'color' => 'blue'],
    'customs_clearance'  => ['label' => 'Таможня',              'color' => 'yellow'],
    'customs_hold'       => ['label' => 'Задержан на таможне',  'color' => 'red'],
    'arrived_in_country' => ['label' => 'Прибыл в РБ',         'color' => 'blue'],
    'out_for_delivery'   => ['label' => 'Передан на доставку',  'color' => 'blue'],
    'delivered'          => ['label' => 'Доставлен',            'color' => 'green'],
    'returned'           => ['label' => 'Возврат',              'color' => 'red'],
    'problem'            => ['label' => 'Проблема',             'color' => 'red'],
];

$dpStatus = $order->dp_status ?? null;
$dpSent   = !empty($order->dp_shipment_id);

// Map order status to step index (0-based)
$orderStatusSteps = [
    'new'                   => 0,
    'paid'                  => 1,
    'confirmed_and_paid'    => 2,
    'ordered'               => 2,
    'awaiting_warehouse'    => 3,
    'international_delivery'=> 4,
    'at_warehouse'          => 5,
    'local_delivery'        => 6,
    'delivered'             => 7,
    'canceled'              => -1,
];
$currentStep = $orderStatusSteps[$order->status] ?? 0;

// DP sub-steps (shown inside step 4 "Отправлен в DP" when dp_status is set)
$dpInTransitStatuses = ['created', 'accepted', 'in_transit', 'customs_clearance', 'customs_hold', 'arrived_in_country', 'out_for_delivery'];
$dpCurrentInfo = $dpStatus && isset($dpStatusMap[$dpStatus]) ? $dpStatusMap[$dpStatus] : null;
?>

<div class="dp-timeline">
    <?php if ($order->estimated_delivery_date): ?>
    <div class="dp-eta-badge">
        <i class="bi bi-calendar-check"></i>
        Ожидаемая доставка: <strong><?= Html::encode(date('d.m.Y', strtotime($order->estimated_delivery_date))) ?></strong>
    </div>
    <?php endif; ?>

    <?php if ($order->dp_track_number || $order->local_track_number): ?>
    <div class="dp-track-numbers">
        <?php if ($order->dp_track_number): ?>
        <div class="dp-track-item">
            <i class="bi bi-box-seam"></i>
            <span class="dp-track-label">Трек Таможня:ДП:</span>
            <span class="dp-track-value"><?= Html::encode($order->dp_track_number) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($order->local_track_number): ?>
        <div class="dp-track-item">
            <i class="bi bi-truck"></i>
            <span class="dp-track-label">Локальный трек:</span>
            <span class="dp-track-value"><?= Html::encode($order->local_track_number) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="timeline">
        <?php
        $steps = [
            ['icon' => 'bi-bag-plus',       'label' => 'Создан',              'desc' => 'Заказ оформлен',                 'step' => 0],
            ['icon' => 'bi-credit-card',    'label' => 'Оплачен',             'desc' => 'Оплата подтверждена',             'step' => 1],
            ['icon' => 'bi-person-vcard',   'label' => 'Паспорт',             'desc' => 'Данные переданы для таможни',     'step' => 2],
            ['icon' => 'bi-send',           'label' => 'Отправлен в Таможня:ДП','desc' => 'Передан в сервис доставки',      'step' => 3],
            ['icon' => 'bi-airplane',       'label' => 'В международной доставке','desc' => 'Международный транзит',       'step' => 4],
            ['icon' => 'bi-building',       'label' => 'Передан в РБ',        'desc' => 'Прибыл на склад в Беларуси',     'step' => 5],
            ['icon' => 'bi-truck',          'label' => 'Локальная доставка',  'desc' => 'Доставляется получателю',         'step' => 6],
            ['icon' => 'bi-check-circle',   'label' => 'Доставлен',           'desc' => 'Заказ получен',                  'step' => 7],
        ];

        foreach ($steps as $step):
            $state = '';
            if ($currentStep > $step['step']) $state = 'completed';
            elseif ($currentStep === $step['step']) $state = 'active';

            // For DP step (step 3/4): show special DP sub-status
            $isDpStep = ($step['step'] === 4);
            $dpColor = '';
            if ($isDpStep && $dpCurrentInfo) {
                $dpColor = $dpCurrentInfo['color'];
            }
        ?>
        <div class="timeline-item <?= $state ?><?= $isDpStep && $dpColor ? ' dp-step-' . $dpColor : '' ?>">
            <div class="timeline-dot">
                <i class="bi <?= $step['icon'] ?>"></i>
            </div>
            <div class="timeline-content">
                <h4><?= $step['label'] ?></h4>
                <p><?= $step['desc'] ?></p>

                <?php if ($isDpStep && $dpCurrentInfo && $state !== ''): ?>
                <div class="dp-substatus dp-substatus--<?= $dpCurrentInfo['color'] ?>">
                    <?= Html::encode($dpCurrentInfo['label']) ?>
                    <?php if ($order->dp_status_date): ?>
                    <span class="dp-substatus-date"><?= date('d.m.Y', strtotime($order->dp_status_date)) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($isDpStep && $dpSent && !$dpCurrentInfo): ?>
                <div class="dp-substatus dp-substatus--blue">Ожидаем статус от Таможня:ДП</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($order->status === 'canceled'): ?>
        <div class="timeline-item canceled">
            <div class="timeline-dot"><i class="bi bi-x-circle"></i></div>
            <div class="timeline-content">
                <h4>Отменён</h4>
                <p>Заказ отменён</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.dp-timeline {
    padding: 4px 0;
}

.dp-eta-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
    border-radius: 20px;
    font-size: 13px;
    margin-bottom: 16px;
}

.dp-track-numbers {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
    padding: 12px 14px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
}

.dp-track-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.dp-track-label {
    color: #6b7280;
    font-weight: 500;
}

.dp-track-value {
    font-family: monospace;
    font-size: 13px;
    font-weight: 600;
    color: #111827;
    letter-spacing: 0.03em;
}

.timeline {
    position: relative;
    padding-left: 32px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 8px;
    bottom: 8px;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    padding: 0 0 20px 20px;
    color: #9ca3af;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -18px;
    top: 2px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f3f4f6;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #9ca3af;
    z-index: 1;
    transition: all 0.2s;
}

.timeline-item.completed .timeline-dot {
    background: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}

.timeline-item.active .timeline-dot {
    background: #dbeafe;
    border-color: #3b82f6;
    color: #1d4ed8;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

.timeline-item.canceled .timeline-dot {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}

.timeline-item.dp-step-yellow.active .timeline-dot {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
}

.timeline-item.dp-step-red.active .timeline-dot {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}

.timeline-item.dp-step-green.active .timeline-dot {
    background: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}

.timeline-content h4 {
    margin: 4px 0 2px;
    font-size: 14px;
    font-weight: 600;
    color: #d1d5db;
}

.timeline-item.completed .timeline-content h4,
.timeline-item.active .timeline-content h4 {
    color: #111827;
}

.timeline-content p {
    margin: 0;
    font-size: 12px;
    color: #9ca3af;
}

.timeline-item.completed .timeline-content p,
.timeline-item.active .timeline-content p {
    color: #6b7280;
}

.dp-substatus {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.dp-substatus--green  { background: #d1fae5; color: #065f46; }
.dp-substatus--blue   { background: #dbeafe; color: #1e40af; }
.dp-substatus--yellow { background: #fef3c7; color: #92400e; }
.dp-substatus--red    { background: #fee2e2; color: #991b1b; }

.dp-substatus-date {
    font-weight: 400;
    opacity: 0.8;
    margin-left: 4px;
}
</style>
