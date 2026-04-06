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



