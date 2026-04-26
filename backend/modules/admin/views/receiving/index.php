<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\Receiving[] $receivings */
/** @var yii\data\Pagination $pagination */
/** @var array $kpi */
/** @var app\backend\modules\procurement\models\Supplier[] $suppliers */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\backend\modules\procurement\models\Receiving;

$this->title = 'Приёмки товаров';
?>
<style>
.rcv-kpi-bar{display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap}
.rcv-kpi{background:#fff;border-radius:12px;padding:14px 18px;box-shadow:0 1px 4px rgba(0,0,0,.07);flex:1;min-width:160px}
.rcv-kpi-val{font-size:1.6rem;font-weight:800;color:var(--admin-text-primary,#111);line-height:1}
.rcv-kpi-lbl{font-size:.75rem;color:var(--admin-text-secondary,#6b7280);margin-top:4px}
.compact-filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:14px}
.compact-filter-input,.compact-filter-select{height:32px;border:1.5px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:0 10px;font-size:.8125rem;background:#fff;color:var(--admin-text-primary,#111)}
.compact-filter-btn{height:32px;padding:0 12px;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:5px;background:var(--admin-surface-hover,#f3f4f6);color:#374151}
.compact-filter-btn--apply{background:var(--admin-primary,#2563eb);color:#fff}
.compact-filter-btn--apply:hover{background:#1d4ed8;color:#fff}
.compact-filter-btn:hover{background:#e5e7eb}
.status-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;color:#fff}
.crm-table{width:100%;border-collapse:collapse;font-size:.8125rem}
.crm-table thead th{background:#fff;padding:9px 12px;text-align:left;font-size:.72rem;font-weight:700;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid var(--admin-border,#e5e7eb);position:sticky;top:0;z-index:10}
.crm-table tbody td{padding:9px 12px;border-bottom:1px solid var(--admin-border,#e5e7eb);vertical-align:middle}
.crm-table tbody tr:hover{background:var(--admin-surface-hover,#f9fafb)}
.crm-table tbody tr:hover td:first-child{border-left:3px solid var(--admin-primary,#2563eb)}
.crm-table tbody td:first-child{border-left:3px solid transparent}
.rcv-num{font-weight:700;color:var(--admin-primary,#2563eb);text-decoration:none}
.rcv-num:hover{text-decoration:underline}
.rcv-supplier{font-size:.8125rem;color:var(--admin-text-secondary,#6b7280)}
.rcv-progress-mini{height:4px;background:#e5e7eb;border-radius:4px;width:70px;display:inline-block;vertical-align:middle;margin-left:6px}
.rcv-progress-mini-bar{height:100%;border-radius:4px;background:#059669;transition:width .3s}
.page-header-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.btn-primary{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--admin-primary,#2563eb);color:#fff;border:none;border-radius:8px;font-size:.875rem;font-weight:600;cursor:pointer;text-decoration:none}
.btn-primary:hover{background:#1d4ed8;color:#fff;text-decoration:none}
</style>

<div class="page-header-row">
    <h1 style="font-size:1.25rem;font-weight:800;margin:0">Приёмки товаров</h1>
    <a href="<?= Url::to(['/admin/receiving/create']) ?>" class="btn-primary">
        <i class="bi bi-plus-lg"></i> Новая приёмка
    </a>
</div>

<!-- KPI -->
<div class="rcv-kpi-bar">
    <div class="rcv-kpi">
        <div class="rcv-kpi-val"><?= $kpi['in_transit'] ?></div>
        <div class="rcv-kpi-lbl"><i class="bi bi-truck"></i> В пути</div>
    </div>
    <div class="rcv-kpi">
        <div class="rcv-kpi-val"><?= $kpi['arrived'] ?></div>
        <div class="rcv-kpi-lbl"><i class="bi bi-box-arrow-in-down"></i> Прибыло / Проверка</div>
    </div>
    <div class="rcv-kpi">
        <div class="rcv-kpi-val"><?= $kpi['accepted_month'] ?></div>
        <div class="rcv-kpi-lbl"><i class="bi bi-check-circle"></i> Принято в <?= date('F') ?></div>
    </div>
    <div class="rcv-kpi">
        <div class="rcv-kpi-val"><?= number_format($kpi['total_month_byn'], 2) ?></div>
        <div class="rcv-kpi-lbl"><i class="bi bi-currency-exchange"></i> BYN за месяц</div>
    </div>
</div>

<!-- Filters -->
<form method="get" class="compact-filter-bar">
    <select name="status" class="compact-filter-select" style="min-width:140px">
        <option value="">Все статусы</option>
        <?php foreach (Receiving::getStatuses() as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="supplier_id" class="compact-filter-select" style="min-width:160px">
        <option value="">Все поставщики</option>
        <?php foreach ($suppliers as $s): ?>
            <option value="<?= $s->id ?>" <?= $supplierId == $s->id ? 'selected' : '' ?>><?= htmlspecialchars($s->name) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" class="compact-filter-input" value="<?= htmlspecialchars($dateFrom) ?>" title="Дата от">
    <input type="date" name="date_to"   class="compact-filter-input" value="<?= htmlspecialchars($dateTo) ?>"   title="Дата до">
    <input type="text" name="q" class="compact-filter-input" placeholder="Номер…" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="compact-filter-btn compact-filter-btn--apply"><i class="bi bi-search"></i> Найти</button>
    <a href="<?= Url::to(['/admin/receiving']) ?>" class="compact-filter-btn"><i class="bi bi-x-lg"></i> Сброс</a>
</form>

<!-- Table -->
<div style="background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden">
<table class="crm-table">
    <thead>
        <tr>
            <th>№</th>
            <th>Поставщик / Источник</th>
            <th>Статус</th>
            <th>Товаров</th>
            <th>Прибыло / Ожидалось</th>
            <th>Итого BYN</th>
            <th>Ожидалась</th>
            <th>Прибыла</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$receivings): ?>
        <tr><td colspan="8" style="text-align:center;padding:32px;color:#9ca3af">Нет приёмок</td></tr>
    <?php endif; ?>
    <?php foreach ($receivings as $r): ?>
    <?php
        $pct = $r->total_qty_expected > 0
            ? min(100, round($r->total_qty_arrived / $r->total_qty_expected * 100))
            : 0;
    ?>
    <tr>
        <td>
            <a href="<?= Url::to(['/admin/receiving/view', 'id' => $r->id]) ?>" class="rcv-num"><?= Html::encode($r->number) ?></a>
        </td>
        <td class="rcv-supplier">
            <?php if ($r->supplier_id && $r->supplier): ?>
                <i class="bi bi-building"></i> <?= Html::encode($r->supplier->name) ?>
            <?php elseif ($r->buyout_id): ?>
                <i class="bi bi-bag-check"></i> Выкуп #<?= $r->buyout_id ?>
            <?php else: ?>
                <span style="color:#d1d5db">—</span>
            <?php endif; ?>
        </td>
        <td>
            <span class="status-pill" style="background:<?= $r->getStatusColor() ?>">
                <?= $r->getStatusLabel() ?>
            </span>
        </td>
        <td><?= $r->total_items ?></td>
        <td>
            <?= $r->total_qty_arrived ?> / <?= $r->total_qty_expected ?>
            <span class="rcv-progress-mini">
                <span class="rcv-progress-mini-bar" style="width:<?= $pct ?>%"></span>
            </span>
        </td>
        <td style="font-weight:600"><?= number_format($r->total_with_expenses_byn, 2) ?></td>
        <td style="color:#6b7280;font-size:.78rem">
            <?= $r->expected_date ? date('d.m.Y', strtotime($r->expected_date)) : '—' ?>
        </td>
        <td style="color:#6b7280;font-size:.78rem">
            <?= $r->arrived_date ? date('d.m.Y', strtotime($r->arrived_date)) : '—' ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($pagination->pageCount > 1): ?>
<div style="margin-top:16px;display:flex;justify-content:center">
    <?= LinkPager::widget(['pagination' => $pagination]) ?>
</div>
<?php endif; ?>
