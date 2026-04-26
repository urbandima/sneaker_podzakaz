<?php
/**
 * _index_rows.php — AJAX partial for infinite scroll (mirrors index.php tbody loop)
 *
 * @var \app\backend\modules\checkout\models\Order[] $orders
 * @var array $statuses   status key → label
 * @var array $logistMap  logist id → username
 * @var array $dupMap     china_track_number → count (duplicates only)
 */
use yii\helpers\Html;
use yii\helpers\Url;

$statusPills = [
    'new'                    => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'created'                => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
    'paid'                   => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
    'confirmed_and_paid'     => ['bg' => '#faf5ff', 'color' => '#7c3aed'],
    'ordered'                => ['bg' => '#ecfeff', 'color' => '#0891b2'],
    'processing'             => ['bg' => '#fffbeb', 'color' => '#d97706'],
    'awaiting_warehouse'     => ['bg' => '#faf5ff', 'color' => '#7c3aed'],
    'international_delivery' => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'at_warehouse'           => ['bg' => '#f5f3ff', 'color' => '#7c3aed'],
    'local_delivery'         => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'shipped'                => ['bg' => '#faf5ff', 'color' => '#9333ea'],
    'in_transit'             => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'delivered'              => ['bg' => '#ecfdf5', 'color' => '#059669'],
    'cancelled'              => ['bg' => '#fef2f2', 'color' => '#dc2626'],
    'canceled'               => ['bg' => '#fef2f2', 'color' => '#dc2626'],
    'returned'               => ['bg' => '#fef2f2', 'color' => '#dc2626'],
    'imported'               => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
];
$pmLabels = ['cash' => 'Наличные', 'card' => 'Карта', 'transfer' => 'Перевод', 'erip' => 'ЕРИП'];
$dmLabels = ['europochta' => 'Европочта', 'belpochta' => 'Белпочта', 'cdek' => 'СДЭК', 'courier' => 'Курьер'];

foreach ($orders as $order):
    $daysSince = (int)floor((time() - $order->created_at) / 86400);
    $firstItem = $order->orderItems[0] ?? null;
    $sp          = $statusPills[$order->status] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];
    $statusLabel = \app\backend\modules\checkout\models\Order::statusLabel($order->status);
?>
<tr>
    <td style="padding:6px"><input type="checkbox" class="order-checkbox" value="<?= $order->id ?>"></td>
    <td style="white-space:nowrap;padding:6px 8px">
        <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>"
           style="font-weight:700;color:var(--admin-text-primary,#111);text-decoration:none">
            <?= Html::encode($order->order_number ?: '#'.$order->id) ?>
        </a>
        <div style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);margin-top:1px">
            <?= date('d.m.Y', $order->created_at) ?>
            <?php if ($daysSince > 0): ?><span style="opacity:.7"> · <?= $daysSince ?>д</span><?php endif; ?>
        </div>
    </td>
    <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <?= Html::encode($order->client_name) ?>
    </td>
    <td style="white-space:nowrap">
        <a href="tel:<?= Html::encode($order->client_phone) ?>" style="color:inherit;text-decoration:none">
            <?= Html::encode($order->client_phone) ?>
        </a>
    </td>
    <td data-col="email" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
        <?= $order->client_email ? Html::encode($order->client_email) : '—' ?>
    </td>
    <td data-col="item" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <?= $firstItem ? Html::encode($firstItem->product_name) : '—' ?>
    </td>
    <td data-col="status" style="padding:6px 8px">
        <span class="status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>">
            <?= Html::encode($statusLabel) ?>
        </span>
    </td>
    <td data-col="amount" style="font-weight:700;white-space:nowrap">
        <?= number_format($order->total_amount, 2) ?> <span style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);font-weight:400">Br</span>
    </td>
    <td data-col="payment" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
        <?= Html::encode($pmLabels[$order->payment_method ?? ''] ?? ($order->payment_method ?: '—')) ?>
    </td>
    <td data-col="delivery" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
        <?= Html::encode($dmLabels[$order->delivery_method ?? ''] ?? ($order->delivery_method ?: '—')) ?>
    </td>
    <td data-col="china_track">
        <?php if (!empty($order->china_track_number)): ?>
        <span class="track-badge" title="<?= Html::encode($order->china_track_number) ?>"><?= Html::encode($order->china_track_number) ?></span>
        <?php if (!empty($dupMap[$order->china_track_number]) && $dupMap[$order->china_track_number] > 1): ?>
        <span class="dup-badge" title="Трек встречается в <?= $dupMap[$order->china_track_number] ?> заказах">×<?= $dupMap[$order->china_track_number] ?></span>
        <?php endif; ?>
        <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
    </td>
    <td data-col="dp_track">
        <?php if (!empty($order->dp_track_number)): ?>
        <span class="track-badge" title="<?= Html::encode($order->dp_track_number) ?>"><?= Html::encode($order->dp_track_number) ?></span>
        <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
    </td>
    <td data-col="local_track">
        <?php if (!empty($order->local_track_number)): ?>
        <span class="track-badge" title="<?= Html::encode($order->local_track_number) ?>"><?= Html::encode($order->local_track_number) ?></span>
        <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
    </td>
    <td data-col="dp_status" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280);font-size:.75rem">
        <?= Html::encode($order->dp_status ?: '—') ?>
    </td>
    <td data-col="city" style="white-space:nowrap">
        <?= Html::encode($order->city ?: '—') ?>
    </td>
    <td data-col="logist" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
        <?= Html::encode($logistMap[$order->assigned_logist] ?? '—') ?>
    </td>
    <td data-col="source" style="font-size:.75rem;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
        <?= Html::encode($order->source ?: '—') ?>
    </td>
    <td data-col="comment" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)"
        title="<?= Html::encode($order->comment ?? '') ?>">
        <?= Html::encode($order->comment ?: '—') ?>
    </td>
    <td style="padding:4px 6px">
        <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>"
           class="admin-btn admin-btn-secondary"
           style="padding:.2rem .45rem;font-size:.875rem;">
            <i class="bi bi-eye"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
