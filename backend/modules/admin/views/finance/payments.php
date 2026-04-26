<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\finance\models\Payment[] $payments */
/** @var array $totals */
/** @var string $filterStatus, $filterMethod, $filterFrom, $filterTo */
/** @var array $statuses, $methods */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Платежи';

$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

$statusPills = [
    'confirmed' => ['bg' => '#d1f7e5', 'color' => '#008060'],
    'pending'   => ['bg' => '#fff4e5', 'color' => '#ffa500'],
    'failed'    => ['bg' => '#fbeae5', 'color' => '#d72c0d'],
    'refunded'  => ['bg' => '#e5f3ff', 'color' => '#0078d4'],
];

$columnDefs = [
    'order'    => 'Заказ',
    'customer' => 'Клиент',
    'method'   => 'Метод',
    'bank_ref' => 'Реквизит банка',
];
$storageKey = 'paymentsColumns';
?>

<!-- KPI bar -->
<div class="atu-kpi-bar">
    <div class="atu-kpi-card atu-kpi-card--green">
        <div class="atu-kpi-label">Подтверждено</div>
        <div class="atu-kpi-value"><?= number_format($totals['confirmed'], 2) ?> <span style="font-size:.875rem;font-weight:400">BYN</span></div>
    </div>
    <div class="atu-kpi-card atu-kpi-card--yellow">
        <div class="atu-kpi-label">Ожидает</div>
        <div class="atu-kpi-value"><?= number_format($totals['pending'], 2) ?> <span style="font-size:.875rem;font-weight:400">BYN</span></div>
    </div>
</div>

