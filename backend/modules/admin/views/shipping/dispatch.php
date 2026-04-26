<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $orders */
/** @var string $carrier */
/** @var string[] $carriers */
/** @var array $counts */

$this->title = 'Отправка заказов';

$this->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ['/admin/shipping']];
$this->params['breadcrumbs'][] = 'Отправка';

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-printer"></i> Печать накладных', '#', [
        'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
        'id'    => 'btn-print-labels',
        'onclick' => 'printSelectedLabels(); return false;',
    ]),
    Html::a('<i class="bi bi-check2-all"></i> Отметить отправленными', '#', [
        'class'   => 'admin-btn admin-btn-primary admin-btn-sm',
        'id'      => 'btn-mark-shipped',
        'onclick' => 'markSelectedShipped(); return false;',
    ]),
];

$carrierLabels = [
    'europochta' => 'Европочта',
    'belpochta'  => 'Белпочта',
    'cdek'       => 'СДЭК',
];
$carrierIcons = [
    'europochta' => 'bi-envelope-arrow-up',
    'belpochta'  => 'bi-mailbox',
    'cdek'       => 'bi-truck',
];
$carrierColors = [
    'europochta' => '#e63946',
    'belpochta'  => '#2563eb',
    'cdek'       => '#16a34a',
];
?>

