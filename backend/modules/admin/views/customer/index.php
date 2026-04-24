<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string|null $search */
/** @var int|null $status */
/** @var array $stats */
/** @var int $totalCount */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Покупатели';

$customers  = $dataProvider->getModels();
$pagination = $dataProvider->pagination;

// Sort helpers
$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)        return ' <span style="color:var(--admin-primary,#2563eb);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col)  return ' <span style="color:var(--admin-primary,#2563eb);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

// Status pill styles
$statusPills = [
    10 => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'Активен'],
    9  => ['bg' => '#fef2f2', 'color' => '#dc2626', 'label' => 'Заблокирован'],
    1  => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => 'Активен'],
    0  => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => 'Удалён'],
];

// Column selector definitions
$columnDefs = [
    'email'        => 'Email',
    'phone'        => 'Телефон',
    'orders_count' => 'Заказов',
    'total_spent'  => 'Потрачено',
    'last_order'   => 'Последний заказ',
    'status'       => 'Статус',
];

// Status funnel
$statusItems = [
    ''   => ['label' => 'Все',            'count' => $stats['total'],    'dot' => '#6b7280'],
    '10' => ['label' => 'Активные',       'count' => $stats['active'],   'dot' => '#16a34a'],
    '9'  => ['label' => 'Заблокированные','count' => $stats['inactive'], 'dot' => '#dc2626'],
];
?>