<!-- Filter bar -->
<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <select name="status" class="compact-filter-select">
            <option value="">Все статусы</option>
            <?php foreach ($statuses as $k => $v): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="method" class="compact-filter-select" style="min-width:160px">
            <option value="">Все методы</option>
            <?php foreach ($methods as $k => $v): ?>
            <option value="<?= htmlspecialchars($k) ?>" <?= $filterMethod === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
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
        <button type="button" class="compact-filter-btn compact-filter-btn--apply" onclick="openCreatePayment()" style="margin-left:4px">
            <i class="bi bi-plus-lg"></i> Добавить платёж
        </button>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-credit-card"></i> Платежи
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($payments) ?></span>
        </h2>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th style="width:44px">ID</th>
                    <th data-col="order">Заказ</th>
                    <th data-col="customer">Клиент</th>
                    <th data-sort="amount" onclick="AdminTable.sortBy('amount')" style="white-space:nowrap">Сумма <?= $sortIcon('amount') ?></th>
                    <th data-col="method">Метод</th>
                    <th>Статус</th>
                    <th data-col="bank_ref">Реквизит банка</th>
                    <th data-sort="created_at" onclick="AdminTable.sortBy('created_at')">Дата <?= $sortIcon('created_at') ?></th>
                    <th style="width:44px">—</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$payments): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2.5rem;color:var(--admin-text-secondary,#6d7175)">Платежей нет</td></tr>
                <?php endif; ?>
                <?php foreach ($payments as $p):
                    $sp = $statusPills[$p->status] ?? ['bg' => '#f3f4f6', 'color' => '#6d7175'];
                ?>
                <tr>
                    <td style="color:var(--admin-text-secondary,#6d7175);font-size:11px"><?= $p->id ?></td>
                    <td data-col="order">
                        <?= $p->order_id ? '<a href="/admin/order/' . $p->order_id . '" style="color:var(--admin-info,#0078d4)">' . ($p->order ? htmlspecialchars($p->order->order_number) : '#' . $p->order_id) . '</a>' : '—' ?>
                    </td>
                    <td data-col="customer" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= htmlspecialchars($p->customer_id ?? '—') ?>
                    </td>
                    <td style="font-weight:700;white-space:nowrap">
                        <?= number_format($p->amount, 2) ?> <span style="font-size:.7rem;color:var(--admin-text-secondary,#6d7175);font-weight:400"><?= $p->currency ?></span>
                    </td>
                    <td data-col="method" style="color:var(--admin-text-secondary,#6d7175)"><?= $p->getMethodLabel() ?></td>
                    <td>
                        <span class="status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>">
                            <?= $p->getStatusLabel() ?>
                        </span>
                    </td>
                    <td data-col="bank_ref" style="font-size:.75rem;color:var(--admin-text-secondary,#6d7175)">
                        <?= htmlspecialchars($p->bank_details ?? $p->bank_reference ?? '—') ?>
                    </td>
                    <td style="white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= $p->created_at ? date('d.m.Y H:i', strtotime($p->created_at)) : '—' ?>
                    </td>
                    <td style="padding:4px 6px">
                        <?php if ($p->status !== 'confirmed'): ?>
                        <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="confirmPayment(<?= $p->id ?>)" title="Подтвердить">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Payment Slide-in Panel -->
<style>
.pay-panel-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1040;display:none}
.pay-panel-overlay.active{display:block}
.pay-panel{position:fixed;top:0;right:0;height:100%;width:480px;max-width:100vw;background:var(--admin-surface,#fff);box-shadow:-4px 0 32px rgba(0,0,0,.18);z-index:1041;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .25s ease}
.pay-panel.active{transform:translateX(0)}
.pay-panel-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid var(--admin-border,#e5e7eb)}
.pay-panel-header h5{margin:0;font-size:1rem;font-weight:700}
.pay-panel-body{flex:1;overflow-y:auto;padding:20px}
.pay-panel-footer{padding:14px 20px;border-top:1px solid var(--admin-border,#e5e7eb);display:flex;gap:8px;justify-content:flex-end}
.pay-ac-wrap{position:relative}
.pay-ac-list{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--admin-border,#e5e7eb);border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:9999;max-height:200px;overflow-y:auto;display:none}
.pay-ac-item{padding:8px 12px;cursor:pointer;font-size:.8125rem;border-bottom:1px solid #f3f4f6}
.pay-ac-item:last-child{border-bottom:none}
.pay-ac-item:hover{background:var(--admin-surface-hover,#f9fafb)}
.pay-ac-item strong{display:block;font-weight:600}
.pay-ac-item span{color:var(--admin-text-secondary,#6b7280);font-size:.75rem}
</style>

<div class="pay-panel-overlay" id="payPanelOverlay" onclick="closePayPanel()"></div>
<div class="pay-panel" id="payPanel">
  <div class="pay-panel-header">
    <h5>Новый платёж</h5>
    <button class="btn-close" onclick="closePayPanel()"></button>
  </div>
  <div class="pay-panel-body">
    <div class="mb-3">
      <label class="form-label">Заказ</label>
      <div class="pay-ac-wrap">
        <input type="text" id="cp_order_search" class="form-control" placeholder="Поиск по номеру заказа…" autocomplete="off">
        <input type="hidden" id="cp_order_id">
        <div class="pay-ac-list" id="cp_order_ac"></div>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Клиент</label>
      <div class="pay-ac-wrap">
        <input type="text" id="cp_customer_search" class="form-control" placeholder="Поиск по имени или email…" autocomplete="off">
        <input type="hidden" id="cp_customer_id">
        <div class="pay-ac-list" id="cp_customer_ac"></div>
      </div>
    </div>
    <div class="mb-3"><label class="form-label">Сумма *</label><input type="number" step="0.01" id="cp_amount" class="form-control" required></div>
    <div class="mb-3">
      <label class="form-label">Валюта</label>
      <select id="cp_currency" class="form-control"><option>BYN</option><option>USD</option><option>EUR</option><option>CNY</option></select>
    </div>
    <div class="mb-3">
      <label class="form-label">Метод</label>
      <select id="cp_method" class="form-control">
        <?php foreach ($methods as $k => $v): ?><option value="<?= $k ?>"><?= Html::encode($v) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Статус</label>
      <select id="cp_status" class="form-control">
        <?php foreach ($statuses as $k => $v): ?><option value="<?= $k ?>"><?= Html::encode($v) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3"><label class="form-label">Реквизит банка</label><input type="text" id="cp_bank_ref" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Описание</label><textarea id="cp_desc" class="form-control" rows="3"></textarea></div>
  </div>
  <div class="pay-panel-footer">
    <button class="admin-btn admin-btn-secondary" onclick="closePayPanel()">Отмена</button>
    <button class="admin-btn admin-btn-primary" onclick="submitCreatePayment()">Сохранить</button>
  </div>
</div>

<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

document.addEventListener('DOMContentLoaded', function() {
    if (typeof AdminTable !== 'undefined') AdminTable.init({ pageKey: '<?= $storageKey ?>' });
});

function openCreatePayment() {
    document.getElementById('payPanelOverlay').classList.add('active');
    document.getElementById('payPanel').classList.add('active');
    document.getElementById('cp_amount').focus();
}
function closePayPanel() {
    document.getElementById('payPanelOverlay').classList.remove('active');
    document.getElementById('payPanel').classList.remove('active');
}
function closeModal(id) {
    closePayPanel();
}

// Autocomplete helper
function acSearch(inputId, hiddenId, acListId, url, renderFn, selectFn) {
    var inp = document.getElementById(inputId);
    var hid = document.getElementById(hiddenId);
    var lst = document.getElementById(acListId);
    var timer;
    inp.addEventListener('input', function() {
        hid.value = '';
        clearTimeout(timer);
        var q = inp.value.trim();
        if (q.length < 2) { lst.style.display = 'none'; return; }
        timer = setTimeout(function() {
            fetch(url + encodeURIComponent(q)).then(r=>r.json()).then(function(data) {
                lst.innerHTML = '';
                if (!data.length) { lst.style.display = 'none'; return; }
                data.forEach(function(item) {
                    var div = document.createElement('div');
                    div.className = 'pay-ac-item';
                    div.innerHTML = renderFn(item);
                    div.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectFn(item, inp, hid);
                        lst.style.display = 'none';
                    });
                    lst.appendChild(div);
                });
                lst.style.display = 'block';
            });
        }, 250);
    });
    inp.addEventListener('blur', function() { setTimeout(function(){ lst.style.display='none'; }, 200); });
}

acSearch('cp_order_search', 'cp_order_id', 'cp_order_ac',
    '/admin/order/autocomplete?q=',
    function(o){ return '<strong>' + o.order_number + '</strong><span>' + o.client_name + '</span>'; },
    function(o, inp, hid){ inp.value = o.order_number + ' — ' + o.client_name; hid.value = o.id; }
);
acSearch('cp_customer_search', 'cp_customer_id', 'cp_customer_ac',
    '/admin/customer/autocomplete?q=',
    function(c){ return '<strong>' + c.name + '</strong><span>' + c.email + '</span>'; },
    function(c, inp, hid){ inp.value = c.name + ' (' + c.email + ')'; hid.value = c.id; }
);

function submitCreatePayment() {
    const body = {
        order_id:       document.getElementById('cp_order_id').value || null,
        customer_id:    document.getElementById('cp_customer_id').value || null,
        amount:         document.getElementById('cp_amount').value,
        currency:       document.getElementById('cp_currency').value,
        payment_method: document.getElementById('cp_method').value,
        status:         document.getElementById('cp_status').value,
        bank_reference: document.getElementById('cp_bank_ref').value,
        description:    document.getElementById('cp_desc').value,
    };
    fetch('/admin/finance/create-payment', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify(body)
    }).then(r=>r.json()).then(d => {
        if (d.success) { window.location.reload(); }
        else { alert('Ошибка: ' + JSON.stringify(d.errors)); }
    });
}

function confirmPayment(id) {
    if (!confirm('Подтвердить платёж #' + id + '?')) return;
    fetch('/admin/finance/confirm-payment', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({id})
    }).then(r=>r.json()).then(d => {
        if (d.success) window.location.reload();
        else alert('Ошибка');
    });
}
</script>
