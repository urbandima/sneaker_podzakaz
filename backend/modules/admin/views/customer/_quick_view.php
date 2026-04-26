<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\account\models\Customer $customer */
/** @var int $loyaltyBalance */
/** @var app\backend\modules\checkout\models\Order[] $recentOrders */
/** @var array $notes */
/** @var array $tags */

use yii\helpers\Html;
use yii\helpers\Url;

$fullName = trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? ''));
if (!$fullName) $fullName = $customer->email;

$statusColors = [
    'new' => '#6b7280', 'created' => '#6b7280',
    'confirmed_and_paid' => '#059669', 'paid' => '#059669',
    'processing' => '#d97706', 'shipped' => '#2563eb', 'in_transit' => '#2563eb',
    'delivered' => '#16a34a', 'cancelled' => '#dc2626', 'returned' => '#dc2626',
];
$statusBgColors = [
    'new' => '#f3f4f6', 'created' => '#f3f4f6',
    'confirmed_and_paid' => '#d1fae5', 'paid' => '#d1fae5',
    'processing' => '#fef3c7', 'shipped' => '#dbeafe', 'in_transit' => '#dbeafe',
    'delivered' => '#dcfce7', 'cancelled' => '#fee2e2', 'returned' => '#fee2e2',
];
try {
    $statuses = Yii::$app->settings->getStatuses();
} catch (\Exception $e) {
    $statuses = [];
}
?>

<!-- Hero row -->
<div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:16px">
    <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.25rem;flex-shrink:0">
        <?= mb_strtoupper(mb_substr($customer->first_name ?? $customer->email ?? 'C', 0, 1)) ?>
    </div>
    <div style="flex:1;min-width:0">
        <div style="font-weight:800;font-size:1rem;color:var(--admin-text-primary,#111);margin-bottom:2px"><?= Html::encode($fullName) ?></div>
        <div style="font-size:0.75rem;color:var(--admin-text-secondary,#6b7280);display:flex;flex-wrap:wrap;gap:10px;align-items:center">
            <?php if ($customer->email): ?>
            <a href="mailto:<?= Html::encode($customer->email) ?>" style="color:inherit;text-decoration:none"><i class="bi bi-envelope"></i> <?= Html::encode($customer->email) ?></a>
            <?php endif; ?>
            <?php if ($customer->phone): ?>
            <a href="tel:<?= Html::encode($customer->phone) ?>" style="color:inherit;text-decoration:none"><i class="bi bi-telephone"></i> <?= Html::encode($customer->phone) ?></a>
            <?php endif; ?>
        </div>
        <div style="margin-top:4px">
            <?php
            $statusBadge = $customer->status == 1 ? ['bg'=>'#d1fae5','color'=>'#065f46','label'=>'Активен'] : ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Заблокирован'];
            ?>
            <span style="font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:6px;background:<?= $statusBadge['bg'] ?>;color:<?= $statusBadge['color'] ?>"><?= $statusBadge['label'] ?></span>
            <?php if ($customer->email_verified): ?>
            <span style="font-size:0.65rem;font-weight:600;padding:2px 7px;border-radius:6px;background:#dbeafe;color:#1e40af;margin-left:3px"><i class="bi bi-patch-check"></i> Email</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats row -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:18px">
    <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:10px;padding:10px;text-align:center">
        <div style="font-size:1.25rem;font-weight:800;color:var(--admin-text-primary,#111)"><?= $customer->orders_count ?? count($recentOrders) ?></div>
        <div style="font-size:0.65rem;text-transform:uppercase;letter-spacing:.04em;color:var(--admin-text-secondary,#6b7280)">Заказов</div>
    </div>
    <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:10px;padding:10px;text-align:center">
        <div style="font-size:1.125rem;font-weight:800;color:var(--admin-text-primary,#111)"><?= $customer->total_spent ? number_format($customer->total_spent, 0, '.', ' ') : '—' ?></div>
        <div style="font-size:0.65rem;text-transform:uppercase;letter-spacing:.04em;color:var(--admin-text-secondary,#6b7280)">Потрачено Br</div>
    </div>
    <div style="background:<?= $loyaltyBalance > 0 ? '#fef3c7' : 'var(--admin-surface-hover,#f9fafb)' ?>;border-radius:10px;padding:10px;text-align:center">
        <div style="font-size:1.25rem;font-weight:800;color:<?= $loyaltyBalance > 0 ? '#92400e' : 'var(--admin-text-primary,#111)' ?>"><?= $loyaltyBalance ?></div>
        <div style="font-size:0.65rem;text-transform:uppercase;letter-spacing:.04em;color:var(--admin-text-secondary,#6b7280)">Бонусов</div>
    </div>
</div>

