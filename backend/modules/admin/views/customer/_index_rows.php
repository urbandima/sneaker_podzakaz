<?php
/** @var app\backend\modules\account\models\Customer[] $customers */

use yii\helpers\Html;
use yii\helpers\Url;

$statusPills = [
    10 => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'Активен'],
    9  => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'Заблокирован'],
    1  => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'Активен'],
    0  => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Удалён'],
];

foreach ($customers as $customer):
    $sp = $statusPills[$customer->status] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => $customer->getStatusLabel()];
    $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
    if (!$fullName) $fullName = 'Клиент #' . $customer->id;
?>
<tr data-href="<?= Url::to(['customer/view', 'id' => $customer->id]) ?>">
    <td style="white-space:nowrap;padding:6px 8px;font-weight:700">
        <?= $customer->id ?>
    </td>
    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <div style="font-weight:600"><?= Html::encode($fullName) ?></div>
    </td>
    <td data-col="email" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
        <?= $customer->email ? Html::encode($customer->email) : '—' ?>
    </td>
    <td data-col="phone" style="white-space:nowrap">
        <?= $customer->phone ? Html::encode($customer->phone) : '—' ?>
    </td>
    <td data-col="orders_count" style="text-align:center;font-weight:600">
        <?= (int)$customer->orders_count ?>
    </td>
    <td data-col="total_spent" style="font-weight:700;white-space:nowrap">
        <?= number_format((float)$customer->total_spent, 2) ?> <span style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);font-weight:400">Br</span>
    </td>
    <td data-col="last_order" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280);font-size:.8rem">
        <?= $customer->last_order_at ? date('d.m.Y', $customer->last_order_at) : '—' ?>
    </td>
    <td data-col="status" style="padding:6px 8px">
        <span class="status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>">
            <?= Html::encode($sp['label']) ?>
        </span>
    </td>
    <td style="padding:4px 6px">
        <a href="<?= Url::to(['customer/view', 'id' => $customer->id]) ?>"
           class="admin-btn admin-btn-secondary"
           style="padding:.2rem .45rem;font-size:.875rem;">
            <i class="bi bi-eye"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
