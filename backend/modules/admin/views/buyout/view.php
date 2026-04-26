<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Buyout $buyout */
/** @var app\backend\modules\procurement\models\BuyoutOrderLink[] $links */
/** @var app\backend\modules\procurement\models\BuyoutHistory[] $histories */
/** @var array $statuses */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Выкуп #' . $buyout->id;
$snap = is_array($buyout->product_snapshot) ? $buyout->product_snapshot : (array)json_decode((string)$buyout->product_snapshot, true);
$allowed = $buyout->getAllowedTransitions();
?>
<style>
/* Reuse .crm-* classes defined in order/view.php which is already in the layout */
</style>

<div class="crm-wrap">

<!-- ── Topbar ─────────────────────────────────────────────────────────────── -->
<div class="crm-topbar">
    <div class="crm-topbar-left">
        <a href="/admin/procurement/buyouts" class="admin-btn admin-btn-sm admin-btn-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <span class="crm-order-num">Выкуп #<?= $buyout->id ?></span>
        <span class="crm-order-date"><?= date('d.m.Y H:i', strtotime($buyout->created_at)) ?></span>
        <span class="crm-status-pill" id="status-pill"
              style="background:<?= $buyout->getStatusBg() ?>;color:<?= $buyout->getStatusColor() ?>">
            <?= Html::encode($buyout->getStatusLabel()) ?>
        </span>
    </div>
    <div class="crm-topbar-actions">
        <?php foreach ($allowed as $s): ?>
        <button class="admin-btn admin-btn-sm admin-btn-primary" onclick="changeStatus('<?= $s ?>')">
            → <?= Html::encode($statuses[$s] ?? $s) ?>
        </button>
        <?php endforeach; ?>
        <?php if (in_array($buyout->status, [\app\backend\modules\procurement\models\Buyout::STATUS_ARRIVED], true)): ?>
        <button class="admin-btn admin-btn-sm" style="background:#059669;color:#fff" onclick="acceptBuyout()">
            <i class="bi bi-check-circle"></i> Принять (создать приёмку)
        </button>
        <?php endif; ?>
        <a href="/admin/procurement/buyout/<?= $buyout->id ?>/edit" class="admin-btn admin-btn-sm admin-btn-secondary">
            <i class="bi bi-pencil"></i> Редактировать
        </a>
    </div>
</div>

<!-- ── Body ───────────────────────────────────────────────────────────────── -->
<div class="crm-body">