<!-- Personal data -->
<div class="cqv-section">
    <div class="cqv-section-title">Личные данные</div>
    <div class="cqv-grid">
        <div class="cqv-field">
            <div class="cqv-label">Дата рождения</div>
            <div class="cqv-val"><?= $customer->birth_date ? Html::encode(date('d.m.Y', strtotime($customer->birth_date))) : '—' ?></div>
        </div>
        <div class="cqv-field">
            <div class="cqv-label">Пол</div>
            <div class="cqv-val"><?= $customer->gender === 'male' ? 'Мужской' : ($customer->gender === 'female' ? 'Женский' : '—') ?></div>
        </div>
        <?php if ($customer->default_city || $customer->default_country): ?>
        <div class="cqv-field">
            <div class="cqv-label">Город</div>
            <div class="cqv-val"><?= Html::encode($customer->default_city ?? '—') ?></div>
        </div>
        <div class="cqv-field">
            <div class="cqv-label">Страна</div>
            <div class="cqv-val"><?= Html::encode($customer->default_country ?? '—') ?></div>
        </div>
        <?php endif; ?>
        <?php if ($customer->default_address): ?>
        <div class="cqv-field" style="grid-column:span 2">
            <div class="cqv-label">Адрес</div>
            <div class="cqv-val"><?= Html::encode($customer->default_address) ?><?= $customer->default_postal_code ? ', ' . Html::encode($customer->default_postal_code) : '' ?></div>
        </div>
        <?php endif; ?>
        <div class="cqv-field">
            <div class="cqv-label">Регистрация</div>
            <div class="cqv-val"><?= $customer->created_at ? date('d.m.Y', is_numeric($customer->created_at) ? $customer->created_at : strtotime($customer->created_at)) : '—' ?></div>
        </div>
        <?php if ($customer->last_login_at): ?>
        <div class="cqv-field">
            <div class="cqv-label">Последний вход</div>
            <div class="cqv-val"><?= date('d.m.Y H:i', is_numeric($customer->last_login_at) ? $customer->last_login_at : strtotime($customer->last_login_at)) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($tags)): ?>
<!-- Tags -->
<div class="cqv-section">
    <div class="cqv-section-title">Теги</div>
    <div>
        <?php foreach ($tags as $tag): ?>
        <span class="cqv-tag"><?= Html::encode($tag) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent orders -->
<div class="cqv-section">
    <div class="cqv-section-title">Последние заказы</div>
    <?php if (empty($recentOrders)): ?>
    <div style="color:var(--admin-text-secondary,#9ca3af);font-size:0.8125rem">Заказов нет.</div>
    <?php else: ?>
    <?php foreach ($recentOrders as $order):
        $sc = $statusColors[$order->status] ?? '#6b7280';
        $sb = $statusBgColors[$order->status] ?? '#f3f4f6';
        $sl = $statuses[$order->status] ?? (method_exists($order, 'getStatusLabel') ? $order->getStatusLabel() : $order->status);
    ?>
    <div class="cqv-order-row">
        <div style="flex:1;min-width:0">
            <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>" style="font-weight:600;color:var(--admin-text-primary,#111);text-decoration:none">
                №<?= Html::encode($order->order_number ?: $order->id) ?>
            </a>
            <span style="font-size:0.75rem;color:var(--admin-text-secondary,#9ca3af);margin-left:6px"><?= Yii::$app->formatter->asDate($order->created_at, 'short') ?></span>
        </div>
        <div style="font-weight:700;font-size:0.875rem;margin-right:8px"><?= Yii::$app->formatter->asDecimal($order->total_amount, 2) ?> Br</div>
        <span style="font-size:0.65rem;font-weight:700;padding:2px 7px;border-radius:6px;background:<?= $sb ?>;color:<?= $sc ?>;white-space:nowrap"><?= Html::encode($sl) ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($notes)): ?>
<!-- Notes -->
<div class="cqv-section">
    <div class="cqv-section-title">Заметки</div>
    <?php foreach (array_slice($notes, 0, 3) as $note): ?>
    <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:8px;padding:8px 12px;margin-bottom:6px;font-size:0.8125rem">
        <div style="display:flex;justify-content:space-between;margin-bottom:3px">
            <strong style="font-size:0.75rem"><?= Html::encode($note['author'] ?? 'Менеджер') ?></strong>
            <span style="font-size:0.7rem;color:var(--admin-text-secondary,#9ca3af)"><?= isset($note['created_at']) ? date('d.m.Y', strtotime($note['created_at'])) : '' ?></span>
        </div>
        <div><?= Html::encode($note['text'] ?? $note['note'] ?? '') ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Footer link -->
<div style="text-align:center;padding-top:4px">
    <a href="<?= Url::to(['/admin/customer/view', 'id' => $customer->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="font-size:12px">
        <i class="bi bi-person-lines-fill"></i> Открыть полный профиль
    </a>
</div>
