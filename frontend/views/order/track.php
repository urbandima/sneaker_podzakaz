<?php
use yii\helpers\Html;

$this->title = 'Статус заказа #' . Html::encode($order->order_number ?? '');
$this->registerCssFile('@web/css/pages/order-track.css');

$statusChain = [
    'created' => 'Новый',
    'paid' => 'Оплачен',
    'ordered' => 'Выкуплен на Poizon',
    'shipped' => 'В доставке',
    'delivered' => 'На складе',
    'completed' => 'Завершён',
    'cancelled' => 'Отменён',
];
$currentIdx = array_search($order->status ?? '', array_keys($statusChain));
if ($currentIdx === false) $currentIdx = -1;
?>
<div class="order-track">
    <h1>Заказ #<?= Html::encode($order->order_number ?? '') ?></h1>

    <!-- Status chain -->
    <div class="status-chain">
        <?php $i = 0; foreach ($statusChain as $key => $label): if ($key === 'cancelled') continue; ?>
        <div class="status-step">
            <div class="status-dot <?= $i <= $currentIdx ? 'status-dot--active' : 'status-dot--inactive' ?>"><?= $i + 1 ?></div>
            <div class="status-label <?= $i <= $currentIdx ? 'status-label--active' : 'status-label--inactive' ?>"><?= Html::encode($label) ?></div>
        </div>
        <?php if ($i < count($statusChain) - 2): ?>
        <div class="status-connector <?= $i < $currentIdx ? 'status-connector--active' : '' ?>"></div>
        <?php endif ?>
        <?php $i++; endforeach ?>
    </div>

    <!-- Order info -->
    <?php if (!empty($order->orderItems)): ?>
    <div class="order-items-box">
        <h3>Состав заказа</h3>
        <?php foreach ($order->orderItems as $item): ?>
        <div class="order-item-row">
            <span><?= Html::encode($item->product_name ?? '') ?><?= $item->size ? ' (EU ' . Html::encode($item->size) . ')' : '' ?></span>
            <span class="order-item-price"><?= number_format($item->price ?? 0, 2) ?> BYN</span>
        </div>
        <?php endforeach ?>
        <div class="order-total">Итого: <?= number_format($order->total_amount ?? 0, 2) ?> BYN</div>
    </div>
    <?php endif ?>

    <?php if (!empty($order->china_track_number)): ?>
    <p>Трек-номер (Китай): <b><?= Html::encode($order->china_track_number) ?></b></p>
    <?php endif ?>

    <?php if (!empty($order->local_track_number)): ?>
    <p>Трек-номер (РБ): <b><?= Html::encode($order->local_track_number) ?></b></p>
    <?php endif ?>

    <?php if ($order->status === 'cancelled'): ?>
    <div class="order-cancelled">
        <b>Заказ отменён</b>
        <?php if (!empty($order->cancel_reason)): ?>
            — <?= Html::encode($order->cancel_reason) ?>
        <?php endif ?>
    </div>
    <?php endif ?>
</div>