<!-- Left: main content -->
<div class="crm-main">

    <!-- Product card -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-box-seam"></i> Товар</h3></div>
        <div class="crm-card-body" style="display:flex;gap:16px;align-items:flex-start">
            <?php if (!empty($snap['image'])): ?>
            <img src="<?= Html::encode($snap['image']) ?>" alt=""
                 style="width:100px;height:100px;object-fit:cover;border-radius:10px;flex-shrink:0;border:1px solid #e5e7eb">
            <?php endif; ?>
            <div style="flex:1;min-width:0">
                <div style="font-size:1rem;font-weight:700;margin-bottom:4px"><?= Html::encode($buyout->getProductName()) ?></div>
                <?php if (!empty($snap['brand'])): ?>
                <div style="font-size:0.8rem;color:#6b7280;margin-bottom:6px"><?= Html::encode($snap['brand']) ?></div>
                <?php endif; ?>
                <?php if ($buyout->size): ?>
                <div class="crm-field-row"><span class="crm-field-label">Размер</span><span class="crm-field-val"><?= Html::encode($buyout->size) ?></span></div>
                <?php endif; ?>
                <?php if ($buyout->source_url): ?>
                <div style="margin-top:8px">
                    <a href="<?= Html::encode($buyout->source_url) ?>" target="_blank" class="admin-btn admin-btn-sm admin-btn-secondary">
                        <i class="bi bi-box-arrow-up-right"></i> Открыть источник
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Financials -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-cash-stack"></i> Финансы</h3></div>
        <div class="crm-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="crm-field-row"><span class="crm-field-label">Цена ед. (источник)</span>
                    <span class="crm-field-val"><?= $buyout->unit_cost_source ? number_format((float)$buyout->unit_cost_source, 2) . ' ' . $buyout->source_currency : '—' ?></span></div>
                <div class="crm-field-row"><span class="crm-field-label">Курс</span>
                    <span class="crm-field-val"><?= $buyout->exchange_rate ? number_format((float)$buyout->exchange_rate, 4) : '—' ?></span></div>
                <div class="crm-field-row"><span class="crm-field-label">Цена ед. (BYN)</span>
                    <span class="crm-field-val"><?= $buyout->unit_cost_byn ? number_format((float)$buyout->unit_cost_byn, 2) . ' BYN' : '—' ?></span></div>
                <div class="crm-field-row"><span class="crm-field-label">Кол-во</span>
                    <span class="crm-field-val"><?= (int)$buyout->qty ?></span></div>
                <div class="crm-field-row"><span class="crm-field-label">Доставка</span>
                    <span class="crm-field-val"><?= number_format((float)$buyout->shipping_cost, 2) ?> BYN</span></div>
                <div class="crm-field-row"><span class="crm-field-label">Комиссии/пошлины</span>
                    <span class="crm-field-val"><?= number_format((float)$buyout->fees, 2) ?> BYN</span></div>
                <div class="crm-field-row" style="grid-column:1/-1;border-top:1px solid #e5e7eb;padding-top:8px;margin-top:4px">
                    <span class="crm-field-label" style="font-weight:700">Итого</span>
                    <span class="crm-field-val" style="font-size:1.1rem;font-weight:800;color:var(--admin-accent,#2563eb)">
                        <?= number_format((float)$buyout->total_cost_byn, 2) ?> BYN
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Linked Orders -->
    <div class="crm-card">
        <div class="crm-card-head">
            <h3><i class="bi bi-link-45deg"></i> Связанные заказы (<?= count($links) ?>)</h3>
            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="showLinkOrderModal()">
                <i class="bi bi-plus-lg"></i> Привязать
            </button>
        </div>
        <div class="crm-card-body">
            <?php if (empty($links)): ?>
            <div style="color:#9ca3af;font-size:0.85rem;padding:8px 0">Нет привязанных заказов</div>
            <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:0.8125rem">
                <tr style="color:#6b7280;font-size:0.72rem;border-bottom:1px solid #e5e7eb">
                    <th style="padding:6px;text-align:left">Заказ</th>
                    <th style="padding:6px;text-align:left">Клиент</th>
                    <th style="padding:6px;text-align:left">Статус</th>
                    <th style="padding:6px"></th>
                </tr>
                <?php foreach ($links as $link): ?>
                <?php $order = $link->order; ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:6px">
                        <?php if ($order): ?>
                        <a href="/admin/order/<?= $order->id ?>" style="font-weight:700;color:var(--admin-accent,#2563eb)">
                            #<?= Html::encode($order->order_number ?? $order->id) ?>
                        </a>
                        <?php else: ?>#<?= $link->order_id ?><?php endif; ?>
                    </td>
                    <td style="padding:6px;color:#374151">
                        <?= $order ? Html::encode($order->name ?? '—') : '—' ?>
                    </td>
                    <td style="padding:6px">
                        <?php if ($order): ?>
                        <span style="font-size:0.72rem;padding:2px 7px;background:#f3f4f6;border-radius:999px">
                            <?= Html::encode($order->getStatusLabel()) ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:6px;text-align:right">
                        <button class="admin-btn admin-btn-sm admin-btn-danger"
                                onclick="unlinkOrder(<?= $buyout->id ?>, <?= $link->order_id ?>)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- History Timeline -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-clock-history"></i> История</h3></div>
        <div class="crm-card-body" style="padding:0">
            <?php if (empty($histories)): ?>
            <div style="padding:16px;color:#9ca3af;font-size:0.85rem">Нет событий</div>
            <?php else: ?>
            <ul style="list-style:none;margin:0;padding:0">
                <?php foreach ($histories as $h): ?>
                <?php
                    $nv = is_array($h->new_value) ? $h->new_value : (array)json_decode((string)$h->new_value, true);
                    $ov = is_array($h->old_value) ? $h->old_value : (array)json_decode((string)$h->old_value, true);
                ?>
                <li style="display:flex;gap:10px;padding:10px 16px;border-bottom:1px solid #f3f4f6">
                    <div style="width:8px;height:8px;border-radius:50%;background:var(--admin-accent,#2563eb);margin-top:5px;flex-shrink:0"></div>
                    <div style="flex:1">
                        <div style="font-size:0.8rem;font-weight:600"><?= Html::encode($h->getActionLabel()) ?></div>
                        <?php if ($h->action === 'status_changed'): ?>
                        <div style="font-size:0.75rem;color:#6b7280">
                            <?= Html::encode($ov['status'] ?? '—') ?> → <?= Html::encode($nv['status'] ?? '—') ?>
                        </div>
                        <?php endif; ?>
                        <div style="font-size:0.72rem;color:#9ca3af;margin-top:2px">
                            <?= date('d.m.Y H:i', strtotime($h->created_at)) ?>
                            <?= $h->user_id ? '· #' . $h->user_id : '' ?>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /crm-main -->

