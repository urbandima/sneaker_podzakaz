<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Buyout[] $buyouts */
/** @var array $kpi */
/** @var array $statuses */
/** @var array $sources */
/** @var string $filterStatus */
/** @var string $filterSource */

use yii\helpers\Html;

$this->title = 'Выкупы товаров';
?>
<style>
.buyout-kpi { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.buyout-kpi-card { background:#fff; border:1px solid var(--admin-border,#e5e7eb); border-radius:10px; padding:12px 18px; min-width:120px; flex:1; }
.buyout-kpi-val  { font-size:1.5rem; font-weight:800; color:var(--admin-text-primary,#111); }
.buyout-kpi-label{ font-size:0.72rem; color:var(--admin-text-secondary,#6b7280); text-transform:uppercase; letter-spacing:.05em; margin-top:2px; }

.compact-filter-row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
.compact-filter-row select, .compact-filter-row input[type=date], .compact-filter-row input[type=text] {
    height:34px; padding:0 10px; border:1px solid var(--admin-border,#e5e7eb);
    border-radius:8px; font-size:0.8125rem; background:#fff; color:var(--admin-text-primary,#111);
}
.compact-filter-btn { height:34px; padding:0 14px; border-radius:8px; font-size:0.8125rem; font-weight:600;
    border:1px solid var(--admin-border,#e5e7eb); background:#fff; cursor:pointer; }
.compact-filter-btn--apply { background:var(--admin-accent,#2563eb); color:#fff; border-color:var(--admin-accent,#2563eb); }

.buyout-table { width:100%; border-collapse:collapse; }
.buyout-table th { font-size:0.72rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; padding:8px 10px; border-bottom:2px solid #e5e7eb; white-space:nowrap; }
.buyout-table td { padding:9px 10px; border-bottom:1px solid #f3f4f6; font-size:0.8125rem; vertical-align:middle; }
.buyout-table tr:hover td { background:#f9fafb; }
.status-pill { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:0.72rem; font-weight:700; }
.source-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:0.72rem; font-weight:600;
    background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; }

.bulk-actions { display:flex; gap:8px; align-items:center; padding:8px 0; }
.buyout-select-all { cursor:pointer; }
</style>

<div class="buyout-kpi">
    <div class="buyout-kpi-card">
        <div class="buyout-kpi-val"><?= $kpi['total'] ?></div>
        <div class="buyout-kpi-label">Всего</div>
    </div>
    <div class="buyout-kpi-card">
        <div class="buyout-kpi-val" style="color:#d97706"><?= ($kpi['ordered'] ?? 0) + ($kpi['in_transit'] ?? 0) ?></div>
        <div class="buyout-kpi-label">В процессе</div>
    </div>
    <div class="buyout-kpi-card">
        <div class="buyout-kpi-val" style="color:#059669"><?= $kpi['accepted'] ?? 0 ?></div>
        <div class="buyout-kpi-label">Принято</div>
    </div>
    <div class="buyout-kpi-card">
        <div class="buyout-kpi-val" style="color:#dc2626"><?= $kpi['cancelled'] ?? 0 ?></div>
        <div class="buyout-kpi-label">Отменено</div>
    </div>
    <div class="buyout-kpi-card">
        <div class="buyout-kpi-val"><?= number_format($kpi['total_cost'], 2) ?> BYN</div>
        <div class="buyout-kpi-label">Итого затрат</div>
    </div>
</div>

<form method="GET" action="/admin/procurement/buyouts">
<div class="compact-filter-row">
    <select name="status">
        <option value="">Все статусы</option>
        <?php foreach ($statuses as $k => $v): ?>
        <option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="source">
        <option value="">Все источники</option>
        <?php foreach ($sources as $k => $v): ?>
        <option value="<?= $k ?>" <?= $filterSource === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= Html::encode($filterFrom ?? '') ?>" placeholder="От">
    <input type="date" name="date_to"   value="<?= Html::encode($filterTo ?? '') ?>"   placeholder="До">
    <button type="submit" class="compact-filter-btn compact-filter-btn--apply">Применить</button>
    <a href="/admin/procurement/buyouts" class="compact-filter-btn">Сбросить</a>
    <a href="/admin/procurement/buyout/create" class="compact-filter-btn compact-filter-btn--apply" style="margin-left:auto">
        <i class="bi bi-plus-lg"></i> Новый выкуп
    </a>
</div>
</form>

<!-- Bulk actions -->
<div class="bulk-actions" id="bulk-panel" style="display:none">
    <span id="bulk-count" style="font-size:0.8rem;color:#6b7280"></span>
    <select id="bulk-status-select">
        <?php foreach ($statuses as $k => $v): ?>
        <option value="<?= $k ?>"><?= Html::encode($v) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="compact-filter-btn compact-filter-btn--apply" onclick="bulkChangeStatus()">Применить</button>
</div>

<div class="admin-card" style="overflow-x:auto">
<table class="buyout-table">
<thead>
    <tr>
        <th><input type="checkbox" class="buyout-select-all" id="chk-all" onchange="toggleAll(this)"></th>
        <th>#</th>
        <th>Товар</th>
        <th>Источник</th>
        <th>Размер</th>
        <th>Кол-во</th>
        <th>Цена ед.</th>
        <th>Итого</th>
        <th>Статус</th>
        <th>Заказан</th>
        <th>Закупщик</th>
        <th>Заказы</th>
        <th></th>
    </tr>
</thead>
<tbody>
<?php if (empty($buyouts)): ?>
<tr><td colspan="13" style="text-align:center;padding:32px;color:#9ca3af">Выкупов не найдено</td></tr>
<?php else: ?>
<?php foreach ($buyouts as $b): ?>
<?php $snap = is_array($b->product_snapshot) ? $b->product_snapshot : (array)json_decode((string)$b->product_snapshot, true); ?>
<tr data-id="<?= $b->id ?>">
    <td><input type="checkbox" class="buyout-chk" value="<?= $b->id ?>" onchange="onCheckChange()"></td>
    <td><a href="/admin/procurement/buyout/<?= $b->id ?>" style="font-weight:700;color:var(--admin-accent,#2563eb)"><?= $b->id ?></a></td>
    <td>
        <?php if (!empty($snap['image'])): ?>
        <img src="<?= Html::encode($snap['image']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;margin-right:6px;vertical-align:middle">
        <?php endif; ?>
        <a href="/admin/procurement/buyout/<?= $b->id ?>" style="font-weight:600;color:var(--admin-text-primary,#111);text-decoration:none">
            <?= Html::encode($b->getProductName()) ?>
        </a>
    </td>
    <td><span class="source-badge"><?= Html::encode($b->getSourceLabel()) ?></span></td>
    <td><?= Html::encode($b->size ?? '—') ?></td>
    <td><?= (int)$b->qty ?></td>
    <td><?= $b->unit_cost_byn ? number_format((float)$b->unit_cost_byn, 2) . ' BYN' : '—' ?></td>
    <td style="font-weight:700"><?= $b->total_cost_byn ? number_format((float)$b->total_cost_byn, 2) . ' BYN' : '—' ?></td>
    <td>
        <span class="status-pill" style="background:<?= $b->getStatusBg() ?>;color:<?= $b->getStatusColor() ?>">
            <?= Html::encode($b->getStatusLabel()) ?>
        </span>
    </td>
    <td style="color:#6b7280;font-size:0.75rem"><?= $b->ordered_at ? date('d.m.y', strtotime($b->ordered_at)) : '—' ?></td>
    <td style="color:#6b7280;font-size:0.75rem"><?= Html::encode($b->buyer_user_id ?? '—') ?></td>
    <td style="font-size:0.75rem"><?= count($b->orderLinks) ?></td>
    <td>
        <a href="/admin/procurement/buyout/<?= $b->id ?>" class="admin-btn admin-btn-sm admin-btn-secondary" title="Открыть">
            <i class="bi bi-eye"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<script>
function toggleAll(cb) {
    document.querySelectorAll('.buyout-chk').forEach(c => c.checked = cb.checked);
    onCheckChange();
}
function onCheckChange() {
    const checked = document.querySelectorAll('.buyout-chk:checked');
    const panel = document.getElementById('bulk-panel');
    panel.style.display = checked.length ? 'flex' : 'none';
    document.getElementById('bulk-count').textContent = 'Выбрано: ' + checked.length;
}
function bulkChangeStatus() {
    const ids = [...document.querySelectorAll('.buyout-chk:checked')].map(c => +c.value);
    const status = document.getElementById('bulk-status-select').value;
    if (!ids.length) return;
    if (!confirm('Сменить статус у ' + ids.length + ' выкупов?')) return;

    fetch('/admin/procurement/buyout/bulk-status', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':yii.getCsrfToken()},
        body: JSON.stringify({ids, status})
    }).then(r=>r.json()).then(d=>{
        if (d.success) { alert('Обновлено: ' + d.updated); location.reload(); }
        else alert('Ошибка: ' + (d.message||''));
    });
}
</script>