<style>
/* === Funnel === */
.customer-funnel{display:flex;gap:6px;flex-wrap:wrap;align-items:center;padding:12px 16px;background:var(--admin-surface,#fff);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:12px}
.funnel-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6b7280);font-size:.8125rem;font-weight:500;text-decoration:none;transition:all .18s ease;white-space:nowrap;border:1.5px solid transparent}
.funnel-pill:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1);color:var(--admin-text-primary,#111);text-decoration:none}
.funnel-pill--active{background:var(--admin-primary,#2563eb)!important;color:#fff!important;border-color:var(--admin-primary,#2563eb);font-weight:700}
.funnel-pill--active .funnel-pill-count{background:rgba(255,255,255,.25);color:#fff}
.funnel-pill-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.funnel-pill-count{font-size:.75rem;font-weight:700;background:rgba(0,0,0,.07);color:inherit;padding:1px 6px;border-radius:10px;min-width:20px;text-align:center}
/* === Filter bar === */
.filter-wrap{margin-bottom:14px}
.compact-filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:var(--admin-surface,#fff);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.compact-filter-input,.compact-filter-select{height:32px;border:1.5px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:0 10px;font-size:.8125rem;color:var(--admin-text-primary,#111);background:var(--admin-surface,#fff);outline:none;transition:border-color .15s}
.compact-filter-input:focus,.compact-filter-select:focus{border-color:var(--admin-primary,#2563eb)}
.compact-filter-input{flex:1;min-width:180px;max-width:300px}
.compact-filter-select{flex:0 0 auto;min-width:130px}
.compact-filter-btn{height:32px;padding:0 12px;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6b7280)}
.compact-filter-btn:hover{background:#e5e7eb;color:#374151;text-decoration:none}
.compact-filter-btn--apply{background:var(--admin-primary,#2563eb);color:#fff}
.compact-filter-btn--apply:hover{background:#1d4ed8;color:#fff}
.compact-filter-btn--reset:hover{background:#fee2e2;color:#dc2626}
/* === Column selector === */
.col-selector-wrap{position:relative;flex-shrink:0}
.col-selector-dropdown{position:absolute;right:0;top:calc(100% + 6px);background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e5e7eb);border-radius:10px;padding:12px;z-index:300;min-width:190px;max-height:370px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.12)}
.col-selector-item{display:flex;align-items:center;gap:8px;padding:4px 2px;font-size:.8125rem;cursor:pointer;color:var(--admin-text-primary,#111);user-select:none;border-radius:4px}
.col-selector-item:hover{background:var(--admin-surface-hover,#f3f4f6)}
.col-selector-item input{width:15px;height:15px;cursor:pointer;accent-color:var(--admin-primary,#2563eb);flex-shrink:0}
/* === Table status pill === */
.status-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;line-height:1.6}
/* === Sortable column headers === */
th[data-sort]{cursor:pointer;user-select:none;white-space:nowrap}
th[data-sort]:hover{background:var(--admin-surface-hover,#f3f4f6)!important}
/* === Clickable row === */
#customersTable tbody tr{cursor:pointer;transition:background .1s}
#customersTable tbody tr:hover{background:var(--admin-surface-hover,#f5f7fa)!important}
/* === Infinite scroll === */
#scroll-sentinel{height:1px;margin-top:8px}
#scroll-loading{display:none;justify-content:center;align-items:center;padding:16px;gap:8px;color:var(--admin-text-secondary,#6b7280);font-size:.875rem}
@keyframes spin{to{transform:rotate(360deg)}}
.scroll-spinner{width:18px;height:18px;border:2px solid #e5e7eb;border-top-color:var(--admin-primary,#2563eb);border-radius:50%;animation:spin .7s linear infinite}
/* === Sticky table header === */
#customersTable thead th{position:sticky;top:0;z-index:10;background:var(--admin-surface,#fff);box-shadow:0 1px 0 var(--admin-border,#e5e7eb)}
</style>

<!-- Status Funnel -->
<div class="customer-funnel">
    <?php foreach ($statusItems as $sKey => $sItem):
        $isAct = (string)$status === (string)$sKey;
        $url = $sKey === '' ? Url::to(['/admin/customer']) : Url::to(['/admin/customer', 'status' => $sKey]);
    ?>
    <a href="<?= $url ?>" class="funnel-pill <?= $isAct ? 'funnel-pill--active' : '' ?>">
        <span class="funnel-pill-dot" style="background:<?= $sItem['dot'] ?>"></span>
        <span class="funnel-pill-label"><?= Html::encode($sItem['label']) ?></span>
        <span class="funnel-pill-count"><?= $sItem['count'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter Bar -->
<form method="get" id="filterForm" class="filter-wrap">
    <div class="compact-filter-bar">
        <?php if ($status !== null && $status !== ''): ?>
        <input type="hidden" name="status" value="<?= Html::encode($status) ?>">
        <?php endif; ?>
        <input type="text" name="search" class="compact-filter-input"
               placeholder="&#128269; Имя, телефон, email…"
               value="<?= Html::encode($search) ?>">
        <select name="status" class="compact-filter-select">
            <option value="">Все статусы</option>
            <option value="10" <?= $status == 10 ? 'selected' : '' ?>>Активные</option>
            <option value="9" <?= $status == 9 ? 'selected' : '' ?>>Заблокированные</option>
        </select>
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply">
            <i class="bi bi-search"></i> Найти
        </button>
        <a href="<?= Url::to(['/admin/customer']) ?>" class="compact-filter-btn compact-filter-btn--reset">
            <i class="bi bi-x-lg"></i> Сбросить
        </a>
        <a href="<?= Url::to(['customer/export']) ?>" class="compact-filter-btn" style="margin-left:4px">
            <i class="bi bi-download"></i> CSV
        </a>
        <!-- Column selector -->
        <div class="col-selector-wrap" style="margin-left:auto">
            <button type="button" class="compact-filter-btn" onclick="toggleColSelector(event)">
                <i class="bi bi-layout-three-columns"></i> Столбцы
            </button>
            <div id="colSelector" class="col-selector-dropdown" style="display:none">
                <div style="font-weight:700;margin-bottom:8px;font-size:.8125rem">Показать столбцы:</div>
                <?php foreach ($columnDefs as $colKey => $colLabel): ?>
                <label class="col-selector-item">
                    <input type="checkbox" data-col="<?= $colKey ?>"
                           onchange="toggleColumn('<?= $colKey ?>', this.checked)" checked>
                    <?= Html::encode($colLabel) ?>
                </label>
                <?php endforeach; ?>
                <div style="margin-top:8px;border-top:1px solid var(--admin-border,#e5e7eb);padding-top:8px;display:flex;gap:6px">
                    <button type="button" onclick="selectAllCols(true)"  class="compact-filter-btn compact-filter-btn--apply"  style="flex:1;height:26px;font-size:11px;padding:0 8px">Все</button>
                    <button type="button" onclick="selectAllCols(false)" class="compact-filter-btn compact-filter-btn--reset" style="flex:1;height:26px;font-size:11px;padding:0 8px">Скрыть</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Main card -->
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
        <h2 class="admin-card-title" style="margin:0;"><i class="bi bi-people"></i> Покупатели
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6b7280);margin-left:8px">
                Всего: <?= $stats['total'] ?>
                &nbsp;·&nbsp;
                <a href="?has_orders=1" style="color:inherit;text-decoration:none" title="Показать только клиентов с заказами">С заказами: <?= $stats['with_orders'] ?></a>
                &nbsp;·&nbsp;
                <a href="?has_orders=0" style="color:inherit;text-decoration:none" title="Показать без заказов">Без заказов: <?= $stats['without_orders'] ?></a>
            </span>
        </h2>
    </div>

    <div style="overflow-x:auto;">
        <table class="admin-table" id="customersTable" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th data-sort="created_at" onclick="sortBy('created_at')" title="Сортировать по ID" style="white-space:nowrap">ID <?= $sortIcon('created_at') ?></th>
                    <th data-sort="first_name" onclick="sortBy('first_name')" title="Сортировать по имени">Имя <?= $sortIcon('first_name') ?></th>
                    <th data-col="email" data-sort="email" onclick="sortBy('email')" title="Сортировать по email">Email <?= $sortIcon('email') ?></th>
                    <th data-col="phone">Телефон</th>
                    <th data-col="orders_count" data-sort="orders_count" onclick="sortBy('orders_count')" title="Сортировать по заказам" style="text-align:center">Заказов <?= $sortIcon('orders_count') ?></th>
                    <th data-col="total_spent" data-sort="total_spent" onclick="sortBy('total_spent')" title="Сортировать по сумме" style="white-space:nowrap">Потрачено <?= $sortIcon('total_spent') ?></th>
                    <th data-col="last_order" data-sort="last_order_at" onclick="sortBy('last_order_at')" title="Сортировать по последнему заказу">Последний заказ <?= $sortIcon('last_order_at') ?></th>
                    <th data-col="status" data-sort="status" onclick="sortBy('status')" title="Сортировать по статусу">Статус <?= $sortIcon('status') ?></th>
                    <th style="width:44px">–</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $customer):
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
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="padding:0">
                            <div class="empty-state" style="padding:2.5rem;text-align:center">
                                <div class="empty-state-icon"><i class="bi bi-people" style="font-size:2.5rem;opacity:.4"></i></div>
                                <h3 style="margin:.75rem 0 .5rem">Покупатели не найдены</h3>
                                <p style="margin:0;color:var(--admin-text-secondary,#6b7280)">Попробуйте изменить параметры поиска</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Infinite scroll sentinel -->
    <div id="scroll-sentinel"></div>
    <div id="scroll-loading">
        <div class="scroll-spinner"></div>
        <span>Загрузка…</span>
    </div>
</div>

<script>
/* ──────────────────────────────────────────
   Column selector
────────────────────────────────────────── */
function toggleColSelector(e) {
    e.stopPropagation();
    var d = document.getElementById('colSelector');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    var w = document.querySelector('.col-selector-wrap');
    if (w && !w.contains(e.target)) {
        var d = document.getElementById('colSelector');
        if (d) d.style.display = 'none';
    }
});
function toggleColumn(col, show) {
    document.querySelectorAll('[data-col="' + col + '"]').forEach(function(el) {
        el.style.display = show ? '' : 'none';
    });
    var saved = JSON.parse(localStorage.getItem('customerColumns') || '{}');
    saved[col] = show;
    localStorage.setItem('customerColumns', JSON.stringify(saved));
}
function selectAllCols(show) {
    document.querySelectorAll('#colSelector input[type=checkbox]').forEach(function(cb) {
        cb.checked = show;
        toggleColumn(cb.dataset.col, show);
    });
}
/* Restore column visibility from localStorage */
(function() {
    var saved = JSON.parse(localStorage.getItem('customerColumns') || '{}');
    Object.entries(saved).forEach(function(entry) {
        var col = entry[0], show = entry[1];
        if (show === false) {
            var cb = document.querySelector('#colSelector input[data-col="' + col + '"]');
            if (cb) {
                cb.checked = false;
                toggleColumn(col, false);
            }
        }
    });
})();

/* ──────────────────────────────────────────
   Sortable columns
────────────────────────────────────────── */
function sortBy(col) {
    var url = new URL(window.location.href);
    var cur = url.searchParams.get('sort') || '';
    url.searchParams.set('sort', cur === col ? ('-' + col) : col);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

/* ──────────────────────────────────────────
   Infinite scroll
────────────────────────────────────────── */
(function() {
    var loading  = false;
    var hasMore  = <?= $pagination->pageCount > 1 ? 'true' : 'false' ?>;
    var nextPage = 2;

    var sentinel = document.getElementById('scroll-sentinel');
    var spinner  = document.getElementById('scroll-loading');

    if (!hasMore || !sentinel) return;

    function applyHiddenColumns(rows) {
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem('customerColumns') || '{}'); } catch(e){}
        Object.keys(saved).forEach(function(col) {
            if (saved[col] === false) {
                rows.forEach(function(row) {
                    row.querySelectorAll('[data-col="' + col + '"]').forEach(function(el) {
                        el.style.display = 'none';
                    });
                });
            }
        });
    }

    function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        if (spinner) spinner.style.display = 'flex';

        var url = new URL(window.location.href);
        url.searchParams.set('scroll', '1');
        url.searchParams.set('page', nextPage);

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (spinner) spinner.style.display = 'none';
            loading = false;

            if (data.rows) {
                var tbody = document.querySelector('#customersTable tbody');
                var temp  = document.createElement('table');
                temp.innerHTML = '<tbody>' + data.rows + '</tbody>';
                var newRows = Array.from(temp.querySelector('tbody').children);
                newRows.forEach(function(row) { tbody.appendChild(row); });
                applyHiddenColumns(newRows);
            }

            hasMore  = !!data.hasMore;
            nextPage = data.nextPage || (nextPage + 1);

            if (!hasMore) sentinel.style.display = 'none';
        })
        .catch(function() {
            loading = false;
            if (spinner) spinner.style.display = 'none';
        });
    }

    if (typeof IntersectionObserver !== 'undefined') {
        new IntersectionObserver(function(entries) {
            entries.forEach(function(e) { if (e.isIntersecting) loadMore(); });
        }, { rootMargin: '300px' }).observe(sentinel);
    } else {
        window.addEventListener('scroll', function() {
            var r = sentinel.getBoundingClientRect();
            if (r.top < window.innerHeight + 400) loadMore();
        }, { passive: true });
    }
})();

/* ──────────────────────────────────────────
   Click anywhere on row to open customer
────────────────────────────────────────── */
(function() {
    var tbody = document.querySelector('#customersTable tbody');
    if (!tbody) return;
    tbody.addEventListener('click', function(e) {
        if (e.target.closest('a, button, input, select, label, [onclick]')) return;
        var row = e.target.closest('tr');
        if (!row || !row.dataset.href) return;
        window.location.href = row.dataset.href;
    });
})();
</script>
