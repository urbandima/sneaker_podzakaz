<?php
/**
 * Печатная накладная / счёт-фактура заказа.
 * Используется через window.print() — открывается в отдельном окне.
 *
 * @var yii\web\View $this
 * @var app\backend\modules\checkout\models\Order $order
 * @var array $company  Реквизиты компании из Yii::$app->settings->getCompany()
 */

use yii\helpers\Html;
use yii\helpers\Url;

$company = $company ?? (Yii::$app->settings->getCompany() ?? []);
$trackerUrl = $order->track_number
    ? Url::to(['/order/track', 'track' => $order->track_number], true)
    : null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Накладная №<?= Html::encode($order->order_number) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; padding: 20mm; }
        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #1a1a1a; }
        .company-block h1 { font-size: 18px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px; }
        .company-block p { font-size: 11px; color: #555; line-height: 1.5; }
        .doc-meta { text-align: right; }
        .doc-meta h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .doc-meta .doc-date { font-size: 11px; color: #666; }

        .section { margin-bottom: 16px; }
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #888; margin-bottom: 6px; }

        .parties { display: flex; gap: 24px; margin-bottom: 20px; }
        .party { flex: 1; background: #f7f7f7; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px 14px; }
        .party h3 { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #888; margin-bottom: 6px; }
        .party p { font-size: 12px; line-height: 1.6; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items thead tr { background: #1a1a1a; color: #fff; }
        table.items th { padding: 7px 10px; font-size: 11px; font-weight: 600; text-align: left; }
        table.items th:last-child, table.items td:last-child { text-align: right; }
        table.items td { padding: 7px 10px; border-bottom: 1px solid #ebebeb; font-size: 12px; vertical-align: top; }
        table.items tbody tr:last-child td { border-bottom: none; }
        table.items tfoot tr { font-weight: 700; border-top: 2px solid #1a1a1a; }
        table.items tfoot td { padding: 8px 10px; font-size: 13px; }

        .track-block { display: flex; align-items: center; gap: 20px; background: #f0f7ff; border: 1px solid #b3d4f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; }
        .track-label { font-size: 11px; color: #555; margin-bottom: 3px; }
        .track-number { font-size: 15px; font-weight: 700; letter-spacing: 1px; }

        .qr-placeholder { width: 72px; height: 72px; border: 2px dashed #b3d4f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #999; text-align: center; padding: 4px; flex-shrink: 0; }

        .footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #ddd; display: flex; justify-content: space-between; font-size: 10px; color: #888; }

        @media print {
            body { padding: 10mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Print button (hidden in print mode) -->
<div class="no-print" style="text-align:right;margin-bottom:16px;">
    <button onclick="window.print()" style="padding:8px 18px;background:#1a1a1a;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;">
        Печать / PDF
    </button>
    <button onclick="window.close()" style="padding:8px 18px;background:#f3f4f6;color:#374151;border:1px solid #d1d5db;border-radius:6px;font-size:13px;cursor:pointer;margin-left:8px;">
        Закрыть
    </button>
</div>

<!-- Header -->
<div class="doc-header">
    <div class="company-block">
        <h1><?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?></h1>
        <?php if (!empty($company['unp'])): ?>
            <p>УНП: <?= Html::encode($company['unp']) ?></p>
        <?php endif; ?>
        <?php if (!empty($company['address'])): ?>
            <p><?= Html::encode($company['address']) ?></p>
        <?php endif; ?>
        <?php if (!empty($company['phone'])): ?>
            <p>Тел.: <?= Html::encode($company['phone']) ?></p>
        <?php endif; ?>
        <?php if (!empty($company['email'])): ?>
            <p><?= Html::encode($company['email']) ?></p>
        <?php endif; ?>
    </div>
    <div class="doc-meta">
        <h2>Накладная №<?= Html::encode($order->order_number) ?></h2>
        <div class="doc-date">
            Дата: <?= Yii::$app->formatter->asDate($order->created_at, 'dd.MM.yyyy') ?>
        </div>
        <?php if ($order->paid_at): ?>
        <div class="doc-date">
            Оплачено: <?= Yii::$app->formatter->asDate($order->paid_at, 'dd.MM.yyyy') ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Parties -->
<div class="parties">
    <div class="party">
        <h3>Продавец</h3>
        <p>
            <strong><?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?></strong><br>
            <?= Html::encode($company['address'] ?? '') ?>
        </p>
    </div>
    <div class="party">
        <h3>Покупатель</h3>
        <p>
            <strong><?= Html::encode($order->client_name ?? '') ?></strong><br>
            <?php if (!empty($order->client_phone)): ?>
                Тел.: <?= Html::encode($order->client_phone) ?><br>
            <?php endif; ?>
            <?php if (!empty($order->client_email)): ?>
                <?= Html::encode($order->client_email) ?><br>
            <?php endif; ?>
            <?php if (!empty($order->delivery_address)): ?>
                <?= Html::encode($order->delivery_address) ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Items Table -->
<table class="items">
    <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Наименование</th>
            <th style="width:60px;text-align:center;">Кол-во</th>
            <th style="width:90px;">Цена, BYN</th>
            <th style="width:100px;">Сумма, BYN</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 1; foreach ($order->items as $item): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td>
                <?= Html::encode($item->product_name) ?>
                <?php if (!empty($item->size)): ?>
                    <br><small style="color:#888;">Размер: <?= Html::encode($item->size) ?></small>
                <?php endif; ?>
                <?php if (!empty($item->color)): ?>
                    <small style="color:#888;"> | Цвет: <?= Html::encode($item->color) ?></small>
                <?php endif; ?>
            </td>
            <td class="text-center"><?= (int)$item->quantity ?></td>
            <td class="text-right"><?= number_format($item->price, 2, '.', ' ') ?></td>
            <td class="text-right"><?= number_format($item->price * $item->quantity, 2, '.', ' ') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <?php if (!empty($order->discount_amount) && $order->discount_amount > 0): ?>
        <tr>
            <td colspan="4" style="text-align:right;font-weight:400;font-size:11px;">Скидка:</td>
            <td style="text-align:right;font-weight:400;color:#e53e3e;">-<?= number_format($order->discount_amount, 2, '.', ' ') ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($order->delivery_cost) && $order->delivery_cost > 0): ?>
        <tr>
            <td colspan="4" style="text-align:right;font-weight:400;font-size:11px;">Доставка:</td>
            <td style="text-align:right;font-weight:400;"><?= number_format($order->delivery_cost, 2, '.', ' ') ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td colspan="4" class="text-right">ИТОГО:</td>
            <td class="text-right"><?= number_format($order->total_amount, 2, '.', ' ') ?> BYN</td>
        </tr>
    </tfoot>
</table>

<!-- Track Number -->
<?php if ($order->track_number): ?>
<div class="track-block">
    <div style="flex:1;">
        <div class="track-label">Трек-номер отправления</div>
        <div class="track-number"><?= Html::encode($order->track_number) ?></div>
        <?php if ($trackerUrl): ?>
            <div style="font-size:10px;color:#3b82f6;margin-top:3px;"><?= Html::encode($trackerUrl) ?></div>
        <?php endif; ?>
    </div>
    <div class="qr-placeholder">
        QR<br>трекер
    </div>
</div>
<?php endif; ?>

<!-- Footer -->
<div class="footer">
    <div>Документ сформирован: <?= date('d.m.Y H:i') ?></div>
    <div>
        Подпись: _________________________
    </div>
</div>

</body>
</html>
