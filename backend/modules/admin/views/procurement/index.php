<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\PurchaseOrder[] $orders */
/** @var app\backend\modules\procurement\models\Supplier[] $suppliers */
/** @var string $filterStatus, $filterType */
/** @var array $statuses, $types */
$this->title = 'Закупки';

$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

$statusPills = [
    'draft'      => ['bg' => '#f3f4f6', 'color' => '#6d7175'],
    'ordered'    => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'confirmed'  => ['bg' => '#faf5ff', 'color' => '#7c3aed'],
    'shipped'    => ['bg' => '#fffbeb', 'color' => '#d97706'],
    'received'   => ['bg' => '#d1f7e5', 'color' => '#008060'],
    'cancelled'  => ['bg' => '#fbeae5', 'color' => '#d72c0d'],
];

$columnDefs = [
    'type'       => 'Тип',
    'supplier'   => 'Поставщик',
    'amount_cny' => 'Сумма CNY',
    'amount_byn' => 'Сумма BYN',
    'items'      => 'Позиций',
    'ordered_at' => 'Дата заказа',
];
$storageKey = 'purchaseOrdersColumns';

// Z52: show CNY column only if at least one order has a CNY amount
$hasCnyColumn = false;
foreach ($orders as $_o) {
    if (!empty($_o->total_amount_cny)) { $hasCnyColumn = true; break; }
}
?>

