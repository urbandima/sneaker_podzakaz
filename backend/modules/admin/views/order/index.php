<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string|null $filterStatus */
/** @var string|null $filterLogist */
/** @var string|null $filterSearch */
/** @var string|null $filterProcessed */
/** @var string|null $filterDateFrom */
/** @var string|null $filterDateTo */
/** @var array $statusCounts */
/** @var int $totalCount */
/** @var array $monthlySummary */
/** @var array $kpiSummary */
/** @var array $pipelineStats */
/** @var int $processedCount */
/** @var int $shippedCount */
/** @var int $customsClearedCount */
/** @var int $pageSize */
/** @var array $pageSizeOptions */
/** @var \app\backend\modules\admin\models\User[] $logists */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Управление заказами';

$user       = Yii::$app->user->identity;
$orders     = $dataProvider->getModels();
$pagination = $dataProvider->pagination;
$statuses   = Yii::$app->settings->getStatuses();
$formatter  = Yii::$app->formatter;
$currentPage = $pagination->page;
$totalPages  = $pagination->pageCount;

$logistMap = [];
foreach ($logists as $logist) {
    $logistMap[$logist->id] = $logist->username;
}

$this->params['breadcrumbs'][] = ['label' => 'Заказы', 'url' => ['/admin/order/index']];

// Kanban column definitions
$kanbanColumns = [
    'new'        => 'Новый',
    'paid'       => 'Оплачен',
    'ordered'    => 'Выкуплен',
    'shipped'    => 'В доставке',
    'delivered'  => 'На складе',
    'transferred'=> 'Передан в РБ',
    'completed'  => 'Завершён',
    'canceled'   => 'Отменён',
];

// Merge with all DB statuses (keep Kanban order first, then extras)
foreach ($statuses as $k => $v) {
    if (!isset($kanbanColumns[$k])) {
        $kanbanColumns[$k] = $v;
    }
}

// Group orders by status for Kanban
$kanbanGroups = [];
foreach ($kanbanColumns as $k => $v) {
    $kanbanGroups[$k] = [];
}
foreach ($orders as $order) {
    $s = $order->status;
    if (!isset($kanbanGroups[$s])) {
        $kanbanGroups[$s] = [];
    }
    $kanbanGroups[$s][] = $order;
}

// Active filter badges
$activeFilters = [];
if (!empty($filterSearch))   $activeFilters[] = ['Поиск',   $filterSearch];
if (!empty($filterLogist))   $activeFilters[] = ['Логист',  $logistMap[$filterLogist] ?? 'ID '.$filterLogist];
if ($filterProcessed !== null && $filterProcessed !== '') $activeFilters[] = ['Обработка', $filterProcessed === '1' ? 'Обработано' : 'Не обработано'];
if (!empty($filterDateFrom) || !empty($filterDateTo)) $activeFilters[] = ['Диапазон', trim(($filterDateFrom ?: '…').' — '.($filterDateTo ?: '…'))];

// Restore filter state from session (advanced filters)
$session = Yii::$app->session;
$filterDelivery = Yii::$app->request->get('delivery_method', $session->get('filter_delivery', ''));
$filterPayment  = Yii::$app->request->get('payment_method',  $session->get('filter_payment',  ''));
$filterHasTrack = Yii::$app->request->get('has_track',       $session->get('filter_has_track', ''));
$filterStatuses = Yii::$app->request->get('statuses', $session->get('filter_statuses', []));

if (Yii::$app->request->isGet && Yii::$app->request->get('_filter_save') === '1') {
    $session->set('filter_delivery',  $filterDelivery);
    $session->set('filter_payment',   $filterPayment);
    $session->set('filter_has_track', $filterHasTrack);
    $session->set('filter_statuses',  $filterStatuses);
}

$showingFrom = $totalCount ? ($pagination->page * $pagination->pageSize) + 1 : 0;
$showingTo   = $totalCount ? $showingFrom + count($orders) - 1 : 0;
?>

