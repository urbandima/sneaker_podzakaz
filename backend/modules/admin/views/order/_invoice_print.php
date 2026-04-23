<?php
/**
 * Печатный шаблон счёта/накладной заказа (для window.print() и TCPDF)
 * @var yii\web\View $this
 * @var \app\backend\modules\checkout\models\Order $model
 * @var array $statuses
 */
use yii\helpers\Html;
$company = Yii::$app->settings->getCompany() ?? [];
?>
<html lang="ru"><head><meta charset="UTF-8"><title>Заказ №<?= Html::encode($model->order_number ?: $model->id) ?></title>
<style>
body{font-family:Arial,sans-serif;font-size:12px;color:#1a1a1a;padding:20mm}
.header{display:flex;justify-content:space-between;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #000}
.company h1{font-size:18px;font-weight:800;margin-bottom:4px}
.company p{font-size:11px;color:#555}
.meta h2{font-size:20px;font-weight:700}
.meta p{font-size:11px;color:#666}
.row{display:flex;gap:20px;margin-bottom:16px}
.col{flex:1;background:#f7f7f7;border:1px solid #e0e0e0;border-radius:6px;padding:10px}
.col h3{font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:6px}
table{width:100%;border-collapse:collapse;margin-bottom:16px}
thead tr{background:#1a1a1a;color:#fff}
th{padding:7px 10px;font-size:11px;text-align:left}
td{padding:7px 10px;border-bottom:1px solid #ebebeb;font-size:12px}
tfoot td{font-weight:700;border-top:2px solid #000;font-size:13px}
</style>
</head><body>
<div class="header">
    <div class="company">
        <h1><?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?></h1>
        <p><?= Html::encode($company['address'] ?? '') ?></p>
        <p><?= Html::encode($company['phone'] ?? '') ?> | <?= Html::encode($company['email'] ?? '') ?></p>
        <?php if (!empty($company['unp'])): ?><p>УНП: <?= Html::encode($company['unp']) ?></p><?php endif ?>
    </div>
    <div class="meta text-right">
        <h2>Заказ №<?= Html::encode($model->order_number ?: $model->id) ?></h2>
        <p>от <?= date('d.m.Y', is_numeric($model->created_at) ? $model->created_at : strtotime($model->created_at)) ?></p>
        <p>Статус: <?= Html::encode($statuses[$model->status] ?? $model->status) ?></p>
    </div>
</div>
<div class="row">
    <div class="col">
        <h3>Клиент</h3>
        <p><?= Html::encode($model->client_name) ?></p>
        <p><?= Html::encode($model->client_phone) ?></p>
        <p><?= Html::encode($model->client_email) ?></p>
    </div>
    <div class="col">
        <h3>Доставка</h3>
        <p><?= Html::encode($model->delivery_address ?? 'Не указан') ?></p>
        <?php if ($model->china_track_number): ?><p>Трек: <?= Html::encode($model->china_track_number) ?></p><?php endif ?>
    </div>
</div>
<table>
<thead><tr><th>#</th><th>Товар</th><th>Размер</th><th>Кол-во</th><th>Цена</th><th>Итого</th></tr></thead>
<tbody>
<?php foreach ($model->orderItems as $i => $item): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= Html::encode($item->product_name ?? '') ?></td>
    <td><?= Html::encode($item->size ?? '—') ?></td>
    <td><?= (int)($item->quantity ?? 1) ?></td>
    <td><?= number_format($item->price ?? 0, 2) ?> BYN</td>
    <td><?= number_format(($item->price ?? 0) * ($item->quantity ?? 1), 2) ?> BYN</td>
</tr>
<?php endforeach ?>
</tbody>
<tfoot><tr><td colspan="5" class="text-right">ИТОГО:</td><td><?= number_format($model->total_amount ?? 0, 2) ?> BYN</td></tr></tfoot>
</table>
<?php if (!empty($company['bank_details'])): ?>
<div style="margin-top:20px;font-size:11px;color:#555">
    <strong>Банковские реквизиты:</strong> <?= Html::encode($company['bank_details']) ?>
</div>
<?php endif ?>
</body></html>
