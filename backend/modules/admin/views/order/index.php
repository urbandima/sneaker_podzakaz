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
use app\backend\modules\admin\assets\AdminAsset;

AdminAsset::register($this);

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
    'created' => 'Заказ создан и ожидает подтверждения',
    'confirmed' => 'Подтвержден менеджером',
    'paid' => 'Оплата получена и проверена',
    'ordered' => 'Товар заказан у поставщика',
    'shipped' => 'Отправлен в Беларусь',
    'delivered' => 'Готов к выдаче клиенту',
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const filtersAccordionToggle = document.getElementById('filtersAccordionToggle');
    const filtersAccordionContent = document.getElementById('filtersAccordionContent');
    const filtersAccordionIcon = document.getElementById('filtersAccordionIcon');
    const filtersPanel = document.getElementById('filtersPanel');
    const filtersToggleButton = document.getElementById('filtersToggleButton');
    const filtersFlyout = document.getElementById('filtersFlyout');

    filtersAccordionToggle?.addEventListener('click', () => {
        const isOpen = filtersPanel?.classList.toggle('is-open');
        if (!isOpen) {
            filtersAccordionContent?.classList.add('collapsed');
            filtersAccordionToggle.setAttribute('aria-expanded', 'false');
            if (filtersAccordionIcon) {
                filtersAccordionIcon.classList.remove('bi-chevron-up');
                filtersAccordionIcon.classList.add('bi-chevron-down');
            }
        } else {
            filtersAccordionContent?.classList.remove('collapsed');
            filtersAccordionToggle.setAttribute('aria-expanded', 'true');
            if (filtersAccordionIcon) {
                filtersAccordionIcon.classList.remove('bi-chevron-down');
                filtersAccordionIcon.classList.add('bi-chevron-up');
            }
        }
    });

    function setFiltersFlyoutState(open) {
        if (!filtersFlyout || !filtersToggleButton) {
            return;
        }
        filtersFlyout.classList.toggle('is-open', open);
        filtersToggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    filtersToggleButton?.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = filtersFlyout?.classList.contains('is-open');
        setFiltersFlyoutState(!isOpen);
    });

    document.addEventListener('click', (event) => {
        if (
            filtersFlyout?.classList.contains('is-open') &&
            !event.target.closest('#filtersFlyout') &&
            !event.target.closest('#filtersToggleButton')
        ) {
            setFiltersFlyoutState(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && filtersFlyout?.classList.contains('is-open')) {
            setFiltersFlyoutState(false);
        }
    });

    // Column toggles
    initColumnToggles();

    // Select all behaviour
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('.order-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkActions();
    });

    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checked = document.querySelectorAll('.order-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = checked;
        document.getElementById('bulkActions').classList.toggle('visible', checked > 0);
    }

    // Inline editing
    document.querySelectorAll('.editable-cell').forEach(cell => {
        cell.addEventListener('dblclick', function () {
            const field = this.dataset.field;
            const orderId = this.dataset.orderId;
            const valueSpan = this.querySelector('.cell-value');
            const currentValue = valueSpan.textContent.trim();

            if (this.querySelector('input')) {
                return;
            }

            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue === '—' ? '' : currentValue;
            input.style.width = '100%';

            valueSpan.style.display = 'none';
            this.querySelector('.edit-icon').style.display = 'none';
            this.appendChild(input);
            input.focus();
            input.select();

            const indicator = this.querySelector('.inline-cell-indicator');
            const saveValue = () => {
                const newValue = input.value.trim();
                updateOrderField(orderId, field, newValue, valueSpan, input, indicator, cell);
            };

            input.addEventListener('blur', saveValue);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') saveValue();
                if (e.key === 'Escape') {
                    input.remove();
                    valueSpan.style.display = '';
                    cell.querySelector('.edit-icon').style.display = '';
                }
            });
        });
    });

    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function () {
            const indicator = this.closest('td')?.querySelector('.inline-cell-indicator');
            updateOrderField(this.dataset.orderId, 'status', this.value, null, null, indicator, this.closest('td'));
        });
    });

    document.querySelectorAll('.status-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            const value = this.checked ? 1 : 0;
            const indicator = this.closest('td')?.querySelector('.inline-cell-indicator');
            updateOrderField(this.dataset.orderId, this.dataset.field, value, null, null, indicator, this.closest('td'));
        });
    });

    // Search debounce
    let searchTimeout;
    document.getElementById('searchInput')?.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 450);
    });

    document.getElementById('logistFilter')?.addEventListener('change', applyFilters);
    document.getElementById('processedFilter')?.addEventListener('change', applyFilters);
    document.getElementById('dateFrom')?.addEventListener('change', applyFilters);
    document.getElementById('dateTo')?.addEventListener('change', applyFilters);

    function setIndicator(indicator, state, message = '') {
        if (!indicator) return;
        indicator.innerHTML = '';
        indicator.classList.remove('text-success', 'text-danger');
        if (state === 'saving') {
            const spinner = document.createElement('span');
            spinner.className = 'spinner';
            indicator.appendChild(spinner);
        } else if (state === 'saved') {
            indicator.textContent = '✓';
            indicator.classList.add('text-success');
        } else if (state === 'error') {
            indicator.textContent = '!';
            indicator.classList.add('text-danger');
            if (message) {
                indicator.title = message;
            }
        }
    }

    function updateOrderField(orderId, field, value, valueSpan = null, input = null, indicator = null, cell = null) {
        const formData = new FormData();
        formData.append('field', field);
        formData.append('value', value);
        formData.append('_csrf', csrfToken);

        if (cell) {
            cell.classList.remove('cell-saved', 'cell-error');
            cell.classList.add('cell-saving');
        }
        setIndicator(indicator, 'saving');

        fetch('/admin/order/update-field?id=' + orderId, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (valueSpan) {
                        valueSpan.textContent = value || '—';
                        valueSpan.style.display = '';
                    }
                    if (input) {
                        input.remove();
                        const editIcon = input.parentElement?.querySelector('.edit-icon');
                        if (editIcon) {
                            editIcon.style.display = '';
                        }
                    }
                    if (cell) {
                        cell.classList.remove('cell-saving');
                        cell.classList.add('cell-saved');
                        setTimeout(() => cell.classList.remove('cell-saved'), 1200);
                    }
                    setIndicator(indicator, 'saved');
                    showNotification('Сохранено', 'success');
                } else {
                    if (cell) {
                        cell.classList.remove('cell-saving');
                        cell.classList.add('cell-error');
                    }
                    setIndicator(indicator, 'error', data.message || 'Ошибка');
                    showNotification(data.message || 'Ошибка сохранения', 'error');
                }
            })
            .catch(() => {
                if (cell) {
                    cell.classList.remove('cell-saving');
                    cell.classList.add('cell-error');
                }
                setIndicator(indicator, 'error', 'Ошибка сети');
                showNotification('Ошибка сети', 'error');
            });
    }

    // Resizable headers
    const resizableTable = new ResizableTable('ordersTable');
    resizableTable.restoreSizes();
});