<!-- Filter bar -->
<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <select name="status" class="compact-filter-select">
            <option value="">Все статусы</option>
            <?php foreach ($statuses as $k => $v): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="compact-filter-select">
            <option value="">Все типы</option>
            <?php foreach ($types as $k => $v): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply"><i class="bi bi-search"></i> Найти</button>
        <a href="?" class="compact-filter-btn compact-filter-btn--reset"><i class="bi bi-x-lg"></i> Сброс</a>
        <div class="col-selector-wrap" style="margin-left:auto">
            <button type="button" class="compact-filter-btn" onclick="AdminTable.toggleColSelector(event)">
                <i class="bi bi-layout-three-columns"></i> Столбцы
            </button>
            <div id="colSelector" class="col-selector-dropdown" style="display:none">
                <div style="font-weight:700;margin-bottom:8px;font-size:.8125rem">Показать столбцы:</div>
                <?php foreach ($columnDefs as $colKey => $colLabel): ?>
                <label class="col-selector-item">
                    <input type="checkbox" data-col="<?= $colKey ?>"
                           onchange="AdminTable.toggleColumn('<?= $colKey ?>', this.checked, '<?= $storageKey ?>')" checked>
                    <?= htmlspecialchars($colLabel) ?>
                </label>
                <?php endforeach; ?>
                <div style="margin-top:8px;border-top:1px solid var(--admin-border,#e1e3e5);padding-top:8px;display:flex;gap:6px">
                    <button type="button" onclick="AdminTable.selectAllCols(true,'<?= $storageKey ?>')"  class="compact-filter-btn compact-filter-btn--apply"  style="flex:1;height:26px;font-size:11px;padding:0 8px">Все</button>
                    <button type="button" onclick="AdminTable.selectAllCols(false,'<?= $storageKey ?>')" class="compact-filter-btn compact-filter-btn--reset" style="flex:1;height:26px;font-size:11px;padding:0 8px">Скрыть</button>
                </div>
            </div>
        </div>
        <a href="/admin/procurement/create" class="compact-filter-btn compact-filter-btn--apply" style="margin-left:4px">
            <i class="bi bi-plus-lg"></i> Новая закупка
        </a>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-cart3"></i> Закупки
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($orders) ?></span>
        </h2>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th data-sort="purchase_number" onclick="AdminTable.sortBy('purchase_number')">Номер <?= $sortIcon('purchase_number') ?></th>
                    <th data-col="type">Тип</th>
                    <th data-col="supplier">Поставщик</th>
                    <th>Статус</th>
                    <?php if ($hasCnyColumn): ?><th data-col="amount_cny" data-sort="total_amount_cny" onclick="AdminTable.sortBy('total_amount_cny')" style="text-align:right">Сумма CNY <?= $sortIcon('total_amount_cny') ?></th><?php endif; ?>
                    <th data-col="amount_byn" data-sort="total_amount_byn" onclick="AdminTable.sortBy('total_amount_byn')" style="text-align:right">Сумма BYN <?= $sortIcon('total_amount_byn') ?></th>
                    <th data-col="items" style="text-align:center">Позиций</th>
                    <th data-col="ordered_at" data-sort="ordered_at" onclick="AdminTable.sortBy('ordered_at')">Дата заказа <?= $sortIcon('ordered_at') ?></th>
                    <th data-sort="created_at" onclick="AdminTable.sortBy('created_at')">Создан <?= $sortIcon('created_at') ?></th>
                    <th style="width:80px">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$orders): ?>
                    <tr><td colspan="10" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Закупок нет</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $po):
                    $sp = $statusPills[$po->status] ?? ['bg' => '#f3f4f6', 'color' => '#6d7175'];
                ?>
                <tr>
                    <td style="white-space:nowrap">
                        <a href="/admin/procurement/view/<?= $po->id ?>" style="font-weight:700;color:var(--admin-text-primary,#202223);text-decoration:none">
                            <?= htmlspecialchars($po->purchase_number ?: sprintf('%05d', $po->id)) ?>
                        </a>
                    </td>
                    <td data-col="type" style="color:var(--admin-text-secondary,#6d7175)"><?= $po->getTypeLabel() ?></td>
                    <td data-col="supplier" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($po->supplier->name ?? '—') ?>
                    </td>
                    <td>
                        <span class="status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>"
                              id="po-status-<?= $po->id ?>">
                            <?= $po->getStatusLabel() ?>
                        </span>
                    </td>
                    <?php if ($hasCnyColumn): ?>
                    <td data-col="amount_cny" style="text-align:right;white-space:nowrap">
                        <?= $po->total_amount_cny ? number_format($po->total_amount_cny, 2) . ' <span style="font-size:.7rem;color:var(--admin-text-secondary,#6d7175)">CNY</span>' : '<span style="color:var(--admin-text-secondary,#6d7175);font-size:.75rem">Будет указано в выкупе</span>' ?>
                    </td>
                    <?php endif; ?>
                    <td data-col="amount_byn" style="text-align:right;font-weight:700;white-space:nowrap">
                        <?= $po->total_amount_byn ? number_format($po->total_amount_byn, 2) . ' <span style="font-size:.7rem;color:var(--admin-text-secondary,#6d7175);font-weight:400">BYN</span>' : '—' ?>
                    </td>
                    <td data-col="items" style="text-align:center"><?= count($po->items) ?></td>
                    <td data-col="ordered_at" style="white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= $po->ordered_at ? date('d.m.Y', strtotime($po->ordered_at)) : '—' ?>
                    </td>
                    <td style="white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= $po->created_at ? date('d.m.Y', strtotime($po->created_at)) : '—' ?>
                    </td>
                    <td style="padding:4px 6px">
                        <div style="position:relative;display:inline-block" class="po-dropdown-wrap">
                            <button class="admin-btn admin-btn-sm admin-btn-secondary"
                                    onclick="togglePODropdown(<?= $po->id ?>)" title="Сменить статус">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <div id="po-dd-<?= $po->id ?>" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e1e3e5);border-radius:8px;padding:4px;z-index:200;min-width:140px;box-shadow:0 4px 16px rgba(0,0,0,.1)">
                                <?php foreach ($statuses as $k => $v): ?>
                                    <?php if ($k !== $po->status): ?>
                                    <a href="#" onclick="updateStatus(<?= $po->id ?>, '<?= $k ?>', this); return false"
                                       style="display:block;padding:5px 10px;font-size:.8125rem;color:var(--admin-text-primary,#202223);text-decoration:none;border-radius:5px;white-space:nowrap"
                                       onmouseover="this.style.background='var(--admin-surface-hover,#f3f4f6)'"
                                       onmouseout="this.style.background=''">
                                        <?= htmlspecialchars($v) ?>
                                    </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <a href="/admin/procurement/view/<?= $po->id ?>" class="admin-btn admin-btn-sm admin-btn-secondary" title="Открыть">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

AdminTable.initColSelector('<?= $storageKey ?>');

function togglePODropdown(id) {
    document.querySelectorAll('[id^="po-dd-"]').forEach(function(d) {
        if (d.id !== 'po-dd-' + id) d.style.display = 'none';
    });
    var dd = document.getElementById('po-dd-' + id);
    if (dd) dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.po-dropdown-wrap')) {
        document.querySelectorAll('[id^="po-dd-"]').forEach(function(d) { d.style.display = 'none'; });
    }
});

function updateStatus(id, status, el) {
    fetch('/admin/procurement/update-status', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({id, status})
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка');
    });
}
</script>
