<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\procurement\models\PurchaseOrder[] $orders */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Приёмка товара';

$this->params['breadcrumbs'][] = ['label' => 'Закупки', 'url' => ['/admin/procurement']];
$this->params['breadcrumbs'][] = 'Приёмка';
?>

<style>
.filter-wrap{margin-bottom:14px}
.compact-filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:var(--admin-surface,#fff)}
.filter-row1{border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.compact-filter-btn{height:32px;padding:0 12px;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6b7280)}
.compact-filter-btn:hover{background:#e5e7eb;color:#374151;text-decoration:none}
.compact-filter-btn--apply{background:var(--admin-primary,#2563eb);color:#fff}
.compact-filter-btn--apply:hover{background:#1d4ed8;color:#fff}
.compact-filter-btn--success{background:#059669;color:#fff}
.compact-filter-btn--success:hover{background:#047857;color:#fff}
.status-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;line-height:1.6}

/* Card styles */
.rcv-card{background:var(--admin-surface,#fff);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:16px;overflow:hidden;transition:box-shadow .2s}
.rcv-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.1)}
.rcv-card-header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;flex-wrap:wrap;gap:8px;cursor:pointer;user-select:none;transition:background .15s}
.rcv-card-header:hover{background:var(--admin-surface-hover,#f9fafb)}
.rcv-progress{height:4px;background:var(--admin-border,#e5e7eb);width:100%}
.rcv-progress-bar{height:100%;background:#059669;transition:width .3s}

/* Chevron */
.rcv-chevron{transition:transform .25s ease;font-size:.875rem;color:var(--admin-text-secondary,#6b7280)}
.rcv-card.collapsed .rcv-chevron{transform:rotate(-90deg)}
.rcv-card.collapsed .rcv-body{display:none}

/* Table */
.rcv-table{width:100%;font-size:.8125rem;border-collapse:collapse}
.rcv-table thead th{position:sticky;top:0;z-index:10;background:var(--admin-surface,#fff);box-shadow:0 1px 0 var(--admin-border,#e5e7eb);padding:8px 12px;text-align:left;font-weight:600;font-size:.75rem;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.03em}
.rcv-table tbody td{padding:8px 12px;border-bottom:1px solid var(--admin-border,#e5e7eb);transition:background .15s}
.rcv-table tbody tr:hover{background:var(--admin-surface-hover,#f5f7fa)}
.rcv-input{height:30px;width:70px;border:1.5px solid var(--admin-border,#e5e7eb);border-radius:6px;padding:0 8px;font-size:.8125rem;text-align:right;outline:none;transition:border-color .15s}
.rcv-input:focus{border-color:var(--admin-primary,#2563eb)}

/* Row color coding */
.rcv-row-full{background:rgba(5,150,105,.06)}
.rcv-row-partial{background:rgba(217,119,6,.06)}

/* Footer summary */
.rcv-footer{display:flex;justify-content:space-between;align-items:center;padding:10px 16px;background:var(--admin-surface-hover,#f9fafb);border-top:1px solid var(--admin-border,#e5e7eb);font-size:.75rem;color:var(--admin-text-secondary,#6b7280);flex-wrap:wrap;gap:8px}
.rcv-footer strong{color:var(--admin-text-primary,#111)}

/* Modal */
.rcv-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center}
.rcv-modal-overlay.active{display:flex}
.rcv-modal{background:var(--admin-surface,#fff);border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.18);width:94%;max-width:720px;max-height:85vh;overflow-y:auto;animation:rcvSlideUp .25s ease}
@keyframes rcvSlideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.rcv-modal-header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--admin-border,#e5e7eb)}
.rcv-modal-header h3{margin:0;font-size:1rem;font-weight:700;color:var(--admin-text-primary,#111)}
.rcv-modal-close{background:none;border:none;font-size:1.25rem;cursor:pointer;color:var(--admin-text-secondary,#6b7280);padding:4px 8px;border-radius:6px;transition:background .15s}
.rcv-modal-close:hover{background:var(--admin-surface-hover,#f3f4f6)}
.rcv-modal-body{padding:16px 20px}
.rcv-modal-footer{padding:12px 20px;border-top:1px solid var(--admin-border,#e5e7eb);display:flex;justify-content:flex-end;gap:8px}
.rcv-form-group{margin-bottom:14px}
.rcv-form-group label{display:block;font-size:.75rem;font-weight:600;color:var(--admin-text-secondary,#6b7280);margin-bottom:4px;text-transform:uppercase;letter-spacing:.03em}
.rcv-form-control{width:100%;height:36px;border:1.5px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:0 10px;font-size:.8125rem;outline:none;transition:border-color .15s;background:var(--admin-surface,#fff)}
.rcv-form-control:focus{border-color:var(--admin-primary,#2563eb)}
textarea.rcv-form-control{height:60px;padding:8px 10px;resize:vertical}

/* Modal items table */
.rcv-modal-items{margin-top:8px}
.rcv-modal-items .rcv-table{font-size:.8125rem}

/* Responsive */
@media(max-width:640px){
    .rcv-card-header{padding:10px 12px}
    .rcv-table thead th,.rcv-table tbody td{padding:6px 8px}
    .rcv-modal{width:98%;max-height:90vh}
    .rcv-footer{flex-direction:column;align-items:flex-start}
}
</style>

<!-- Header -->
<div class="compact-filter-bar filter-row1 filter-wrap">
    <button class="compact-filter-btn compact-filter-btn--apply" onclick="openCreateReceiving()" style="font-size:.8125rem">
        <i class="bi bi-plus-circle"></i> Создать приёмку
    </button>
    <span style="font-size:.8125rem;color:var(--admin-text-secondary,#6b7280);margin-left:8px">
        <i class="bi bi-info-circle"></i> Заказы в статусе «Заказано» или «В пути»
    </span>
    <a href="/admin/procurement" class="compact-filter-btn" style="margin-left:auto">
        <i class="bi bi-arrow-left"></i> К закупкам
    </a>
</div>

<?php if (!$orders): ?>
<div class="admin-card" style="text-align:center;padding:2.5rem">
    <div style="font-size:48px;opacity:.3"><i class="bi bi-box-seam"></i></div>
    <h3 style="margin-top:12px;font-size:1rem;color:var(--admin-text-primary,#111)">Нет закупок, ожидающих приёмки</h3>
    <p style="color:var(--admin-text-secondary,#6b7280);font-size:.875rem">Все заказы уже получены</p>
</div>
<?php endif; ?>

<?php foreach ($orders as $po):
    $totalQty = array_sum(array_column($po->items, 'quantity'));
    $rcvQty   = array_sum(array_column($po->items, 'received_quantity'));
    $pct      = $po->getReceivedPercent();
    $itemCount = count($po->items);
    $remaining = $totalQty - $rcvQty;
?>
<div class="rcv-card collapsed" id="po-card-<?= $po->id ?>">
    <div class="rcv-card-header" onclick="toggleCard(<?= $po->id ?>)">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <i class="bi bi-chevron-down rcv-chevron"></i>
            <a href="/admin/procurement/view/<?= $po->id ?>" style="font-weight:700;color:var(--admin-text-primary,#111);text-decoration:none;font-size:.875rem" onclick="event.stopPropagation()">
                <?= Html::encode($po->purchase_number) ?>
            </a>
            <span class="status-pill" style="background:<?= $po->getStatusColor() ?>;color:#fff"><?= Html::encode($po->getStatusLabel()) ?></span>
            <span style="font-size:.8125rem;color:var(--admin-text-secondary,#6b7280)"><?= Html::encode($po->supplier->name ?? '') ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap" onclick="event.stopPropagation()">
            <span style="font-size:.75rem;color:var(--admin-text-secondary,#6b7280)">
                Получено: <strong id="rcv-count-<?= $po->id ?>"><?= $rcvQty ?></strong>/<?= $totalQty ?>
                (<span id="rcv-pct-<?= $po->id ?>"><?= $pct ?></span>%)
            </span>
            <button class="compact-filter-btn compact-filter-btn--success" onclick="acceptAll(<?= $po->id ?>)" style="height:28px;font-size:.75rem" title="Заполнить все количества">
                <i class="bi bi-check-all"></i> Принять всё
            </button>
            <button class="compact-filter-btn compact-filter-btn--apply" onclick="saveReceiving(<?= $po->id ?>)" style="height:28px;font-size:.75rem">
                <i class="bi bi-check-circle"></i> Сохранить
            </button>
        </div>
    </div>
    <div class="rcv-progress">
        <div class="rcv-progress-bar" id="rcv-bar-<?= $po->id ?>" style="width:<?= $pct ?>%"></div>
    </div>
    <div class="rcv-body">
        <table class="rcv-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Размер</th>
                    <th style="text-align:right">Заказано</th>
                    <th style="text-align:right;width:100px">Получено</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po->items as $item):
                    $rowClass = '';
                    if ($item->received_quantity >= $item->quantity && $item->quantity > 0) {
                        $rowClass = 'rcv-row-full';
                    } elseif ($item->received_quantity > 0) {
                        $rowClass = 'rcv-row-partial';
                    }
                ?>
                <tr class="<?= $rowClass ?>" id="row-item-<?= $item->id ?>">
                    <td><?= Html::encode($item->product_name) ?></td>
                    <td style="color:var(--admin-text-secondary,#6b7280)"><?= Html::encode($item->size ?? '—') ?></td>
                    <td style="text-align:right"><?= $item->quantity ?></td>
                    <td style="text-align:right">
                        <input type="number"
                               class="rcv-input"
                               data-item-id="<?= $item->id ?>"
                               data-po-id="<?= $po->id ?>"
                               data-ordered="<?= $item->quantity ?>"
                               value="<?= $item->received_quantity ?>"
                               min="0" max="<?= $item->quantity ?>"
                               oninput="updateProgress(<?= $po->id ?>)">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="rcv-footer" id="rcv-footer-<?= $po->id ?>">
            <div>
                Позиций: <strong><?= $itemCount ?></strong>
                &nbsp;&middot;&nbsp;
                Всего заказано: <strong><?= $totalQty ?></strong>
                &nbsp;&middot;&nbsp;
                Получено: <strong id="rcv-footer-received-<?= $po->id ?>"><?= $rcvQty ?></strong>
                &nbsp;&middot;&nbsp;
                Осталось: <strong id="rcv-footer-remaining-<?= $po->id ?>"><?= $remaining ?></strong>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Create Receiving Modal -->
<div class="rcv-modal-overlay" id="createReceivingModal">
    <div class="rcv-modal">
        <div class="rcv-modal-header">
            <h3><i class="bi bi-box-seam"></i> Создать приёмку</h3>
            <button class="rcv-modal-close" onclick="closeCreateReceiving()">&times;</button>
        </div>
        <div class="rcv-modal-body">
            <div class="rcv-form-group">
                <label>Закупка</label>
                <select class="rcv-form-control" id="modal-po-select" onchange="onModalPoChange()">
                    <option value="">-- Выберите закупку --</option>
                    <?php foreach ($orders as $po): ?>
                    <option value="<?= $po->id ?>"><?= Html::encode($po->purchase_number . ' — ' . ($po->supplier->name ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="rcv-form-group">
                <label>Дата приёмки</label>
                <input type="date" class="rcv-form-control" id="modal-rcv-date" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="rcv-form-group">
                <label>Примечания</label>
                <textarea class="rcv-form-control" id="modal-rcv-notes" placeholder="Комментарий к приёмке..."></textarea>
            </div>
            <div class="rcv-modal-items" id="modal-items-wrap" style="display:none">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <label style="font-size:.75rem;font-weight:600;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.03em;margin:0">Товары</label>
                    <button class="compact-filter-btn compact-filter-btn--success" onclick="modalAcceptAll()" style="height:26px;font-size:.7rem">
                        <i class="bi bi-check-all"></i> Принять всё
                    </button>
                </div>
                <table class="rcv-table" id="modal-items-table">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Размер</th>
                            <th style="text-align:right">Заказано</th>
                            <th style="text-align:right">Уже получено</th>
                            <th style="text-align:right;width:100px">Принять</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div class="rcv-modal-footer">
            <button class="compact-filter-btn" onclick="closeCreateReceiving()">Отмена</button>
            <button class="compact-filter-btn compact-filter-btn--apply" onclick="submitCreateReceiving()" id="modal-submit-btn">
                <i class="bi bi-check-circle"></i> Сохранить приёмку
            </button>
        </div>
    </div>
</div>

<script src="/js/admin-table-utils.js"></script>
<script>
const csrf = <?= json_encode(\Yii::$app->request->csrfToken) ?>;

// Pre-populate order data for the modal
var ordersData = <?= json_encode(array_map(function($po) {
    return [
        'id' => $po->id,
        'purchase_number' => $po->purchase_number,
        'supplier' => $po->supplier->name ?? '',
        'items' => array_map(function($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'size' => $item->size ?? '—',
                'quantity' => $item->quantity,
                'received_quantity' => $item->received_quantity,
            ];
        }, $po->items),
    ];
}, $orders)) ?>;

// ─── Collapse / Expand ─────────────────────────────────────────────────

function toggleCard(poId) {
    var card = document.getElementById('po-card-' + poId);
    if (card) card.classList.toggle('collapsed');
}

// ─── Accept All (fill all inputs to ordered qty) ────────────────────────

function acceptAll(poId) {
    var inputs = document.querySelectorAll('.rcv-input[data-po-id="' + poId + '"]');
    inputs.forEach(function(inp) {
        inp.value = inp.dataset.ordered;
    });
    updateProgress(poId);
}

// ─── Update Progress (enhanced with color coding + footer) ──────────────

function updateProgress(poId) {
    var inputs = document.querySelectorAll('.rcv-input[data-po-id="' + poId + '"]');
    var totalOrdered = 0, totalReceived = 0;
    inputs.forEach(function(inp) {
        var ordered = parseInt(inp.dataset.ordered);
        var received = parseInt(inp.value) || 0;
        totalOrdered  += ordered;
        totalReceived += received;

        // Color coding per row
        var row = inp.closest('tr');
        if (row) {
            row.classList.remove('rcv-row-full', 'rcv-row-partial');
            if (received >= ordered && ordered > 0) {
                row.classList.add('rcv-row-full');
            } else if (received > 0) {
                row.classList.add('rcv-row-partial');
            }
        }
    });
    var pct = totalOrdered > 0 ? Math.round(totalReceived / totalOrdered * 100) : 0;
    document.getElementById('rcv-count-' + poId).textContent = totalReceived;
    document.getElementById('rcv-pct-' + poId).textContent   = pct;
    document.getElementById('rcv-bar-' + poId).style.width   = pct + '%';

    // Update footer
    var footerReceived = document.getElementById('rcv-footer-received-' + poId);
    var footerRemaining = document.getElementById('rcv-footer-remaining-' + poId);
    if (footerReceived) footerReceived.textContent = totalReceived;
    if (footerRemaining) footerRemaining.textContent = totalOrdered - totalReceived;
}

// ─── Save Receiving ─────────────────────────────────────────────────────

function saveReceiving(poId) {
    var inputs = document.querySelectorAll('.rcv-input[data-po-id="' + poId + '"]');
    var items  = [];
    inputs.forEach(function(inp) {
        items.push({ id: parseInt(inp.dataset.itemId), received_qty: parseInt(inp.value) || 0 });
    });

    fetch('/admin/procurement/receive-items', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({ purchase_order_id: poId, items: items })
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.success) {
            if (d.received_pct >= 100) {
                document.getElementById('po-card-' + poId).style.opacity = '0.5';
                alert('Закупка полностью получена!');
            } else {
                alert('Сохранено. Получено: ' + d.received_pct + '%');
            }
        } else {
            alert('Ошибка: ' + d.message);
        }
    });
}

// ─── Create Receiving Modal ─────────────────────────────────────────────

function openCreateReceiving() {
    document.getElementById('createReceivingModal').classList.add('active');
    document.getElementById('modal-po-select').value = '';
    document.getElementById('modal-rcv-date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('modal-rcv-notes').value = '';
    document.getElementById('modal-items-wrap').style.display = 'none';
    document.querySelector('#modal-items-table tbody').innerHTML = '';
}

function closeCreateReceiving() {
    document.getElementById('createReceivingModal').classList.remove('active');
}

// Close modal on overlay click
document.getElementById('createReceivingModal').addEventListener('click', function(e) {
    if (e.target === this) closeCreateReceiving();
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCreateReceiving();
});

function onModalPoChange() {
    var poId = parseInt(document.getElementById('modal-po-select').value);
    var tbody = document.querySelector('#modal-items-table tbody');
    tbody.innerHTML = '';

    if (!poId) {
        document.getElementById('modal-items-wrap').style.display = 'none';
        return;
    }

    var order = null;
    for (var i = 0; i < ordersData.length; i++) {
        if (ordersData[i].id === poId) { order = ordersData[i]; break; }
    }
    if (!order) return;

    document.getElementById('modal-items-wrap').style.display = 'block';

    order.items.forEach(function(item) {
        var remaining = item.quantity - item.received_quantity;
        var rowClass = '';
        if (item.received_quantity >= item.quantity && item.quantity > 0) rowClass = 'rcv-row-full';
        else if (item.received_quantity > 0) rowClass = 'rcv-row-partial';

        var tr = document.createElement('tr');
        tr.className = rowClass;
        tr.innerHTML =
            '<td>' + escapeHtml(item.product_name) + '</td>' +
            '<td style="color:var(--admin-text-secondary,#6b7280)">' + escapeHtml(item.size) + '</td>' +
            '<td style="text-align:right">' + item.quantity + '</td>' +
            '<td style="text-align:right">' + item.received_quantity + '</td>' +
            '<td style="text-align:right">' +
                '<input type="number" class="rcv-input modal-rcv-input" ' +
                    'data-item-id="' + item.id + '" ' +
                    'data-ordered="' + item.quantity + '" ' +
                    'data-already-received="' + item.received_quantity + '" ' +
                    'value="' + remaining + '" ' +
                    'min="0" max="' + remaining + '">' +
            '</td>';
        tbody.appendChild(tr);
    });
}

function modalAcceptAll() {
    var inputs = document.querySelectorAll('.modal-rcv-input');
    inputs.forEach(function(inp) {
        var ordered = parseInt(inp.dataset.ordered);
        var alreadyReceived = parseInt(inp.dataset.alreadyReceived) || 0;
        inp.value = ordered - alreadyReceived;
    });
}

function submitCreateReceiving() {
    var poId = parseInt(document.getElementById('modal-po-select').value);
    if (!poId) { alert('Выберите закупку'); return; }

    var inputs = document.querySelectorAll('.modal-rcv-input');
    var items = [];
    inputs.forEach(function(inp) {
        var alreadyReceived = parseInt(inp.dataset.alreadyReceived) || 0;
        var newQty = parseInt(inp.value) || 0;
        items.push({
            id: parseInt(inp.dataset.itemId),
            received_qty: alreadyReceived + newQty
        });
    });

    var btn = document.getElementById('modal-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';

    fetch('/admin/procurement/receive-items', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':csrf},
        body: JSON.stringify({
            purchase_order_id: poId,
            items: items,
            notes: document.getElementById('modal-rcv-notes').value,
            received_date: document.getElementById('modal-rcv-date').value
        })
    }).then(function(r) { return r.json(); }).then(function(d) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить приёмку';
        if (d.success) {
            closeCreateReceiving();
            if (d.received_pct >= 100) {
                alert('Закупка полностью получена!');
            } else {
                alert('Приёмка сохранена. Получено: ' + d.received_pct + '%');
            }
            location.reload();
        } else {
            alert('Ошибка: ' + (d.message || 'Неизвестная ошибка'));
        }
    }).catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить приёмку';
        alert('Ошибка сети');
    });
}

function escapeHtml(text) {
    var d = document.createElement('div');
    d.textContent = text || '';
    return d.innerHTML;
}
</script>
