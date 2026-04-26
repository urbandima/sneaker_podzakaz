<?php
/**
 * Printable invoice view — renders with $this->layout = false
 * @var yii\web\View $this
 * @var \app\backend\modules\checkout\models\Order $order
 * @var array $company
 */
use yii\helpers\Html;

$statuses = Yii::$app->settings->getStatuses();
$company  = is_array($company) ? $company : [];
?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Накладная №<?= Html::encode($order->order_number ?: $order->id) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#1a1a1a;background:#fff;padding:20mm}
/* Header */
.inv-header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:14px;margin-bottom:18px;border-bottom:2px solid #1a1a1a}
.inv-company h1{font-size:20px;font-weight:800;letter-spacing:-.02em;margin-bottom:4px}
.inv-company p{font-size:11px;color:#555;line-height:1.5}
.inv-meta{text-align:right}
.inv-meta .inv-num{font-size:22px;font-weight:800;color:#1a1a1a;line-height:1}
.inv-meta p{font-size:11px;color:#666;margin-top:4px;line-height:1.6}
/* Parties */
.inv-parties{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
.inv-party{background:#f7f7f7;border:1px solid #e0e0e0;border-radius:6px;padding:10px 12px}
.inv-party-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:6px}
.inv-party p{font-size:12px;line-height:1.6;color:#1a1a1a}
/* Table */
table{width:100%;border-collapse:collapse;margin-bottom:18px}
thead tr{background:#1a1a1a;color:#fff}
th{padding:8px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;text-align:left}
th:last-child,th:nth-child(4),th:nth-child(5){text-align:right}
tbody td{padding:7px 10px;border-bottom:1px solid #ebebeb;font-size:12px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tfoot td{padding:8px 10px;font-weight:700;border-top:2px solid #1a1a1a;font-size:13px}
.text-right{text-align:right}
/* Footer */
.inv-footer{display:flex;justify-content:space-between;margin-top:24px;padding-top:16px;border-top:1px solid #e0e0e0;font-size:11px;color:#555}
.inv-sign p{margin-top:20px;border-top:1px solid #888;padding-top:4px;font-size:10px;color:#888}
.inv-note{background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:10px 14px;font-size:11px;margin-bottom:16px;line-height:1.5}
/* Print */
@media print {
    body{padding:12mm}
    .no-print{display:none!important}
    @page{margin:12mm}
}
</style>
</head>
<body>

<div class="no-print" style="background:#f3f4f6;padding:10px 16px;border-radius:8px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">
    <span style="font-size:13px;font-weight:600;color:#374151">Накладная №<?= Html::encode($order->order_number ?: $order->id) ?> — просмотр перед печатью</span>
    <button onclick="window.print()" style="background:#111;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer">🖨 Распечатать</button>
</div>

<!-- Header -->
<div class="inv-header">
    <div class="inv-company">
        <h1><?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?></h1>
        <?php if (!empty($company['address'])): ?><p><?= Html::encode($company['address']) ?></p><?php endif; ?>
        <?php if (!empty($company['phone'])): ?><p>Тел: <?= Html::encode($company['phone']) ?><?php if (!empty($company['email'])): ?> | <?= Html::encode($company['email']) ?><?php endif; ?></p><?php endif; ?>
        <?php if (!empty($company['unp'])): ?><p>УНП: <?= Html::encode($company['unp']) ?></p><?php endif; ?>
    </div>
    <div class="inv-meta">
        <div class="inv-num">НАКЛАДНАЯ</div>
        <p>№ <?= Html::encode($order->order_number ?: $order->id) ?></p>
        <p>от <?= date('d.m.Y', is_numeric($order->created_at) ? $order->created_at : strtotime((string)$order->created_at)) ?></p>
        <p>Статус: <strong><?= Html::encode($statuses[$order->status] ?? $order->status) ?></strong></p>
    </div>
</div>

<!-- Parties -->
<div class="inv-parties">
    <div class="inv-party">
        <div class="inv-party-label">Продавец</div>
        <p><strong><?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?></strong></p>
        <?php if (!empty($company['address'])): ?><p><?= Html::encode($company['address']) ?></p><?php endif; ?>
        <?php if (!empty($company['phone'])): ?><p><?= Html::encode($company['phone']) ?></p><?php endif; ?>
        <?php if (!empty($company['unp'])): ?><p>УНП: <?= Html::encode($company['unp']) ?></p><?php endif; ?>
    </div>
    <div class="inv-party">
        <div class="inv-party-label">Покупатель</div>
        <p><strong><?= Html::encode($order->client_name ?? '—') ?></strong></p>
        <?php if ($order->client_phone): ?><p>Тел: <?= Html::encode($order->client_phone) ?></p><?php endif; ?>
        <?php if ($order->client_email): ?><p><?= Html::encode($order->client_email) ?></p><?php endif; ?>
        <?php
        $addr = $order->delivery_address ?? '';
        if ($addr): ?><p>Адрес: <?= Html::encode($addr) ?></p><?php endif; ?>
        <?php if (!empty($order->pickup_point)): ?><p>ПВЗ: <?= Html::encode($order->pickup_point) ?></p><?php endif; ?>
    </div>
</div>

<?php if ($order->comment): ?>
<div class="inv-note">💬 <?= Html::encode($order->comment) ?></div>
<?php endif; ?>

<!-- Items -->
<table>
    <thead>
        <tr>
            <th style="width:28px">#</th>
            <th>Наименование товара</th>
            <th style="width:60px">Размер</th>
            <th style="width:50px;text-align:right">Кол-во</th>
            <th style="width:80px;text-align:right">Цена, Br</th>
            <th style="width:90px;text-align:right">Итого, Br</th>
        </tr>
    </thead>
    <tbody>
        <?php $subtotal = 0; foreach ($order->orderItems as $i => $item): $lineTotal = $item->price * $item->quantity; $subtotal += $lineTotal; ?>
        <tr>
            <td style="color:#9ca3af"><?= $i + 1 ?></td>
            <td><?= Html::encode($item->product_name) ?></td>
            <td style="color:#6b7280"><?= Html::encode($item->size ?: '—') ?></td>
            <td class="text-right"><?= $item->quantity ?></td>
            <td class="text-right"><?= number_format((float)$item->price, 2, '.', ' ') ?></td>
            <td class="text-right" style="font-weight:600"><?= number_format($lineTotal, 2, '.', ' ') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4"></td>
            <td class="text-right" style="font-size:12px;font-weight:500;color:#555">Итого:</td>
            <td class="text-right"><?= number_format((float)$order->total_amount, 2, '.', ' ') ?> Br</td>
        </tr>
    </tfoot>
</table>

<!-- Delivery + Payment info -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
    <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:6px">Доставка</div>
        <p><?= Html::encode(match($order->delivery_method ?? '') {
            'europochta' => 'Европочта',
            'belpochta'  => 'Белпочта',
            'cdek'       => 'СДЭК',
            'courier_minsk' => 'Курьер (Минск)',
            'pickup'     => 'Самовывоз',
            default      => $order->delivery_method ?: 'Не указано',
        }) ?></p>
        <?php if (!empty($order->local_track_number)): ?><p style="font-family:monospace;font-size:11px;color:#2563eb">Трек: <?= Html::encode($order->local_track_number) ?></p><?php endif; ?>
    </div>
    <div>
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:6px">Оплата</div>
        <p><?= Html::encode($order->payment_method ?? 'Не указано') ?></p>
        <?php if (!empty($order->delivery_date)): ?><p>Срок: <?= Html::encode($order->delivery_date) ?></p><?php endif; ?>
    </div>
</div>

<!-- Signatures -->
<div class="inv-footer">
    <div class="inv-sign">
        <strong>Продавец:</strong> <?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>
        <p>Подпись / печать ______________________</p>
    </div>
    <div class="inv-sign" style="text-align:right">
        <strong>Покупатель:</strong> <?= Html::encode($order->client_name ?? '—') ?>
        <p>Подпись / дата ________________________</p>
    </div>
</div>

</body>
</html>
