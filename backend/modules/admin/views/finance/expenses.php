<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\finance\models\Expense[] $expenses */
/** @var array $byCategory */
/** @var string $filterCat, $filterFrom, $filterTo */
/** @var array $categories */
$this->title = 'Расходы';

$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

$catColors = [
    'purchase'  => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'delivery'  => ['bg' => '#faf5ff', 'color' => '#7c3aed'],
    'customs'   => ['bg' => '#fffbeb', 'color' => '#d97706'],
    'rent'      => ['bg' => '#fef2f2', 'color' => '#dc2626'],
    'salary'    => ['bg' => '#ecfdf5', 'color' => '#059669'],
    'marketing' => ['bg' => '#fff1f2', 'color' => '#e11d48'],
    'other'     => ['bg' => '#f3f4f6', 'color' => '#6d7175'],
];

$columnDefs = [
    'orig'     => 'Оригинал',
    'doc'      => 'Документ',
    'order'    => 'Заказ',
    'supplier' => 'Поставщик',
];
$storageKey = 'expensesColumns';
?>

<!-- KPI cards by category -->
<div class="atu-kpi-bar" style="flex-wrap:wrap">
    <?php foreach ($categories as $k => $label):
        $color = $catColors[$k] ?? ['bg' => '#f3f4f6', 'color' => '#6d7175'];
    ?>
    <div class="atu-kpi-card" style="border-color:<?= $color['color'] ?>22">
        <div class="atu-kpi-label"><?= htmlspecialchars($label) ?></div>
        <div class="atu-kpi-value" style="font-size:1.125rem;color:<?= $color['color'] ?>"><?= number_format((float)($byCategory[$k] ?? 0), 0) ?> <span style="font-size:.75rem;font-weight:400">BYN</span></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter bar -->