<?php $this->registerCss(<<<CSS
/* ── Order Index ── */
.view-toggle { display:flex; gap:.375rem; }
.view-toggle-btn {
    padding:.4rem .9rem; border:1px solid var(--admin-border);
    border-radius:.5rem; background:var(--admin-bg); color:var(--admin-text-secondary);
    cursor:pointer; font-size:.875rem; font-weight:600; transition:all .2s;
    display:inline-flex; align-items:center; gap:.35rem;
}
.view-toggle-btn.active {
    background:var(--admin-primary,#2563eb); color:#fff;
    border-color:var(--admin-primary,#2563eb);
}
.view-toggle-btn:hover:not(.active){ background:var(--admin-surface,#f8f9fa); }

/* Kanban */
.kanban-board {
    display:flex; gap:1rem; overflow-x:auto;
    padding-bottom:1rem; min-height:320px;
}
.kanban-col {
    min-width:240px; max-width:260px; flex-shrink:0;
    background:var(--admin-surface,#f5f6fa);
    border:1px solid var(--admin-border,#e5e7eb);
    border-radius:.75rem; display:flex; flex-direction:column;
}
.kanban-col-header {
    padding:.625rem 1rem; font-weight:700; font-size:.8125rem;
    text-transform:uppercase; letter-spacing:.04em;
    display:flex; justify-content:space-between; align-items:center;
    border-bottom:1px solid var(--admin-border,#e5e7eb);
}
.kanban-col-badge {
    background:var(--admin-border,#e5e7eb);
    color:var(--admin-text-secondary,#6b7280);
    border-radius:999px; padding:.1rem .5rem; font-size:.75rem;
}
.kanban-cards {
    flex:1; padding:.5rem; display:flex; flex-direction:column;
    gap:.5rem; overflow-y:auto; max-height:560px; min-height:60px;
}
.kanban-card {
    background:#fff; border:1px solid var(--admin-border,#e5e7eb);
    border-radius:.5rem; padding:.75rem; cursor:grab; user-select:none;
    transition:box-shadow .15s, transform .15s;
    font-size:.8125rem;
}
.kanban-card:active{ cursor:grabbing; transform:rotate(1deg); box-shadow:0 8px 24px rgba(0,0,0,.12); }
.kanban-card.drag-over-target{ border:2px dashed var(--admin-primary,#2563eb); }
.kanban-card .kc-num { font-weight:700; color:var(--admin-accent,#2563eb); }
.kanban-card .kc-client { font-weight:600; margin:.1rem 0; }
.kanban-card .kc-product { color:var(--admin-text-secondary,#6b7280); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px; }
.kanban-card .kc-meta { display:flex; justify-content:space-between; margin-top:.375rem; color:var(--admin-text-secondary,#6b7280); }
.kanban-card .kc-amount { font-weight:700; color:var(--admin-text-primary,#111827); }
.kanban-col.drag-target > .kanban-cards {
    background:rgba(37,99,235,.05);
    border-radius:.5rem;
}

/* Bulk toolbar */
.bulk-toolbar {
    display:none; align-items:center; gap:.5rem; flex-wrap:wrap;
    padding:.75rem 1rem; background:var(--admin-surface,#f5f6fa);
    border:1px solid var(--admin-border,#e5e7eb); border-radius:.5rem;
    margin-bottom:1rem;
}
.bulk-toolbar.visible{ display:flex; }
.bulk-toolbar label{ font-weight:600; font-size:.875rem; margin:0; }

/* Filters advanced */
.filter-section { display:flex; flex-direction:column; gap:.75rem; }
.filter-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.75rem; }
.filter-checkboxes { display:flex; flex-wrap:wrap; gap:.5rem; }
.filter-checkboxes label {
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.25rem .6rem; border:1px solid var(--admin-border,#e5e7eb);
    border-radius:.375rem; cursor:pointer; font-size:.8125rem;
    transition:all .15s;
}
.filter-checkboxes label:hover{ background:var(--admin-surface,#f5f6fa); }
.filter-checkboxes input[type=checkbox]:checked + span {
    color:var(--admin-primary,#2563eb);
}
.form-control {
    width:100%; padding:.625rem .875rem; border:1px solid var(--admin-border,#e5e7eb);
    border-radius:.5rem; background:var(--admin-bg,#fff); color:var(--admin-text-primary,#111827);
    font-size:.875rem; transition:border-color .2s,box-shadow .2s;
}
.form-control:focus {
    outline:none; border-color:var(--admin-primary,#2563eb);
    box-shadow:0 0 0 3px rgba(37,99,235,.1);
}
.form-group{ margin-bottom:.75rem; }
.form-group label { display:block; margin-bottom:.375rem; font-weight:600; color:var(--admin-text-secondary,#6b7280); font-size:.8125rem; }
.pagination{ display:flex; gap:.375rem; list-style:none; padding:0; margin:0; }
.page-link {
    padding:.4rem .7rem; border:1px solid var(--admin-border,#e5e7eb);
    border-radius:.375rem; background:var(--admin-bg,#fff); color:var(--admin-text-primary,#111827);
    text-decoration:none; transition:all .2s;
}
.page-link:hover{ background:var(--admin-primary,#2563eb); color:#fff; border-color:var(--admin-primary,#2563eb); }
CSS
); ?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/order/export-csv'] + Yii::$app->request->get()) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-file-earmark-spreadsheet"></i> Экспорт CSV
        </a>
        <a href="<?= Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i> Новый заказ
        </a>
    </div>
</div>

<!-- Статистика -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= $totalCount ?></p>
        <p class="admin-stat-label">Всего заказов</p>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-success);">
        <p class="admin-stat-number"><?= $processedCount ?></p>
        <p class="admin-stat-label">Обработано</p>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-warning);">
        <p class="admin-stat-number"><?= $shippedCount ?></p>
        <p class="admin-stat-label">Отправлено</p>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-info);">
        <p class="admin-stat-number"><?= $customsClearedCount ?></p>
        <p class="admin-stat-label">Таможня пройдена</p>
    </div>
</div>

<!-- Расширенные фильтры -->
<div class="admin-card">
    <h2 class="admin-card-title"><i class="bi bi-funnel"></i> Фильтры</h2>
    <form method="get" id="filterForm" style="margin-top:1.25rem;" onsubmit="saveFilters()">
        <input type="hidden" name="_filter_save" value="1">
        <div class="filter-section">
            <!-- Строка 1: текстовые фильтры -->
            <div class="filter-row">
                <div class="form-group">
                    <label>Поиск</label>
                    <input type="text" name="search" class="form-control" placeholder="№ заказа, клиент, телефон…" value="<?= Html::encode($filterSearch) ?>">
                </div>
                <div class="form-group">
                    <label>Логист</label>
                    <select name="logist" class="form-control">
                        <option value="">Все</option>
                        <?php foreach ($logists as $l): ?>
                            <option value="<?= $l->id ?>" <?= $filterLogist == $l->id ? 'selected' : '' ?>><?= Html::encode($l->username) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Обработка</label>
                    <select name="processed" class="form-control">
                        <option value="">Все</option>
                        <option value="1" <?= $filterProcessed === '1' ? 'selected' : '' ?>>Обработано</option>
                        <option value="0" <?= $filterProcessed === '0' ? 'selected' : '' ?>>Не обработано</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Метод доставки</label>
                    <select name="delivery_method" class="form-control">
                        <option value="">Все</option>
                        <option value="europochta" <?= $filterDelivery === 'europochta' ? 'selected' : '' ?>>Европочта</option>
                        <option value="belpochta"  <?= $filterDelivery === 'belpochta'  ? 'selected' : '' ?>>Белпочта</option>
                        <option value="cdek"       <?= $filterDelivery === 'cdek'       ? 'selected' : '' ?>>СДЭК</option>
                        <option value="courier"    <?= $filterDelivery === 'courier'    ? 'selected' : '' ?>>Курьер</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Метод оплаты</label>
                    <select name="payment_method" class="form-control">
                        <option value="">Все</option>
                        <option value="cash"        <?= $filterPayment === 'cash'        ? 'selected' : '' ?>>Наличные</option>
                        <option value="card"        <?= $filterPayment === 'card'        ? 'selected' : '' ?>>Карта</option>
                        <option value="transfer"    <?= $filterPayment === 'transfer'    ? 'selected' : '' ?>>Перевод</option>
                        <option value="erip"        <?= $filterPayment === 'erip'        ? 'selected' : '' ?>>ЕРИП</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Трек-номер</label>
                    <select name="has_track" class="form-control">
                        <option value="">Любой</option>
                        <option value="1" <?= $filterHasTrack === '1' ? 'selected' : '' ?>>Есть</option>
                        <option value="0" <?= $filterHasTrack === '0' ? 'selected' : '' ?>>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Дата от</label>
                    <input type="date" name="date_from" class="form-control" value="<?= Html::encode($filterDateFrom) ?>">
                </div>
                <div class="form-group">
                    <label>Дата до</label>
                    <input type="date" name="date_to" class="form-control" value="<?= Html::encode($filterDateTo) ?>">
                </div>
            </div>

            <!-- Фильтр по статусам (чекбоксы) -->
            <div>
                <label style="font-weight:600;font-size:.8125rem;color:var(--admin-text-secondary,#6b7280);display:block;margin-bottom:.4rem;">Статусы</label>
                <div class="filter-checkboxes">
                    <?php foreach ($statuses as $sKey => $sLabel): ?>
                        <label>
                            <input type="checkbox" name="statuses[]" value="<?= $sKey ?>"
                                <?= in_array($sKey, (array)$filterStatuses) ? 'checked' : '' ?>>
                            <span><?= Html::encode($sLabel) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <button type="submit" class="admin-btn admin-btn-primary"><i class="bi bi-search"></i> Применить</button>
                <a href="<?= Url::to(['/admin/order']) ?>" class="admin-btn admin-btn-secondary"><i class="bi bi-x-circle"></i> Сбросить</a>
            </div>
        </div>
    </form>
</div>

<!-- Bulk Actions Toolbar -->
<div class="bulk-toolbar" id="bulkToolbar">
    <label><i class="bi bi-check2-square"></i> Выбрано: <span id="selectedCount">0</span></label>
    <select id="bulkActionSelect" class="form-control" style="width:auto;">
        <option value="">— Действие —</option>
        <option value="change_status">Сменить статус</option>
        <?php if (!$user->isLogist()): ?>
        <option value="assign_logist">Назначить логиста</option>
        <?php endif; ?>
        <option value="export_csv">Выгрузить Excel</option>
    </select>
    <!-- Extra: статус -->
    <select id="bulkStatusExtra" class="form-control" style="width:auto;display:none;">
        <option value="">— Выберите статус —</option>
        <?php foreach ($statuses as $sk => $sl): ?>
            <option value="<?= $sk ?>"><?= Html::encode($sl) ?></option>
        <?php endforeach; ?>
    </select>
    <!-- Extra: логист -->
    <select id="bulkLogistExtra" class="form-control" style="width:auto;display:none;">
        <option value="">— Выберите логиста —</option>
        <?php foreach ($logists as $l): ?>
            <option value="<?= $l->id ?>"><?= Html::encode($l->username) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="admin-btn admin-btn-primary" id="bulkApplyBtn" onclick="applyBulkAction()">
        <i class="bi bi-check-circle"></i> Применить к выбранным
    </button>
    <button class="admin-btn admin-btn-secondary" onclick="clearSelection()">Снять выделение</button>
</div>

<!-- Переключатель вид -->
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
        <h2 class="admin-card-title" style="margin:0;"><i class="bi bi-cart3"></i> Список заказов</h2>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <div class="view-toggle">
                <button class="view-toggle-btn" id="btnTable" onclick="switchView('table')">
                    <i class="bi bi-table"></i> Таблица
                </button>
                <button class="view-toggle-btn" id="btnKanban" onclick="switchView('kanban')">
                    <i class="bi bi-kanban"></i> Канбан
                </button>
            </div>
            <select id="pageSizeSelect" onchange="changePageSize(this.value)" class="form-control" style="width:auto;">
                <?php foreach ($pageSizeOptions as $size): ?>
                    <option value="<?= $size ?>" <?= $pageSize === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- TABLE VIEW -->
    <div id="tableView">
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Клиент</th>
                        <th>Телефон</th>
                        <th>Товар</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Логист</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $daysSince = (int)floor((time() - $order->created_at) / 86400);
                            $firstItem = $order->orderItems[0] ?? null;
                            ?>
                            <tr>
                                <td><input type="checkbox" class="order-checkbox" value="<?= $order->id ?>"></td>
                                <td style="font-weight:600;"><?= Html::encode($order->order_number ?: $order->id) ?></td>
                                <td><?= Html::encode($order->client_name) ?></td>
                                <td><?= Html::encode($order->client_phone) ?></td>
                                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= $firstItem ? Html::encode($firstItem->product_name) : '—' ?>
                                </td>
                                <td style="font-weight:600;"><?= number_format($order->total_amount, 2) ?> BYN</td>
                                <td>
                                    <span class="admin-badge admin-badge-<?= $order->status === 'completed' ? 'success' : ($order->status === 'paid' ? 'warning' : 'info') ?>">
                                        <?= Html::encode($statuses[$order->status] ?? $order->status) ?>
                                    </span>
                                </td>
                                <td><?= Html::encode($logistMap[$order->assigned_logist] ?? '—') ?></td>
                                <td>
                                    <?= Yii::$app->formatter->asDatetime($order->created_at) ?>
                                    <?php if ($daysSince > 0): ?>
                                        <small style="color:var(--admin-text-secondary,#6b7280);display:block;"><?= $daysSince ?> дн. назад</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= Url::to(['view', 'id' => $order->id]) ?>" class="admin-btn admin-btn-secondary" style="padding:.25rem .5rem;font-size:.875rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center;padding:2rem;color:var(--admin-text-secondary,#6b7280);">
                                Нет заказов по текущим фильтрам
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination->pageCount > 1): ?>
            <div style="margin-top:1.25rem;display:flex;justify-content:center;">
                <?= \yii\widgets\LinkPager::widget([
                    'pagination' => $pagination,
                    'options'    => ['class' => 'pagination'],
                    'linkOptions'=> ['class' => 'page-link'],
                ]) ?>
            </div>
        <?php endif; ?>
    </div><!-- /tableView -->

    <!-- KANBAN VIEW -->
    <div id="kanbanView" style="display:none;">
        <div class="kanban-board" id="kanbanBoard">
            <?php foreach ($kanbanColumns as $colKey => $colLabel): ?>
                <?php $colOrders = $kanbanGroups[$colKey] ?? []; ?>
                <div class="kanban-col" data-status="<?= $colKey ?>">
                    <div class="kanban-col-header">
                        <span><?= Html::encode($colLabel) ?></span>
                        <span class="kanban-col-badge"><?= count($colOrders) ?></span>
                    </div>
                    <div class="kanban-cards" id="col-<?= $colKey ?>"
                         ondragover="onDragOver(event)"
                         ondragleave="onDragLeave(event)"
                         ondrop="onDrop(event, '<?= $colKey ?>')">
                        <?php foreach ($colOrders as $ord): ?>
                            <?php
                            $daysSince = (int)floor((time() - $ord->created_at) / 86400);
                            $firstItem = $ord->orderItems[0] ?? null;
                            ?>
                            <div class="kanban-card"
                                 draggable="true"
                                 data-id="<?= $ord->id ?>"
                                 data-status="<?= $ord->status ?>"
                                 ondragstart="onDragStart(event)"
                                 ondragend="onDragEnd(event)">
                                <div class="kc-num"><?= Html::encode($ord->order_number ?: '#'.$ord->id) ?></div>
                                <div class="kc-client"><?= Html::encode($ord->client_name) ?></div>
                                <?php if ($firstItem): ?>
                                    <div class="kc-product" title="<?= Html::encode($firstItem->product_name) ?>"><?= Html::encode($firstItem->product_name) ?></div>
                                <?php endif; ?>
                                <div class="kc-meta">
                                    <span class="kc-amount"><?= number_format($ord->total_amount, 2) ?> Br</span>
                                    <span><?= $daysSince > 0 ? $daysSince.' дн.' : 'сегодня' ?></span>
                                </div>
                                <div style="margin-top:.35rem;">
                                    <a href="<?= Url::to(['view', 'id' => $ord->id]) ?>" style="font-size:.75rem;color:var(--admin-primary,#2563eb);">
                                        <i class="bi bi-box-arrow-up-right"></i> Открыть
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="color:var(--admin-text-secondary,#6b7280);font-size:.8125rem;margin-top:.5rem;">
            <i class="bi bi-info-circle"></i> Перетащите карточку в другую колонку для смены статуса. Показаны заказы текущей страницы.
        </p>
    </div><!-- /kanbanView -->
</div>

<script>
/* ========== VIEW TOGGLE ========== */
function switchView(v) {
    localStorage.setItem('orderView', v);
    document.getElementById('tableView').style.display  = v === 'table'  ? '' : 'none';
    document.getElementById('kanbanView').style.display = v === 'kanban' ? '' : 'none';
    document.getElementById('btnTable').classList.toggle('active',  v === 'table');
    document.getElementById('btnKanban').classList.toggle('active', v === 'kanban');
}
(function(){
    const saved = localStorage.getItem('orderView') || 'table';
    switchView(saved);
})();

/* ========== PAGE SIZE ========== */
function changePageSize(value) {
    const params = new URLSearchParams(window.location.search);
    params.set('per-page', value);
    params.delete('page');
    window.location.href = window.location.pathname + '?' + params.toString();
}

/* ========== SELECT ALL ========== */
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkToolbar();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('order-checkbox')) {
        updateBulkToolbar();
    }
});

function updateBulkToolbar() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const toolbar = document.getElementById('bulkToolbar');
    document.getElementById('selectedCount').textContent = checked.length;
    toolbar.classList.toggle('visible', checked.length > 0);
}

function clearSelection() {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkToolbar();
}

/* ========== BULK ACTION EXTRAS ========== */
document.getElementById('bulkActionSelect')?.addEventListener('change', function() {
    document.getElementById('bulkStatusExtra').style.display = this.value === 'change_status'  ? '' : 'none';
    document.getElementById('bulkLogistExtra').style.display = this.value === 'assign_logist'  ? '' : 'none';
});

function applyBulkAction() {
    const action = document.getElementById('bulkActionSelect').value;
    if (!action) { alert('Выберите действие'); return; }

    const ids = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) { alert('Выберите заказы'); return; }

    let extra = '';
    if (action === 'change_status') {
        extra = document.getElementById('bulkStatusExtra').value;
        if (!extra) { alert('Выберите статус'); return; }
    }
    if (action === 'assign_logist') {
        extra = document.getElementById('bulkLogistExtra').value;
    }
    if (action === 'export_csv') {
        window.location.href = '/admin/order/export-csv?ids=' + ids.join(',');
        return;
    }

    fetch('/admin/order/bulk-action', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: new URLSearchParams({ action, ids: JSON.stringify(ids), extra })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Выполнено');
            location.reload();
        } else {
            alert(data.message || 'Ошибка');
        }
    })
    .catch(() => alert('Ошибка запроса'));
}

/* ========== KANBAN DRAG & DROP ========== */
let draggedCard = null;

function onDragStart(e) {
    draggedCard = e.currentTarget;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', draggedCard.dataset.id);
    draggedCard.style.opacity = '.4';
}

function onDragEnd(e) {
    if (draggedCard) draggedCard.style.opacity = '';
    draggedCard = null;
    document.querySelectorAll('.kanban-col').forEach(c => c.classList.remove('drag-target'));
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const col = e.currentTarget.closest('.kanban-col');
    if (col) col.classList.add('drag-target');
}

function onDragLeave(e) {
    const col = e.currentTarget.closest('.kanban-col');
    if (col) col.classList.remove('drag-target');
}

function onDrop(e, newStatus) {
    e.preventDefault();
    const col = e.currentTarget.closest('.kanban-col');
    if (col) col.classList.remove('drag-target');

    if (!draggedCard) return;
    const orderId = draggedCard.dataset.id;
    const oldStatus = draggedCard.dataset.status;
    if (oldStatus === newStatus) return;

    // Optimistic UI
    const targetCards = document.getElementById('col-' + newStatus);
    if (targetCards) {
        draggedCard.dataset.status = newStatus;
        targetCards.appendChild(draggedCard);
        // Update badge counts
        updateColBadge(oldStatus);
        updateColBadge(newStatus);
    }

    fetch('/admin/order/update-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: new URLSearchParams({ id: orderId, status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert('Ошибка смены статуса: ' + (data.message || ''));
            // Rollback
            const oldCards = document.getElementById('col-' + oldStatus);
            if (oldCards && draggedCard) {
                draggedCard.dataset.status = oldStatus;
                oldCards.appendChild(draggedCard);
                updateColBadge(oldStatus);
                updateColBadge(newStatus);
            }
        }
    })
    .catch(() => alert('Ошибка соединения'));
}

function updateColBadge(status) {
    const col   = document.querySelector('.kanban-col[data-status="' + status + '"]');
    if (!col) return;
    const badge = col.querySelector('.kanban-col-badge');
    const count = col.querySelectorAll('.kanban-card').length;
    if (badge) badge.textContent = count;
}

/* ========== SAVE FILTERS ========== */
function saveFilters() {
    // Form submit already sends _filter_save=1 — session save handled in PHP
}
</script>
