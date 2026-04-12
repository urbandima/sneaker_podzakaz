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
/** @var \app\models\User[] $logists */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Управление заказами';

$user = Yii::$app->user->identity;
$orders = $dataProvider->getModels();
$pagination = $dataProvider->pagination;
$statuses = Yii::$app->settings->getStatuses();
$formatter = Yii::$app->formatter;
$currentPage = $pagination->page;
$totalPages = $pagination->pageCount;
$hasMorePages = ($currentPage + 1) < $totalPages;
$nextPageIndex = $hasMorePages ? $currentPage + 1 : null;
$pageParam = $pagination->pageParam;
$requestParams = Yii::$app->request->get();

$logistMap = [];
foreach ($logists as $logist) {
    $logistMap[$logist->id] = $logist->username;
}

$statusDescriptions = [
    'new' => 'Заказ поступил и ожидает оплаты',
    'paid' => 'Клиент предоставил чек оплаты',
    'confirmed_and_paid' => 'Паспортные данные переданы',
    'ordered' => 'Товар заказан у поставщика',
    'awaiting_warehouse' => 'Ожидается на международном складе',
    'international_delivery' => 'В международной доставке',
    'at_warehouse' => 'Заказ на складе в Беларуси',
    'local_delivery' => 'Доставка внутри Беларуси',
    'delivered' => 'Заказ выдан клиенту',
    'canceled' => 'Заказ отменен',
];

$activeFilterCount = 0;
$activeFilters = [];

if (!empty($filterSearch)) {
    $activeFilterCount++;
    $activeFilters[] = ['label' => 'Поиск', 'value' => $filterSearch];
}
if (!empty($filterLogist)) {
    $activeFilterCount++;
    $activeFilters[] = ['label' => 'Логист', 'value' => $logistMap[$filterLogist] ?? ('ID ' . $filterLogist)];
}
if ($filterProcessed !== null && $filterProcessed !== '') {
    $activeFilterCount++;
    $activeFilters[] = ['label' => 'Обработка', 'value' => $filterProcessed === '1' ? 'Обработано' : 'Не обработано'];
}
if (!empty($filterDateFrom) || !empty($filterDateTo)) {
    $activeFilterCount++;
    $activeFilters[] = [
        'label' => 'Диапазон',
        'value' => trim(($filterDateFrom ?: '…') . ' — ' . ($filterDateTo ?: '…'))
    ];
}

$this->params['breadcrumbs'][] = ['label' => 'Заказы', 'url' => ['/admin/order/index']];

?>

<?php
$showingFrom = $totalCount ? ($pagination->page * $pagination->pageSize) + 1 : 0;
$showingTo = $totalCount ? $showingFrom + count($orders) - 1 : 0;
$inWorkCount = max($totalCount - $processedCount, 0);
$filtersExpanded = $activeFilterCount > 0;
?>