<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <select name="category" class="compact-filter-select" style="min-width:180px">
            <option value="">Все категории</option>
            <?php foreach ($categories as $k => $v): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $filterCat === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" class="compact-filter-input" value="<?= htmlspecialchars($filterFrom) ?>" title="Дата от">
        <input type="date" name="date_to"   class="compact-filter-input" value="<?= htmlspecialchars($filterTo) ?>"   title="Дата до">
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
        <button type="button" class="compact-filter-btn compact-filter-btn--apply" onclick="openAddExpense()" style="margin-left:4px">
            <i class="bi bi-plus-lg"></i> Добавить расход
        </button>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-cash-stack"></i> Расходы
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($expenses) ?></span>
        </h2>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th style="width:44px">ID</th>
                    <th>Категория</th>
                    <th data-sort="amount" onclick="AdminTable.sortBy('amount')" style="white-space:nowrap">Сумма <?= $sortIcon('amount') ?></th>
                    <th data-col="orig">Оригинал</th>
                    <th>Описание</th>
                    <th data-col="doc">Документ</th>
                    <th data-col="order">Заказ</th>
                    <th data-col="supplier">Поставщик</th>
                    <th data-sort="created_at" onclick="AdminTable.sortBy('created_at')">Дата <?= $sortIcon('created_at') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$expenses): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Расходов нет</td></tr>
                <?php endif; ?>
                <?php foreach ($expenses as $e):
                    $catKey = $e->category ?? 'other';
                    $cp = $catColors[$catKey] ?? $catColors['other'];
                ?>
                <tr>
                    <td style="color:var(--admin-text-secondary,#6d7175);font-size:11px"><?= $e->id ?></td>
                    <td>
                        <span class="status-pill" style="background:<?= $cp['bg'] ?>;color:<?= $cp['color'] ?>">
                            <?= $e->getCategoryLabel() ?>
                        </span>
                    </td>
                    <td style="font-weight:700;white-space:nowrap">
                        <?= number_format($e->amount, 2) ?> <span style="font-size:.7rem;color:var(--admin-text-secondary,#6d7175);font-weight:400"><?= $e->currency ?></span>
                    </td>
                    <td data-col="orig" style="white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= $e->amount_original ? number_format($e->amount_original, 2) . ' ' . $e->currency_original : '—' ?>
                    </td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($e->description ?? '') ?>">
                        <?= htmlspecialchars($e->description ?? '—') ?>
                    </td>
                    <td data-col="doc" style="font-size:.75rem;color:var(--admin-text-secondary,#6d7175)">
                        <?= htmlspecialchars($e->document_number ?? '—') ?>
                    </td>
                    <td data-col="order">
                        <?= $e->order_id ? '<a href="/admin/order/' . $e->order_id . '" style="color:var(--admin-info,#0078d4)">#' . $e->order_id . '</a>' : '—' ?>
                    </td>
                    <td data-col="supplier" style="color:var(--admin-text-secondary,#6d7175)">
                        <?= $e->supplier_id ?? '—' ?>
                    </td>
                    <td style="white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= $e->created_at ? date('d.m.Y', strtotime($e->created_at)) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal" id="addExpenseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Новый расход</h5><button type="button" class="btn-close" onclick="closeModal('addExpenseModal')"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Категория *</label>
          <select id="ae_category" class="form-control">
            <?php foreach ($categories as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Сумма (BYN) *</label><input type="number" step="0.01" id="ae_amount" class="form-control"></div>
        <div class="row g-2 mb-3">
          <div class="col"><label class="form-label">Сумма оригинал</label><input type="number" step="0.01" id="ae_amount_orig" class="form-control"></div>
          <div class="col">
            <label class="form-label">Валюта оригинал</label>
            <select id="ae_currency_orig" class="form-control"><option value="">—</option><option>CNY</option><option>USD</option><option>EUR</option><option>BYN</option></select>
          </div>
          <div class="col"><label class="form-label">Курс</label><input type="number" step="0.0001" id="ae_rate" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label">Описание</label><textarea id="ae_desc" class="form-control" rows="2"></textarea></div>
        <div class="mb-3"><label class="form-label">Номер документа</label><input type="text" id="ae_doc_num" class="form-control"></div>
        <div class="row g-2 mb-3">
          <div class="col"><label class="form-label">Заказ ID</label><input type="number" id="ae_order_id" class="form-control"></div>
          <div class="col"><label class="form-label">Поставщик ID</label><input type="number" id="ae_supplier_id" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="admin-btn admin-btn-secondary" onclick="closeModal('addExpenseModal')">Отмена</button>
        <button class="admin-btn admin-btn-primary" onclick="submitAddExpense()">Сохранить</button>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="addExpenseModal-backdrop" style="display:none"></div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

AdminTable.initColSelector('<?= $storageKey ?>');

function openAddExpense() {
    document.getElementById('addExpenseModal').style.display = 'block';
    document.getElementById('addExpenseModal').classList.add('show');
    document.getElementById('addExpenseModal-backdrop').style.display = 'block';
    document.getElementById('addExpenseModal-backdrop').classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.getElementById(id).classList.remove('show');
    const bd = document.getElementById(id + '-backdrop');
    if (bd) { bd.style.display = 'none'; bd.classList.remove('show'); }
}

function submitAddExpense() {
    const body = {
        category:          document.getElementById('ae_category').value,
        amount:            document.getElementById('ae_amount').value,
        currency:          'BYN',
        amount_original:   document.getElementById('ae_amount_orig').value || null,
        currency_original: document.getElementById('ae_currency_orig').value || null,
        exchange_rate:     document.getElementById('ae_rate').value || null,
        description:       document.getElementById('ae_desc').value,
        document_number:   document.getElementById('ae_doc_num').value,
        order_id:          document.getElementById('ae_order_id').value || null,
        supplier_id:       document.getElementById('ae_supplier_id').value || null,
    };
    fetch('/admin/finance/create-expense', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify(body)
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка: ' + JSON.stringify(d.errors));
    });
}
</script>