<style>
/* === Dispatch — theme-aware styles === */
.dispatch-carrier-label { font-weight: 700; display: inline-flex; align-items: center; gap: 5px; font-size: 13px; }
.dispatch-carrier-label.europochta { color: #e63946; }
.dispatch-carrier-label.belpochta  { color: #2563eb; }
.dispatch-carrier-label.cdek       { color: #16a34a; }
[data-theme="dark"] .dispatch-carrier-label.cdek { color: #22c55e; }

.dispatch-track-code {
    font-size: 12px;
    font-family: monospace;
    background: var(--admin-surface-hover, #f1f5f9);
    color: var(--admin-text-primary, #111);
    padding: 2px 6px;
    border-radius: 4px;
}

/* Select-all label */
.dispatch-select-label {
    font-size: 13px;
    color: var(--admin-text-secondary, #6b7280);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Summary footer */
.dispatch-summary {
    margin-top: 1rem;
    text-align: right;
    font-size: 13px;
    color: var(--admin-text-secondary, #6b7280);
}
.dispatch-summary strong { color: var(--admin-text-primary, #111); }
</style>

<!-- Tabs по перевозчикам -->
<div class="admin-card" style="margin-bottom:1.5rem">
    <div class="admin-card-body" style="padding:1rem 1.25rem">
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
            <a href="<?= Url::to(['/admin/shipping/dispatch']) ?>"
               class="admin-btn admin-btn-sm <?= $carrier === '' ? 'admin-btn-primary' : 'admin-btn-secondary' ?>">
                <i class="bi bi-grid-3x2-gap"></i> Все
                <span class="admin-badge admin-badge-<?= $carrier === '' ? 'light' : 'secondary' ?>" style="margin-left:4px">
                    <?= array_sum($counts) ?>
                </span>
            </a>
            <?php foreach ($carriers as $c): ?>
            <a href="<?= Url::to(['/admin/shipping/dispatch', 'carrier' => $c]) ?>"
               class="admin-btn admin-btn-sm <?= $carrier === $c ? 'admin-btn-primary' : 'admin-btn-secondary' ?>">
                <i class="bi <?= $carrierIcons[$c] ?? 'bi-truck' ?>"></i>
                <?= $carrierLabels[$c] ?>
                <?php if (!empty($counts[$c])): ?>
                <span class="admin-badge <?= $carrier === $c ? 'admin-badge-light' : 'admin-badge-secondary' ?>" style="margin-left:4px">
                    <?= (int)$counts[$c] ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>

            <!-- Select all / none -->
            <div style="margin-left:auto;display:flex;align-items:center;gap:0.5rem">
                <label style="font-size:13px;color:var(--admin-text-secondary);cursor:pointer;display:flex;align-items:center;gap:6px">
                    <input type="checkbox" id="check-all" onchange="toggleAllOrders(this.checked)"> Выбрать все
                </label>
                <span id="selected-count" style="font-size:12px;color:var(--admin-text-secondary)">0 выбрано</span>
            </div>
        </div>
    </div>
</div>

<?php if (empty($orders)): ?>
<div class="admin-card">
    <div class="admin-card-body" style="text-align:center;padding:3rem">
        <i class="bi bi-inbox" style="font-size:2.5rem;color:var(--admin-text-secondary);display:block;margin-bottom:1rem"></i>
        <div style="font-size:16px;font-weight:600;margin-bottom:6px">Нет заказов для отправки</div>
        <div style="color:var(--admin-text-secondary);font-size:14px">
            Заказы со статусом «Оплачен» или «В обработке» через
            <?= implode(', ', array_map(fn($c) => $carrierLabels[$c], $carriers)) ?>
            появятся здесь.
        </div>
    </div>
</div>
<?php else: ?>

<div class="admin-card">
    <div style="overflow-x:auto">
        <table class="admin-table" id="dispatch-table">
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="check-all-head" onchange="toggleAllOrders(this.checked)"></th>
                    <th>#</th>
                    <th>Получатель</th>
                    <th>Адрес</th>
                    <th>Перевозчик</th>
                    <th>Трек-номер</th>
                    <th style="text-align:right">Сумма</th>
                    <th>Статус</th>
                    <th>Дата заказа</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <?php
                $method     = $order->delivery_method ?? '';
                $color      = $carrierColors[$method] ?? '#64748b';
                $label      = $carrierLabels[$method] ?? $method;
                $icon       = $carrierIcons[$method] ?? 'bi-truck';
                $statusMap  = ['paid' => ['Оплачен', 'success'], 'processing' => ['В обработке', 'warning']];
                [$statusLabel, $statusColor] = $statusMap[$order->status] ?? [$order->status, 'secondary'];
                $recipient  = trim(($order->recipient_last_name ?? '') . ' ' . ($order->recipient_first_name ?? ''));
                if (!$recipient) $recipient = $order->client_name ?? '—';
                $address    = $order->delivery_address ?? $order->full_address ?? '—';
                $trackNum   = $order->china_track_number ?? '';
                ?>
                <tr data-id="<?= $order->id ?>">
                    <td>
                        <input type="checkbox" class="order-check" value="<?= $order->id ?>" onchange="updateSelectedCount()">
                    </td>
                    <td>
                        <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>" class="admin-link" style="font-weight:600">
                            #<?= Html::encode($order->order_number ?? $order->id) ?>
                        </a>
                    </td>
                    <td>
                        <div style="font-weight:500"><?= Html::encode($recipient) ?></div>
                        <?php if (!empty($order->client_phone)): ?>
                        <div style="font-size:12px;color:var(--admin-text-secondary)"><?= Html::encode($order->client_phone) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="max-width:200px">
                        <div style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= Html::encode($address) ?>">
                            <?= Html::encode($address) ?>
                        </div>
                    </td>
                    <td>
                        <span class="dispatch-carrier-label <?= Html::encode($method) ?>">
                            <i class="bi <?= $icon ?>"></i> <?= Html::encode($label) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($trackNum): ?>
                        <code class="dispatch-track-code"><?= Html::encode($trackNum) ?></code>
                        <?php else: ?>
                        <span style="color:var(--admin-text-secondary,#6b7280);font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;font-weight:600">
                        <?= number_format((float)($order->total_amount ?? 0), 2, ',', ' ') ?> BYN
                    </td>
                    <td>
                        <span class="admin-badge admin-badge-<?= $statusColor ?>"><?= Html::encode($statusLabel) ?></span>
                    </td>
                    <td style="font-size:12px;color:var(--admin-text-secondary)">
                        <?= $order->created_at ? date('d.m.Y', (int)$order->created_at) : '—' ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px">
                            <a href="<?= Url::to(['/admin/pdf/invoice', 'id' => $order->id]) ?>"
                               target="_blank"
                               class="admin-btn admin-btn-sm admin-btn-secondary"
                               title="Накладная PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>"
                               class="admin-btn admin-btn-sm admin-btn-secondary"
                               title="Открыть заказ">
                                <i class="bi bi-arrow-up-right-square"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Итог -->
<div class="dispatch-summary">
    Итого заказов к отправке: <strong><?= count($orders) ?></strong>
    &nbsp;·&nbsp;
    Сумма: <strong><?= number_format(array_sum(array_column(array_map(fn($o) => ['a' => (float)($o->total_amount ?? 0)], $orders), 'a')), 2, ',', ' ') ?> BYN</strong>
</div>

<?php endif; ?>

<script>
function toggleAllOrders(checked) {
    document.querySelectorAll('.order-check').forEach(cb => cb.checked = checked);
    document.getElementById('check-all').checked = checked;
    document.getElementById('check-all-head') && (document.getElementById('check-all-head').checked = checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const n = document.querySelectorAll('.order-check:checked').length;
    document.getElementById('selected-count').textContent = n + ' выбрано';
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.order-check:checked')).map(cb => cb.value);
}

function printSelectedLabels() {
    const ids = getSelectedIds();
    if (!ids.length) { alert('Выберите хотя бы один заказ'); return; }
    ids.forEach(id => window.open('<?= Url::to(['/admin/pdf/invoice', 'id' => '__ID__']) ?>'.replace('__ID__', id), '_blank'));
}

function markSelectedShipped() {
    const ids = getSelectedIds();
    if (!ids.length) { alert('Выберите хотя бы один заказ'); return; }
    if (!confirm('Отметить ' + ids.length + ' заказ(ов) как отправленные?')) return;

    fetch('<?= Url::to(['/admin/shipping/mark-shipped']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>',
        },
        body: 'ids[]=' + ids.join('&ids[]='),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Remove shipped rows from table
            ids.forEach(id => {
                const row = document.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.style.transition = 'opacity .3s';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }
            });
            // Update counter badge
            const badge = document.querySelector('.admin-btn.active .admin-badge, .admin-btn-primary .admin-badge');
            updateSelectedCount();
        } else {
            alert('Ошибка: ' + (data.message || 'неизвестная'));
        }
    })
    .catch(() => alert('Ошибка сети'));
}
</script>