function initColumnToggles() {
    const columnCheckboxes = document.querySelectorAll('#columnMenu input[type="checkbox"]');
    columnCheckboxes.forEach(cb => {
        const saved = localStorage.getItem('orders-column-' + cb.dataset.column);
        if (saved === '0') {
            cb.checked = false;
        }
        toggleColumnVisibility(cb.dataset.column, cb.checked);
        cb.addEventListener('change', function () {
            toggleColumnVisibility(this.dataset.column, this.checked);
            localStorage.setItem('orders-column-' + this.dataset.column, this.checked ? '1' : '0');
            updateColumnIndicator();
        });
    });
    updateColumnIndicator();
}

function toggleColumnVisibility(columnKey, isVisible) {
    document.querySelectorAll(`[data-column="${columnKey}"]`).forEach(cell => {
        if (isVisible) {
            cell.classList.remove('column-hidden');
        } else {
            cell.classList.add('column-hidden');
        }
    });
}

function updateColumnIndicator() {
    const indicator = document.getElementById('columnToggleIndicator');
    if (!indicator) return;
    const total = document.querySelectorAll('#columnMenu input[type="checkbox"]').length;
    const visible = document.querySelectorAll('#columnMenu input[type="checkbox"]:checked').length;
    const hidden = total - visible;
    indicator.textContent = hidden > 0 ? `(${hidden} скрыто)` : '';
}

function navigateWithParams(updates = {}) {
    const params = new URLSearchParams(window.location.search);
    Object.entries(updates).forEach(([key, value]) => {
        if (value === null || value === '' || value === undefined) {
            params.delete(key);
        } else {
            params.set(key, value);
        }
    });
    params.delete('page');
    const basePath = window.location.pathname.includes('/admin/order') ? window.location.pathname : '/admin/order/index';
    const query = params.toString();
    window.location.href = query ? `${basePath}?${query}` : basePath;
}