<div class="orders-admin" id="ordersPage" data-status="<?= Html::encode($filterStatus) ?>" data-per-page="<?= (int)$pageSize ?>">
    <div class="orders-shell">
        <section class="orders-hero orders-surface">
            <div class="hero-header-row">
                <div class="hero-main">
                    <p class="hero-eyebrow">CRM · Live pipeline</p>
                    <h1><?= Html::encode($this->title) ?></h1>
                </div>
                <div class="hero-actions">
                    <div class="hero-action-buttons">
                        <label class="page-size-control">
                            <span>Показывать</span>
                            <select id="pageSizeSelect" onchange="changePageSize(this.value)">
                                <?php foreach ($pageSizeOptions as $size): ?>
                                    <option value="<?= $size ?>" <?= $pageSize === $size ? 'selected' : '' ?>><?= $size ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <?php if (!$user->isLogist()): ?>
                            <a href="<?= Url::to(['/admin/order/create']) ?>" class="btn-action btn-primary-action">
                                <i class="bi bi-plus-lg"></i> Новый заказ
                            </a>
                        <?php endif; ?>
                        <button type="button"
                                class="btn-action btn-ghost filters-toggle-button <?= $activeFilterCount ? 'is-active' : '' ?>"
                                id="filtersToggleButton"
                                aria-haspopup="dialog"
                                aria-expanded="<?= $filtersExpanded ? 'true' : 'false' ?>"
                                aria-controls="filtersFlyout"
                                data-has-active="<?= $activeFilterCount ? '1' : '0' ?>">
                            <i class="bi bi-funnel"></i>
                            <span>Фильтр</span>
                            <?php if ($activeFilterCount): ?>
                                <span class="filters-badge"><?= $activeFilterCount ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                    <div class="filters-flyout <?= $filtersExpanded ? 'is-open' : '' ?>" id="filtersFlyout" role="dialog" aria-label="Фильтры заказов">
                        <div class="filters-card hero-filters-card filters-accordion orders-surface <?= $activeFilterCount ? 'filters-card--pulse' : '' ?> <?= $filtersExpanded ? 'is-open' : '' ?>" id="filtersPanel">
                            <div class="filters-header">
                                <div class="filters-title-group">
                                    <h2>Быстрые фильтры</h2>
                                    <span><?= $activeFilterCount ? "Активно {$activeFilterCount} фильтр(ов)" : 'Выберите условия для точной выдачи' ?></span>
                                </div>
                                <div class="filters-header-actions">
                                    <button type="button" class="btn-action btn-ghost" onclick="resetFilters()">
                                        <i class="bi bi-arrow-counterclockwise"></i> Сбросить
                                    </button>
                                    <button type="button"
                                            class="btn-action btn-ghost accordion-toggle"
                                            id="filtersAccordionToggle"
                                            aria-expanded="<?= $filtersExpanded ? 'true' : 'false' ?>">
                                        <span><?= $filtersExpanded ? 'Свернуть' : 'Раскрыть' ?></span>
                                        <i class="bi <?= $filtersExpanded ? 'bi-chevron-up' : 'bi-chevron-down' ?>" id="filtersAccordionIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <?php if ($activeFilterCount > 0): ?>
                                <div class="filters-summary">
                                    <span class="filters-summary__label">Активные фильтры:</span>
                                    <div class="filter-chips">
                                        <?php foreach ($activeFilters as $chip): ?>
                                            <span class="filter-chip">
                                                <?= Html::encode($chip['label']) ?>: <strong><?= Html::encode($chip['value']) ?></strong>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="filters-summary filters-summary--empty">
                                    Точните выдачу при необходимости — фильтр раскрывается по клику.
                                </div>
                            <?php endif; ?>
                            <div class="filters-accordion-body <?= $filtersExpanded ? '' : 'collapsed' ?>" id="filtersAccordionContent">
                                <div class="filters-body">
                                    <div class="filter-control full">
                                        <label for="searchInput">Поиск</label>
                                        <div class="input-with-icon">
                                            <i class="bi bi-search"></i>
                                            <input type="text" id="searchInput" placeholder="№ заказа, клиент, телефон, трек..." value="<?= Html::encode($filterSearch) ?>">
                                        </div>
                                    </div>
                                    <div class="filter-control">
                                        <label for="logistFilter">Логист</label>
                                        <select id="logistFilter">
                                            <option value="">Все</option>
                                            <?php foreach ($logists as $logist): ?>
                                                <option value="<?= $logist->id ?>" <?= $filterLogist == $logist->id ? 'selected' : '' ?>>
                                                    <?= Html::encode($logist->username) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="filter-control">
                                        <label for="processedFilter">Обработка</label>
                                        <select id="processedFilter">
                                            <option value="">Все</option>
                                            <option value="1" <?= $filterProcessed === '1' ? 'selected' : '' ?>>Обработано</option>
                                            <option value="0" <?= $filterProcessed === '0' ? 'selected' : '' ?>>Не обработано</option>
                                        </select>
                                    </div>
                                    <div class="filter-control">
                                        <label for="dateFrom">Создан от</label>
                                        <input type="date" id="dateFrom" value="<?= Html::encode($filterDateFrom) ?>">
                                    </div>
                                    <div class="filter-control">
                                        <label for="dateTo">Создан до</label>
                                        <input type="date" id="dateTo" value="<?= Html::encode($filterDateTo) ?>">
                                    </div>
                                </div>
                                <div class="filters-footer">
                                    <div class="quick-range">
                                        <span>Быстрый диапазон:</span>
                                        <button type="button" onclick="setQuickRange('week')">Неделя</button>
                                        <button type="button" onclick="setQuickRange('month')">Месяц</button>
                                    </div>
                                    <div class="filters-footer-actions">
                                        <button type="button" class="btn-action btn-ghost" onclick="resetFilters()">Очистить</button>
                                        <button type="button" class="btn-action btn-primary-action" onclick="applyFilters()">Применить</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-quick-filters">
                <div class="hero-quick-filters__label">Быстрые фильтры по статусам</div>
                <div class="hero-quick-filters__pills">
                    <a href="<?= Url::to(['/admin/order/index']) ?>"
                       class="hero-status-pill <?= empty($filterStatus) ? 'is-active' : '' ?>"
                       data-status="all">
                        <span>Все</span>
                        <strong><?= $formatter->asInteger($totalCount) ?></strong>
                    </a>
                    <?php foreach ($statuses as $key => $label): ?>
                        <a href="<?= Url::to(array_merge(['/admin/order/index'], array_merge($requestParams, ['status' => $key, $pageParam => null]))) ?>"
                           class="hero-status-pill <?= $filterStatus === $key ? 'is-active' : '' ?>"
                           data-status="<?= Html::encode($key) ?>">
                            <span><?= Html::encode($label) ?></span>
                            <strong><?= $formatter->asInteger($statusCounts[$key] ?? 0) ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="orders-surface">
            <div class="status-bar">
                <a href="<?= Url::to(['/admin/order/index']) ?>" class="status-pill <?= empty($filterStatus) ? 'active' : '' ?>">
                    Все
                    <span class="count"><?= $totalCount ?></span>
                </a>
                <?php foreach ($statuses as $key => $label): ?>
                    <a href="<?= Url::to(array_merge(['/admin/order/index'], array_merge($requestParams, ['status' => $key, $pageParam => null]))) ?>"
                       class="status-pill <?= $filterStatus === $key ? 'active' : '' ?>">
                        <?= Html::encode($label) ?>
                        <span class="count"><?= $statusCounts[$key] ?? 0 ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="orders-table-card orders-surface">
            <div class="table-toolbar">
                <p class="summary">
                    <?php if ($totalCount > 0): ?>
                        Показано <?= $formatter->asInteger($showingFrom) ?>–<?= $formatter->asInteger($showingTo) ?> из <?= $formatter->asInteger($totalCount) ?> заказов
                    <?php else: ?>
                        Нет данных по текущим фильтрам
                    <?php endif; ?>
                </p>
                <div class="table-toolbar-actions">
                    <div class="column-toggle">
                        <button type="button" class="btn-action btn-ghost" onclick="toggleColumnMenu()">
                            <i class="bi bi-layout-three-columns"></i>
                            Колонки <span id="columnToggleIndicator"></span>
                        </button>
                        <div class="column-menu" id="columnMenu">
                            <label>
                                <input type="checkbox" data-column="order" checked disabled>
                                Заказ
                            </label>
                            <label>
                                <input type="checkbox" data-column="client" checked>
                                Клиент
                            </label>
                            <label>
                                <input type="checkbox" data-column="finance" checked>
                                Финансы
                            </label>
                            <label>
                                <input type="checkbox" data-column="logistics" checked>
                                Логистика
                            </label>
                            <label>
                                <input type="checkbox" data-column="status" checked>
                                Статус
                            </label>
                        </div>
                    </div>
                    <div class="export-menu">
                        <button type="button" class="btn-action btn-ghost" onclick="toggleExportMenu()">
                            <i class="bi bi-download"></i> Экспорт
                        </button>
                        <div class="export-dropdown" id="exportDropdown">
                            <a href="<?= Url::to(['/admin/order/export', 'format' => 'xlsx']) ?>">
                                <i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)
                            </a>
                            <a href="<?= Url::to(['/admin/order/export', 'format' => 'csv']) ?>">
                                <i class="bi bi-file-earmark-text"></i> CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bulk-actions" id="bulkActions">
                <span class="selected-count">Выбрано: <strong id="selectedCount">0</strong></span>
                <div class="divider"></div>
                <button type="button" class="btn-action" onclick="bulkUpdateStatus()">Сменить статус</button>
                <button type="button" class="btn-action" onclick="bulkAssignLogist()">Назначить логиста</button>
                <button type="button" class="btn-action" onclick="bulkExport()">Экспорт</button>
                <button type="button" class="btn-action" onclick="bulkMarkProcessed()">Обработано</button>
                <button type="button" class="btn-action" onclick="bulkMarkShipped()">Отправлено</button>
            </div>

            <div class="table-scroll">
                <table class="orders-table resizable-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th class="resizable" data-column="selection" scope="col">
                                <label class="checkbox-pill checkbox-pill--head">
                                    <input type="checkbox" id="selectAll">
                                    <span></span>
                                </label>
                            </th>
                            <th class="resizable" data-column="order" scope="col">Заказ</th>
                            <th class="resizable" data-column="client" scope="col">Клиент</th>
                            <th class="resizable" data-column="finance" scope="col">Финансы</th>
                            <th class="resizable" data-column="logistics" scope="col">Логистика</th>
                            <th class="resizable" data-column="status" scope="col">Статус</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?= $this->render('_table_rows', [
                            'orders' => $orders,
                            'statuses' => $statuses,
                            'formatter' => $formatter,
                            'statusDescriptions' => $statusDescriptions,
                            'logistMap' => $logistMap,
                            'user' => $user,
                        ]) ?>
                    </tbody>
                </table>
                <div class="scroll-sentinel"
                     id="scrollSentinel"
                     data-has-more="<?= $hasMorePages ? '1' : '0' ?>"
                     data-next-page="<?= $hasMorePages ? $nextPageIndex : '' ?>"
                     data-page-param="<?= Html::encode($pageParam) ?>">
                    <?= $hasMorePages ? 'Прокрутите вниз, чтобы загрузить ещё' : 'Все заказы загружены' ?>
                </div>
            </div>
        </section>

        <?php if (empty($orders)): ?>
            <div class="empty-state orders-surface">
                <h3>Нет заказов по текущим фильтрам</h3>
                <p>Измените параметры поиска или создайте новый заказ, чтобы увидеть данные.</p>
                <?php if (!$user->isLogist()): ?>
                    <a href="<?= Url::to(['/admin/order/create']) ?>" class="btn-action btn-primary-action" style="margin-top:0.75rem;">
                        <i class="bi bi-plus-circle"></i> Создать заказ
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
