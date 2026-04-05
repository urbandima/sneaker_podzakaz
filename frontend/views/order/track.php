<?php
use yii\helpers\Html;
$this->title = 'Статус заказа #' . ($order->order_number ?? '');
$statusChain = ['created'=>'Новый','paid'=>'Оплачен','ordered'=>'Выкуплен на Poizon','shipped'=>'В доставке','delivered'=>'На складе','completed'=>'Завершён','cancelled'=>'Отменён'];
$currentIdx = array_search($order->status ?? '', array_keys($statusChain));
if ($currentIdx === false) $currentIdx = -1;
?>
<div style="max-width:700px;margin:2rem auto;padding:1rem;font-family:Inter,sans-serif">
    <h1 style="font-size:1.5rem;font-weight:700">Заказ #<?= Html::encode($order->order_number ?? '') ?></h1>

    <!-- Status chain -->
    <div style="display:flex;overflow-x:auto;gap:0;margin:2rem 0;align-items:flex-start">
        <?php $i = 0; foreach($statusChain as $key => $label): if($key==='cancelled') continue; ?>
        <div style="flex:1;text-align:center;min-width:80px">
            <div style="width:32px;height:32px;border-radius:50%;background:<?= $i <= $currentIdx ? '#008060' : '#e1e3e5' ?>;color:<?= $i <= $currentIdx ? '#fff' : '#6d7175' ?>;display:flex;align-items:center;justify-content:center;margin:0 auto;font-weight:700"><?= $i+1 ?></div>
            <div style="font-size:0.7rem;margin-top:0.4rem;color:<?= $i <= $currentIdx ? '#008060' : '#6d7175' ?>"><?= Html::encode($label) ?></div>
        </div>
        <?php if($i < count($statusChain)-2): ?><div style="flex:0 0 1rem;border-top:2px solid <?= $i < $currentIdx ? '#008060' : '#e1e3e5' ?>;margin-top:16px"></div><?php endif ?>
        <?php $i++; endforeach ?>
    </div>

    <!-- Order info -->
    <?php if(!empty($order->orderItems)): ?>
    <div style="background:#f7f8fa;border-radius:12px;padding:1rem;margin-bottom:1rem">
        <h3 style="font-size:1rem;font-weight:600;margin:0 0 1rem">Состав заказа</h3>
        <?php foreach($order->orderItems as $item): ?>
        <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid #e1e3e5">
            <span><?= Html::encode($item->product_name ?? '') ?><?= $item->size ? ' (EU '.$item->size.')' : '' ?></span>
            <span style="font-weight:600"><?= number_format($item->price ?? 0, 2) ?> BYN</span>
        </div>
        <?php endforeach ?>
        <div style="text-align:right;font-weight:700;margin-top:0.5rem">Итого: <?= number_format($order->total_amount ?? 0, 2) ?> BYN</div>
    </div>
    <?php endif ?>

    <?php if(!empty($order->china_track_number)): ?>
    <p style="margin-bottom:0.5rem">Трек-номер (Китай): <b><?= Html::encode($order->china_track_number) ?></b></p>
    <?php endif ?>

    <?php if(!empty($order->local_track_number)): ?>
    <p>Трек-номер (РБ): <b><?= Html::encode($order->local_track_number) ?></b></p>
    <?php endif ?>

    <?php if($order->status === 'cancelled'): ?>
    <div style="background:#fff0f0;border-radius:8px;padding:1rem;margin-top:1rem;color:#b71c1c;">
        <b>Заказ отменён</b>
        <?php if(!empty($order->cancel_reason)): ?>— <?= Html::encode($order->cancel_reason) ?><?php endif ?>
    </div>
    <?php endif ?>
</div>