function changePageSize(value) {
    navigateWithParams({ 'per-page': value });
}

function applyFilters() {
    const search = document.getElementById('searchInput')?.value.trim() || null;
    const logist = document.getElementById('logistFilter')?.value || null;
    const processed = document.getElementById('processedFilter')?.value ?? null;
    const dateFrom = document.getElementById('dateFrom')?.value || null;
    const dateTo = document.getElementById('dateTo')?.value || null;

    navigateWithParams({
        search,
        logist,
        processed,
        'date_from': dateFrom,
        'date_to': dateTo
    });
}

function resetFilters() {
    document.getElementById('searchInput')?.value = '';
    document.getElementById('logistFilter')?.value = '';
    document.getElementById('processedFilter')?.value = '';
    document.getElementById('dateFrom')?.value = '';
    document.getElementById('dateTo')?.value = '';

    navigateWithParams({
        search: null,
        logist: null,
        processed: null,
        'date_from': null,
        'date_to': null,
        status: null
    });
}

function toggleExportMenu() {
    document.getElementById('exportDropdown')?.classList.toggle('show');
}

function toggleColumnMenu() {
    document.getElementById('columnMenu')?.classList.toggle('show');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.export-menu')) {
        document.getElementById('exportDropdown')?.classList.remove('show');
    }
    if (!e.target.closest('.column-toggle')) {
        document.getElementById('columnMenu')?.classList.remove('show');
    }
});

function showNotification(message, type) {
    if (window.NotificationManager) {
        NotificationManager[type](message);
    }
}

function setQuickRange(range) {
    const now = new Date();
    let from = new Date(now);

    if (range === 'week') {
        from.setDate(now.getDate() - 6);
    } else if (range === 'month') {
        from.setDate(now.getDate() - 29);
    }

    document.getElementById('dateFrom').value = formatDate(from);
    document.getElementById('dateTo').value = formatDate(now);
    applyFilters();
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Existing bulk + modal logic (unchanged except formatting)
function bulkUpdateStatus() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        showNotification('Выберите заказы', 'warning');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

    const statuses = {
        'created': 'Новый',
        'confirmed': 'Подтвержден',
        'paid': 'Оплачен',
        'ordered': 'Заказан',
        'shipped': 'Отправлен',
        'delivered': 'Доставлен',
        'canceled': 'Отменен'
    };

    let optionsHtml = '';
    for (const [key, label] of Object.entries(statuses)) {
        optionsHtml += `<option value="${key}">${label}</option>`;
    }

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Изменить статус (${selected.length} заказов)</h5>
                    <button type="button" class="btn-close" onclick="closeBulkModal()"></button>
                </div>
                <div class="modal-body">
                    <select class="form-select" id="bulkStatusSelect">
                        ${optionsHtml}
                    </select>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="bulkAddComment">
                        <label class="form-check-label" for="bulkAddComment">
                            Добавить комментарий
                        </label>
                    </div>
                    <textarea class="form-control mt-2" id="bulkComment" rows="3" placeholder="Комментарий к изменению статуса..." style="display:none;"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="confirmBulkStatusUpdate()">Применить</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.getElementById('bulkAddComment').addEventListener('change', function () {
        document.getElementById('bulkComment').style.display = this.checked ? 'block' : 'none';
    });
}

function confirmBulkStatusUpdate() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    const status = document.getElementById('bulkStatusSelect').value;
    const addComment = document.getElementById('bulkAddComment').checked;
    const comment = addComment ? document.getElementById('bulkComment').value : '';

    const formData = new FormData();
    formData.append('ids', JSON.stringify(selected));
    formData.append('status', status);
    if (comment) formData.append('comment', comment);
    formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content);

    fetch('/admin/order/bulk-update-status', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            closeBulkModal();
            if (data.success) {
                showNotification(`Статус обновлен для ${data.updated} заказов`, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showNotification(data.message || 'Ошибка', 'error');
            }
        })
        .catch(() => {
            closeBulkModal();
            showNotification('Ошибка сети', 'error');
        });
}

function bulkAssignLogist() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        showNotification('Выберите заказы', 'warning');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Назначить логиста (${selected.length} заказов)</h5>
                    <button type="button" class="btn-close" onclick="closeBulkModal()"></button>
                </div>
                <div class="modal-body">
                    <select class="form-select" id="bulkLogistSelect">
                        <option value="">Не назначен</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="confirmBulkLogistAssign()">Назначить</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    fetch('/admin/user/logists')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('bulkLogistSelect');
            data.forEach(logist => {
                select.innerHTML += `<option value="${logist.id}">${logist.username}</option>`;
            });
        });
}

