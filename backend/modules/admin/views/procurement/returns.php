<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\SupplierReturn[] $returns */
/** @var string $filterStatus */
/** @var array $statuses */
$this->title = 'Возвраты поставщику';

$statusPills = [
    'pending'    => ['bg' => '#fff4e5', 'color' => '#ffa500', 'icon' => 'bi-clock'],
    'in_transit' => ['bg' => '#eff6ff', 'color' => '#2563eb', 'icon' => 'bi-truck'],
    'received'   => ['bg' => '#d1f7e5', 'color' => '#008060', 'icon' => 'bi-check-circle'],
    'cancelled'  => ['bg' => '#fbeae5', 'color' => '#d72c0d', 'icon' => 'bi-x-circle'],
];
?>

<style>
.returns-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px;margin-top:16px}
.return-card{background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e5e7eb);border-radius:10px;padding:16px;display:flex;flex-direction:column;gap:10px;transition:box-shadow .15s,border-color .15s;cursor:default}
.return-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);border-color:var(--admin-border-hover,#c1c9d2)}
.return-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.return-card-num{font-weight:700;font-size:.9375rem;color:var(--admin-text-primary,#111);text-decoration:none}
.return-card-num:hover{color:var(--admin-primary,#2563eb)}
.return-card-meta{display:flex;flex-direction:column;gap:4px;font-size:.8125rem;color:var(--admin-text-secondary,#6b7280)}
.return-card-meta-row{display:flex;align-items:center;gap:6px}
.return-card-meta-row i{font-size:.85rem;flex-shrink:0}
.return-card-amount{font-weight:700;font-size:1.0625rem;color:var(--admin-text-primary,#111);white-space:nowrap}
.return-card-actions{display:flex;gap:6px;margin-top:4px}
.status-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;line-height:1.6}
</style>

<div class="admin-page-header" style="display:flex;flex-wrap:nowrap;align-items:center;gap:12px;margin-bottom:16px">
    <h1 class="admin-page-title" style="margin:0;min-width:0;flex:1">Возвраты поставщику</h1>
    <a href="/admin/procurement/create-return/0" class="admin-btn admin-btn-primary admin-btn-sm" style="flex-shrink:0">
        <i class="bi bi-plus-lg"></i> Создать возврат
    </a>
</div>

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
            <i class="bi bi-arrow-left"></i> Приёмки
        </a>
    </div>
</form>

<div style="display:flex;align-items:center;gap:8px;margin-top:12px">
    <span style="font-size:.8125rem;color:var(--admin-text-secondary,#6b7280)">
        <i class="bi bi-arrow-return-left"></i>
        <?= count($returns) ?> <?= count($returns) === 1 ? 'возврат' : (count($returns) < 5 ? 'возврата' : 'возвратов') ?>
    </span>
</div>

<?php if (!$returns): ?>
<div class="empty-state" style="padding:3rem;text-align:center;margin-top:16px">
    <div class="empty-state-icon"><i class="bi bi-arrow-return-left" style="font-size:2.5rem;color:var(--admin-text-secondary,#9ca3af)"></i></div>
    <h3 style="margin:12px 0 6px;font-size:1rem">Возвратов нет</h3>
    <p style="color:var(--admin-text-secondary,#6b7280);margin:0 0 16px">Создайте первый возврат поставщику</p>
    <a href="/admin/procurement/create-return/0" class="admin-btn admin-btn-primary admin-btn-sm">
        <i class="bi bi-plus-lg"></i> Создать возврат
    </a>
</div>
<?php else: ?>
<div class="returns-grid">
    <?php foreach ($returns as $r):
        $sp = $statusPills[$r->status] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'icon' => 'bi-circle'];
    ?>
    <div class="return-card crm-card">
        <div class="return-card-head">
            <div>
                <a href="/admin/procurement/view-return/<?= $r->id ?>" class="return-card-num">
                    <?= htmlspecialchars($r->return_number) ?>
                </a>
                <div style="margin-top:4px">
                    <span class="status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>">
                        <i class="bi <?= $sp['icon'] ?>" style="font-size:10px"></i>
                        <?= $r->getStatusLabel() ?>
                    </span>
                </div>
            </div>
            <?php if ($r->total_amount): ?>
            <div class="return-card-amount">
                <?= number_format($r->total_amount, 2) ?>
                <span style="font-size:.75rem;font-weight:400;color:var(--admin-text-secondary,#6b7280)">BYN</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="return-card-meta">
            <?php if ($r->supplier): ?>
            <div class="return-card-meta-row">
                <i class="bi bi-building"></i>
                <span><?= htmlspecialchars($r->supplier->name) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($r->purchaseOrder): ?>
            <div class="return-card-meta-row">
                <i class="bi bi-receipt"></i>
                <a href="/admin/procurement/view/<?= $r->purchase_order_id ?>"
                   style="color:var(--admin-primary,#2563eb);text-decoration:none">
                    <?= htmlspecialchars($r->purchaseOrder->purchase_number) ?>
                </a>
            </div>
            <?php endif; ?>
            <?php if (!empty($r->reason)): ?>
            <div class="return-card-meta-row">
                <i class="bi bi-exclamation-circle"></i>
                <span><?= $r->getReasonLabel() ?></span>
            </div>
            <?php endif; ?>
            <div class="return-card-meta-row">
                <i class="bi bi-calendar3"></i>
                <span><?= $r->created_at ? date('d.m.Y', strtotime($r->created_at)) : '—' ?></span>
            </div>
        </div>

        <div class="return-card-actions">
            <a href="/admin/procurement/view-return/<?= $r->id ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                <i class="bi bi-eye"></i> Открыть
            </a>
            <div style="position:relative" class="rt-dropdown-wrap">
                <button class="admin-btn admin-btn-sm admin-btn-secondary"
                        onclick="toggleRTDropdown(<?= $r->id ?>)" title="Сменить статус">
                    <i class="bi bi-arrow-repeat"></i> Статус
                </button>
                <div id="rt-dd-<?= $r->id ?>" style="display:none;position:absolute;left:0;top:calc(100% + 4px);background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e1e3e5);border-radius:8px;padding:4px;z-index:200;min-width:150px;box-shadow:0 4px 16px rgba(0,0,0,.1)">
                    <?php foreach ($statuses as $k => $v): ?>
                        <?php $ddSp = $statusPills[$k] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'icon' => 'bi-circle']; ?>
                        <a href="#" onclick="updateReturnStatus(<?= $r->id ?>, '<?= $k ?>'); return false"
                           style="display:flex;align-items:center;gap:6px;padding:6px 10px;font-size:.8125rem;color:var(--admin-text-primary,#202223);text-decoration:none;border-radius:5px;white-space:nowrap<?= $k === $r->status ? ';font-weight:700' : '' ?>"
                           onmouseover="this.style.background='var(--admin-surface-hover,#f3f4f6)'"
                           onmouseout="this.style.background=''">
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $ddSp['color'] ?>;flex-shrink:0"></span>
                            <?= htmlspecialchars($v) ?>
                            <?php if ($k === $r->status): ?> <i class="bi bi-check" style="margin-left:auto"></i><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

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