<!-- Right: Sidebar -->
<div class="crm-sidebar">

    <!-- Source info -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-shop"></i> Источник</h3></div>
        <div class="crm-card-body">
            <div class="crm-field-row"><span class="crm-field-label">Платформа</span>
                <span class="crm-field-val"><?= Html::encode($buyout->getSourceLabel()) ?></span></div>
            <?php if ($buyout->external_id): ?>
            <div class="crm-field-row"><span class="crm-field-label">Внешний ID</span>
                <span class="crm-field-val"><?= Html::encode($buyout->external_id) ?></span></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Buyer -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-person"></i> Закупщик</h3></div>
        <div class="crm-card-body">
            <div class="crm-field-val"><?= Html::encode($buyout->buyer_user_id ? '#' . $buyout->buyer_user_id : '—') ?></div>
        </div>
    </div>

    <!-- Tracking -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-truck"></i> Трекинг</h3></div>
        <div class="crm-card-body">
            <div class="crm-field-row"><span class="crm-field-label">Трек-номер</span>
                <span class="crm-field-val"><?= Html::encode($buyout->tracking_number ?? '—') ?></span></div>
            <div class="crm-field-row"><span class="crm-field-label">Перевозчик</span>
                <span class="crm-field-val"><?= Html::encode($buyout->carrier ?? '—') ?></span></div>
        </div>
    </div>

    <!-- Receipt -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-receipt"></i> Чек / инвойс</h3></div>
        <div class="crm-card-body">
            <?php if ($buyout->receipt_url): ?>
            <a href="<?= Html::encode($buyout->receipt_url) ?>" target="_blank" class="admin-btn admin-btn-sm admin-btn-secondary">
                <i class="bi bi-file-earmark"></i> Открыть
            </a>
            <?php else: ?>
            <div style="color:#9ca3af;font-size:0.8rem">Не загружен</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dates -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-calendar3"></i> Даты</h3></div>
        <div class="crm-card-body">
            <div class="crm-field-row"><span class="crm-field-label">Создан</span>
                <span class="crm-field-val"><?= date('d.m.Y H:i', strtotime($buyout->created_at)) ?></span></div>
            <div class="crm-field-row"><span class="crm-field-label">Заказан</span>
                <span class="crm-field-val"><?= $buyout->ordered_at ? date('d.m.Y H:i', strtotime($buyout->ordered_at)) : '—' ?></span></div>
            <div class="crm-field-row"><span class="crm-field-label">Прибыл</span>
                <span class="crm-field-val"><?= $buyout->arrived_at ? date('d.m.Y H:i', strtotime($buyout->arrived_at)) : '—' ?></span></div>
            <div class="crm-field-row"><span class="crm-field-label">Принят</span>
                <span class="crm-field-val"><?= $buyout->accepted_at ? date('d.m.Y H:i', strtotime($buyout->accepted_at)) : '—' ?></span></div>
        </div>
    </div>

    <?php if ($buyout->notes): ?>
    <!-- Notes -->
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-sticky"></i> Заметки</h3></div>
        <div class="crm-card-body" style="white-space:pre-wrap;font-size:0.8125rem;color:#374151">
            <?= Html::encode($buyout->notes) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($buyout->receiving_id): ?>
    <div class="crm-card">
        <div class="crm-card-head"><h3><i class="bi bi-box-arrow-in-down"></i> Приёмка</h3></div>
        <div class="crm-card-body">
            <a href="/admin/procurement/view/<?= $buyout->receiving_id ?>" class="admin-btn admin-btn-sm admin-btn-primary">
                Приёмка #<?= $buyout->receiving_id ?>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /crm-sidebar -->