function confirmBulkLogistAssign() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    const logistId = document.getElementById('bulkLogistSelect').value;

    const formData = new FormData();
    formData.append('ids', JSON.stringify(selected));
    formData.append('logist_id', logistId);
    formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content);

    fetch('/admin/order/bulk-assign-logist', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            closeBulkModal();
            if (data.success) {
                showNotification(`Логист назначен для ${data.updated} заказов`, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showNotification(data.message || 'Ошибка', 'error');
            }
        })
        .catch(() => {
            closeBulkModal();
            showNotification('Ошибка сети', 'error');
        });
}

function bulkExport() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        showNotification('Выберите заказы', 'warning');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Экспорт (${selected.length} заказов)</h5>
                    <button type="button" class="btn-close" onclick="closeBulkModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportFormat" id="exportExcel" value="xlsx" checked>
                        <label class="form-check-label" for="exportExcel">
                            <i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportFormat" id="exportCsv" value="csv">
                        <label class="form-check-label" for="exportCsv">
                            <i class="bi bi-file-earmark-text"></i> CSV
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="confirmBulkExport()">Экспортировать</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

function confirmBulkExport() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    const format = document.querySelector('input[name="exportFormat"]:checked').value;
    closeBulkModal();
    window.location.href = `/admin/order/export?ids=${selected.join(',')}&format=${format}`;
}

function closeBulkModal() {
    document.querySelector('.modal.show')?.remove();
}

function bulkMarkProcessed() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) {
        showNotification('Выберите заказы', 'warning');
        return;
    }
    if (confirm(`Отметить ${selected.length} заказов как обработанные?`)) {
        bulkUpdateField('is_processed', 1, 'отмечены как обработанные');
    }
}

function bulkMarkShipped() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) {
        showNotification('Выберите заказы', 'warning');
        return;
    }
    if (confirm(`Отметить ${selected.length} заказов как отправленные?`)) {
        bulkUpdateField('is_shipped', 1, 'отмечены как отправленные');
    }
}

function bulkUpdateField(field, value, actionText) {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) return;

    const formData = new FormData();
    formData.append('ids', JSON.stringify(selected));
    formData.append('field', field);
    formData.append('value', value);
    formData.append('_csrf', document.querySelector('meta[name="csrf-token"]')?.content);

    fetch('/admin/order/bulk-update-field', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Заказы ${actionText}`, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showNotification(data.message || 'Ошибка', 'error');
            }
        })
        .catch(() => showNotification('Ошибка сети', 'error'));
}

class ResizableTable {
    constructor(tableId) {
        this.table = document.getElementById(tableId);
        if (!this.table) return;

        this.headers = this.table.querySelectorAll('th.resizable');
        this.currentColumn = null;
        this.startX = 0;
        this.startWidth = 0;

        this.init();
    }

    init() {
        this.headers.forEach(header => {
            const handle = document.createElement('div');
            handle.className = 'resize-handle';
            header.appendChild(handle);
            handle.addEventListener('mousedown', (e) => this.startResize(e, header));
        });

        document.addEventListener('mousemove', (e) => this.resize(e));
        document.addEventListener('mouseup', () => this.stopResize());
    }

    startResize(event, header) {
        this.currentColumn = header;
        this.startX = event.pageX;
        this.startWidth = header.offsetWidth;
        document.body.style.cursor = 'col-resize';
        event.preventDefault();
    }

    resize(event) {
        if (!this.currentColumn) return;
        const diff = event.pageX - this.startX;
        const newWidth = Math.max(60, this.startWidth + diff);
        this.currentColumn.style.width = `${newWidth}px`;
        const columnName = this.currentColumn.dataset.column;
        if (columnName) {
            localStorage.setItem(`column-width-${columnName}`, newWidth);
        }
    }

    stopResize() {
        this.currentColumn = null;
        document.body.style.cursor = 'default';
    }

    restoreSizes() {
        this.headers.forEach(header => {
            const columnName = header.dataset.column;
            if (!columnName) return;
            const savedWidth = localStorage.getItem(`column-width-${columnName}`);
            if (savedWidth) {
                header.style.width = `${savedWidth}px`;
            }
        });
    }
}
</script>
