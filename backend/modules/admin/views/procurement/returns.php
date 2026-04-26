<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\SupplierReturn[] $returns */
/** @var string $filterStatus */
/** @var array $statuses */
$this->title = 'Возвраты поставщику';

$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

$statusPills = [
    'pending'    => ['bg' => '#fff4e5', 'color' => '#ffa500'],
    'in_transit' => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'received'   => ['bg' => '#d1f7e5', 'color' => '#008060'],
    'cancelled'  => ['bg' => '#fbeae5', 'color' => '#d72c0d'],
];
?>

<!-- Page header with create button -->
<div class="admin-page-header" style="display:flex;flex-wrap:nowrap;align-items:center;gap:12px;margin-bottom:16px">
    <h1 class="admin-page-title" style="margin:0;min-width:0;flex:1">Возвраты поставщику</h1>
    <a href="/admin/procurement/create-return/0" class="admin-btn admin-btn-primary admin-btn-sm" style="flex-shrink:0">
        <i class="bi bi-plus-lg"></i> Создать возврат
    </a>
</div>

<!-- Filter bar -->
<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <select name="status" class="compact-filter-select" style="min-width:180px">
            <option value="">Все статусы</option>
            <?php foreach ($statuses as $k => $v): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply"><i class="bi bi-search"></i> Найти</button>
        <a href="?" class="compact-filter-btn compact-filter-btn--reset"><i class="bi bi-x-lg"></i> Сброс</a>
        <a href="/admin/procurement" class="compact-filter-btn" style="margin-left:auto">
            <i class="bi bi-arrow-left"></i> Закупки
        </a>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-arrow-return-left"></i> Возвраты поставщику
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($returns) ?></span>
        </h2>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th data-sort="return_number" onclick="AdminTable.sortBy('return_number')">Номер <?= $sortIcon('return_number') ?></th>
                    <th>Закупка</th>
                    <th>Поставщик</th>
                    <th>Причина</th>
                    <th>Статус</th>
                    <th data-sort="total_amount" onclick="AdminTable.sortBy('total_amount')" style="text-align:right">Сумма BYN <?= $sortIcon('total_amount') ?></th>
                    <th data-sort="created_at" onclick="AdminTable.sortBy('created_at')">Создан <?= $sortIcon('created_at') ?></th>
                    <th style="width:80px">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$returns): ?>
                    <tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Возвратов нет</td></tr>
                <?php endif; ?>
                <?php foreach ($returns as $r):
                    $sp = $statusPills[$r->status] ?? ['bg' => '#f3f4f6', 'color' => '#6d7175'];
                ?>
                <tr>
                    <td style="white-space:nowrap">
                        <a href="/admin/procurement/view-return/<?= $r->id ?>" style="font-weight:700;color:var(--admin-text-primary,#202223);text-decoration:none">
                            <?= htmlspecialchars($r->return_number) ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($r->purchaseOrder): ?>
                            <a href="/admin/procurement/view/<?= $r->purchase_order_id ?>" style="color:var(--admin-info,#0078d4)">
                                <?= htmlspecialchars($r->purchaseOrder->purchase_number) ?>
                            </a>
                        <?php else: ?> — <?php endif; ?>
                    </td>
                    <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($r->supplier->name ?? '—') ?>
                    </td>
                    <td style="color:var(--admin-text-secondary,#6d7175)"><?= $r->getReasonLabel() ?></td>
                    <td>
                        <span class="status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>">
                            <?= $r->getStatusLabel() ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:700;white-space:nowrap">
                        <?= $r->total_amount ? number_format($r->total_amount, 2) . ' <span style="font-size:.7rem;color:var(--admin-text-secondary,#6d7175);font-weight:400">BYN</span>' : '—' ?>
                    </td>
                    <td style="white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= $r->created_at ? date('d.m.Y', strtotime($r->created_at)) : '—' ?>
                    </td>
                    <td style="padding:4px 6px">
                        <div style="position:relative;display:inline-block" class="rt-dropdown-wrap">
                            <button class="admin-btn admin-btn-sm admin-btn-secondary"
                                    onclick="toggleRTDropdown(<?= $r->id ?>)" title="Сменить статус">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <div id="rt-dd-<?= $r->id ?>" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e1e3e5);border-radius:8px;padding:4px;z-index:200;min-width:140px;box-shadow:0 4px 16px rgba(0,0,0,.1)">
                                <?php foreach ($statuses as $k => $v): ?>
                                    <?php if ($k !== $r->status): ?>
                                    <a href="#" onclick="updateReturnStatus(<?= $r->id ?>, '<?= $k ?>'); return false"
                                       style="display:block;padding:5px 10px;font-size:.8125rem;color:var(--admin-text-primary,#202223);text-decoration:none;border-radius:5px;white-space:nowrap"
                                       onmouseover="this.style.background='var(--admin-surface-hover,#f3f4f6)'"
                                       onmouseout="this.style.background=''">
                                        <?= htmlspecialchars($v) ?>
                                    </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

function toggleRTDropdown(id) {
    document.querySelectorAll('[id^="rt-dd-"]').forEach(function(d) {
        if (d.id !== 'rt-dd-' + id) d.style.display = 'none';
    });
    var dd = document.getElementById('rt-dd-' + id);
    if (dd) dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.rt-dropdown-wrap')) {
        document.querySelectorAll('[id^="rt-dd-"]').forEach(function(d) { d.style.display = 'none'; });
    }
});

function updateReturnStatus(id, status) {
    fetch('/admin/procurement/update-return-status', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({id, status})
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка');
    });
}
</script>