</div><!-- /crm-body -->
</div><!-- /crm-wrap -->

<!-- Link Order Modal -->
<div id="link-order-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:14px;padding:24px;width:420px;max-width:90vw">
        <h3 style="margin:0 0 16px;font-size:1rem">Привязать заказ</h3>
        <input type="text" id="link-order-input" placeholder="Номер заказа или ID" style="width:100%;padding:8px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:0.875rem;box-sizing:border-box">
        <div style="display:flex;gap:8px;margin-top:14px;justify-content:flex-end">
            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="hideLinkOrderModal()">Отмена</button>
            <button class="admin-btn admin-btn-sm admin-btn-primary" onclick="doLinkOrder()">Привязать</button>
        </div>
    </div>
</div>

<script>
function changeStatus(status) {
    if (!confirm('Сменить статус?')) return;
    fetch('/admin/procurement/buyout/update-status', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':yii.getCsrfToken()},
        body: JSON.stringify({id: <?= $buyout->id ?>, status})
    }).then(r=>r.json()).then(d=>{
        if (d.success) location.reload();
        else alert('Ошибка: ' + (d.message||''));
    });
}
function acceptBuyout() {
    if (!confirm('Принять выкуп и создать приёмку?')) return;
    fetch('/admin/procurement/buyout/<?= $buyout->id ?>/accept', {
        method: 'POST',
        headers: {'X-CSRF-Token':yii.getCsrfToken()}
    }).then(r=>r.json()).then(d=>{
        if (d.success) { alert('Приёмка создана #' + d.receiving_id); location.reload(); }
        else alert('Ошибка: ' + (d.message||''));
    });
}
function showLinkOrderModal() { document.getElementById('link-order-modal').style.display='flex'; }
function hideLinkOrderModal() { document.getElementById('link-order-modal').style.display='none'; }
function doLinkOrder() {
    const val = document.getElementById('link-order-input').value.trim();
    if (!val) return;
    fetch('/admin/procurement/buyout/link-order', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':yii.getCsrfToken()},
        body: JSON.stringify({buyout_id: <?= $buyout->id ?>, order_id: parseInt(val)||0, qty: 1})
    }).then(r=>r.json()).then(d=>{
        if (d.success) { hideLinkOrderModal(); location.reload(); }
        else alert('Ошибка: ' + (d.message||''));
    });
}
function unlinkOrder(buyoutId, orderId) {
    if (!confirm('Отвязать заказ #' + orderId + '?')) return;
    fetch('/admin/procurement/buyout/unlink-order', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':yii.getCsrfToken()},
        body: JSON.stringify({buyout_id: buyoutId, order_id: orderId})
    }).then(r=>r.json()).then(d=>{
        if (d.success) location.reload();
        else alert('Ошибка');
    });
}
</script>
