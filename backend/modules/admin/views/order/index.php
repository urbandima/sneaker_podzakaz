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

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Новый заказ
        </a>
    </div>
</div>

<!-- Статистика по статусам (компактная) -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
    <div class="admin-stat-card" style="padding:12px">
        <p class="admin-stat-number" style="font-size:24px;margin-bottom:4px"><?= $totalCount ?></p>
        <p class="admin-stat-label" style="font-size:13px">Всего заказов</p>
    </div>
    <div class="admin-stat-card" style="padding:12px;border-left-color:var(--admin-success)">
        <p class="admin-stat-number" style="font-size:24px;margin-bottom:4px"><?= $processedCount ?></p>
        <p class="admin-stat-label" style="font-size:13px">Обработано</p>
    </div>
    <div class="admin-stat-card" style="padding:12px;border-left-color:var(--admin-warning)">
        <p class="admin-stat-number" style="font-size:24px;margin-bottom:4px"><?= $shippedCount ?></p>
        <p class="admin-stat-label" style="font-size:13px">Отправлено</p>
    </div>
    <div class="admin-stat-card" style="padding:12px;border-left-color:var(--admin-info)">
        <p class="admin-stat-number" style="font-size:24px;margin-bottom:4px"><?= $customsClearedCount ?></p>
        <p class="admin-stat-label" style="font-size:13px">Таможня пройдена</p>
    </div>
</div>

<!-- Фильтры (компактные) -->
<details class="admin-card" <?= $filtersExpanded ? 'open' : '' ?> style="padding:0">
    <summary style="cursor:pointer;padding:16px;background:var(--admin-bg);border-radius:8px;list-style:none;display:flex;align-items:center;gap:8px;font-weight:600">
        <i class="bi bi-funnel"></i>
        Фильтры
        <?php if ($activeFilterCount > 0): ?>
            <span class="admin-badge admin-badge-primary" style="margin-left:auto"><?= $activeFilterCount ?></span>
        <?php endif; ?>
    </summary>
    
    <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding: 16px;">
        <div class="form-group">
            <label>Поиск</label>
            <input type="text" name="search" class="form-control" placeholder="№ заказа, клиент..." value="<?= Html::encode($filterSearch) ?>">
        </div>
        
        <div class="form-group">
            <label>Статус</label>
            <select name="status" class="form-control">
                <option value="">Все статусы</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filterStatus === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Логист</label>
            <select name="logist" class="form-control">
                <option value="">Все</option>
                <?php foreach ($logists as $logist): ?>
                    <option value="<?= $logist->id ?>" <?= $filterLogist == $logist->id ? 'selected' : '' ?>>
                        <?= Html::encode($logist->username) ?>
                    </option>
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
            <label>Дата от</label>
            <input type="date" name="date_from" class="form-control" value="<?= Html::encode($filterDateFrom) ?>">
        </div>
        
        <div class="form-group">
            <label>Дата до</label>
            <input type="date" name="date_to" class="form-control" value="<?= Html::encode($filterDateTo) ?>">
        </div>
        
        <div class="form-group" style="display: flex; align-items: end; gap: 0.5rem;">
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-search"></i>
                Применить
            </button>
            <a href="<?= Url::to(['/admin/order']) ?>" class="admin-btn admin-btn-secondary">
                <i class="bi bi-x-circle"></i>
                Сбросить
            </a>
        </div>
    </form>
</details>

<!-- Таблица заказов -->
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 class="admin-card-title">
            <i class="bi bi-cart3"></i>
            Список заказов
        </h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <!-- Переключатель видов -->
            <div class="admin-view-toggle">
                <button type="button" class="admin-view-btn active" id="view-table-btn" onclick="switchView('table')">
                    <i class="bi bi-table"></i> Таблица
                </button>
                <button type="button" class="admin-view-btn" id="view-kanban-btn" onclick="switchView('kanban')">
                    <i class="bi bi-kanban"></i> Канбан
                </button>
            </div>
            <a href="<?= Url::to(['/admin/order/export?' . http_build_query($_GET)]) ?>" class="admin-export-btn" title="Excel экспорт">
                <i class="bi bi-file-earmark-excel"></i>
            </a>
            <select id="pageSizeSelect" onchange="changePageSize(this.value)" class="form-control" style="width: auto;">
                <?php foreach ($pageSizeOptions as $size): ?>
                    <option value="<?= $size ?>" <?= $pageSize === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- TABLE VIEW -->
    <div id="table-view">
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <th>Заказ / Товары</th>
                        <th>Клиент</th>
                        <th style="text-align: right;">Сумма</th>
                        <th>Статус</th>
                        <th style="text-align: center;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr onclick="location.href='<?= Url::to(['view', 'id' => $order->id]) ?>'" style="cursor:pointer" class="order-row">
                                <td onclick="event.stopPropagation()"><input type="checkbox" class="order-checkbox" value="<?= $order->id ?>"></td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.875rem;">#<?= $order->id ?></div>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 2px;">
                                        <?php 
                                        $productNames = [];
                                        foreach ($order->orderItems as $item) {
                                            $productNames[] = $item->product_name;
                                        }
                                        $productsText = implode(', ', $productNames);
                                        echo Html::encode(mb_strlen($productsText) > 35 ? mb_substr($productsText, 0, 35) . '...' : $productsText);
                                        ?>
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--admin-text-subdued); margin-top: 2px;">
                                        <?= Yii::$app->formatter->asDatetime($order->created_at, 'short') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($order->customer_id): ?>
                                        <a href="<?= Url::to(['/admin/customer/view', 'id' => $order->customer_id]) ?>" onclick="event.stopPropagation()" style="color: var(--admin-text-primary); text-decoration: none; font-weight: 500; font-size: 0.875rem;">
                                            <?= Html::encode($order->client_name) ?>
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 2px;">
                                            <?= Html::encode($order->client_phone) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-weight: 500; font-size: 0.875rem;"><?= Html::encode($order->client_name) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--admin-text-secondary);"><?= Html::encode($order->client_phone) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 600; white-space: nowrap;">
                                    <?= number_format($order->total_amount, 0) ?> BYN
                                </td>
                                <td onclick="event.stopPropagation()">
                                    <?php
                                    $deliveryStatuses = [
                                        'created' => ['label' => 'Создан', 'class' => 'secondary', 'icon' => 'bi-file-earmark'],
                                        'paid' => ['label' => 'Оплачен', 'class' => 'warning', 'icon' => 'bi-credit-card'],
                                        'processing' => ['label' => 'Обработка', 'class' => 'info', 'icon' => 'bi-gear'],
                                        'shipped' => ['label' => 'Отправлен', 'class' => 'primary', 'icon' => 'bi-truck'],
                                        'delivered' => ['label' => 'Доставлен', 'class' => 'success', 'icon' => 'bi-check-circle'],
                                        'canceled' => ['label' => 'Отменен', 'class' => 'danger', 'icon' => 'bi-x-circle'],
                                    ];
                                    $deliveryStatus = $deliveryStatuses[$order->status] ?? ['label' => $order->status, 'class' => 'secondary', 'icon' => 'bi-question'];
                                    ?>
                                    <span class="admin-badge admin-badge-<?= $deliveryStatus['class'] ?>" style="cursor: pointer;" onclick="showStatusDropdown(<?= $order->id ?>, this)">
                                        <i class="bi <?= $deliveryStatus['icon'] ?>"></i> <?= $deliveryStatus['label'] ?>
                                    </span>
                                    <?php if ($order->track_number): ?>
                                        <div style="font-size: 0.7rem; margin-top: 3px; color: var(--admin-text-secondary);">
                                            <i class="bi bi-upc"></i> <?= Html::encode(mb_substr($order->track_number, 0, 12)) ?><?= mb_strlen($order->track_number) > 12 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;" onclick="event.stopPropagation()">
                                    <div style="display: flex; gap: 4px; justify-content: center;">
                                        <a href="<?= Url::to(['view', 'id' => $order->id]) ?>" class="admin-btn admin-btn-sm admin-btn-secondary" title="Просмотр">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= Url::to(['edit', 'id' => $order->id]) ?>" class="admin-btn admin-btn-sm admin-btn-secondary" title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--admin-text-secondary);">
                                Нет заказов по текущим фильтрам
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- KANBAN VIEW -->
    <div id="kanban-view" style="display: none;">
        <div class="admin-kanban">
            <?php 
            $kanbanStatuses = ['created' => 'Новые', 'paid' => 'Оплачены', 'processing' => 'В работе', 'shipped' => 'Отправлены', 'completed' => 'Завершены'];
            foreach ($kanbanStatuses as $status => $label): 
                $statusOrders = array_filter($orders, fn($o) => $o->status === $status);
            ?>
                <div class="admin-kanban-column">
                    <div class="admin-kanban-header">
                        <span><?= $label ?></span>
                        <span class="admin-kanban-count"><?= count($statusOrders) ?></span>
                    </div>
                    <div class="admin-kanban-items">
                        <?php foreach ($statusOrders as $order): ?>
                            <div class="admin-kanban-card" onclick="location.href='<?= Url::to(['view', 'id' => $order->id]) ?>'">
                                <div class="admin-kanban-card-title">#<?= $order->id ?> <?= Html::encode($order->client_name) ?></div>
                                <div class="admin-kanban-card-meta">
                                    <span><?= number_format($order->total_amount, 0) ?> BYN</span>
                                    <span><?= date('d.m', $order->created_at) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Пагинация -->
    <?php if ($pagination->pageCount > 1): ?>
        <div style="margin-top: 1.5rem; display: flex; justify-content: center;">
            <?= \yii\widgets\LinkPager::widget([
                'pagination' => $pagination,
                'options' => ['class' => 'pagination'],
                'linkOptions' => ['class' => 'page-link'],
            ]) ?>
        </div>
    <?php endif; ?>
</div>

<style>
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg);
    color: var(--admin-text-primary);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
}

.pagination {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.375rem;
    background: var(--admin-bg);
    color: var(--admin-text-primary);
    text-decoration: none;
    transition: all 0.2s;
}

.page-link:hover {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
}
</style>

<script>
function switchView(view) {
    const tableView = document.getElementById('table-view');
    const kanbanView = document.getElementById('kanban-view');
    const tableBtn = document.getElementById('view-table-btn');
    const kanbanBtn = document.getElementById('view-kanban-btn');
    
    if (view === 'table') {
        tableView.style.display = 'block';
        kanbanView.style.display = 'none';
        tableBtn.classList.add('active');
        kanbanBtn.classList.remove('active');
    } else {
        tableView.style.display = 'none';
        kanbanView.style.display = 'grid';
        tableBtn.classList.remove('active');
        kanbanBtn.classList.add('active');
    }
    localStorage.setItem('orders-view-mode', view);
}

// Восстановить сохраненный вид при загрузке
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('orders-view-mode') || 'table';
    if (savedView === 'kanban') {
        switchView('kanban');
    }
});

function changePageSize(value) {
    const params = new URLSearchParams(window.location.search);
    params.set('per-page', value);
    params.delete('page');
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

function updateStatus(select) {
    const orderId = select.dataset.orderId;
    const newStatus = select.value;
    const originalValue = select.querySelector('option[selected]')?.value;
    
    fetch(`/admin/order/${orderId}/change-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            select.style.background = '#dcfce7';
            setTimeout(() => select.style.background = '', 500);
        } else {
            alert('Ошибка: ' + data.message);
            if (originalValue) select.value = originalValue;
        }
    })
    .catch(err => {
        alert('Ошибка сети');
        console.error(err);
    });
}

// Функция для показа выпадающего списка статусов при клике на бейдж
function showStatusDropdown(orderId, badgeElement) {
    // Удаляем существующие дропдауны
    document.querySelectorAll('.status-dropdown-popup').forEach(el => el.remove());
    
    const statuses = <?= json_encode($statuses ?? []) ?>;
    const currentStatus = badgeElement.textContent.trim();
    
    const dropdown = document.createElement('div');
    dropdown.className = 'status-dropdown-popup';
    dropdown.style.cssText = `
        position: absolute;
        background: white;
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        min-width: 150px;
        padding: 4px 0;
    `;
    
    Object.entries(statuses).forEach(([key, label]) => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.15s;
        `;
        item.textContent = label;
        item.onmouseover = () => item.style.background = 'var(--admin-bg)';
        item.onmouseout = () => item.style.background = 'transparent';
        item.onclick = () => {
            changeOrderStatus(orderId, key, badgeElement);
            dropdown.remove();
        };
        dropdown.appendChild(item);
    });
    
    const rect = badgeElement.getBoundingClientRect();
    dropdown.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    dropdown.style.left = (rect.left + window.scrollX) + 'px';
    
    document.body.appendChild(dropdown);
    
    // Закрыть при клике вне
    setTimeout(() => {
        document.addEventListener('click', function close(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.remove();
                document.removeEventListener('click', close);
            }
        });
    }, 100);
}

// Функция для изменения статуса заказа
function changeOrderStatus(orderId, newStatus, badgeElement) {
    fetch(`/admin/order/${orderId}/change-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Обновляем бейдж
            const statusConfig = {
                'created': { class: 'secondary', icon: 'bi-file-earmark', label: 'Создан' },
                'paid': { class: 'warning', icon: 'bi-credit-card', label: 'Оплачен' },
                'processing': { class: 'info', icon: 'bi-gear', label: 'Обработка' },
                'shipped': { class: 'primary', icon: 'bi-truck', label: 'Отправлен' },
                'delivered': { class: 'success', icon: 'bi-check-circle', label: 'Доставлен' },
                'canceled': { class: 'danger', icon: 'bi-x-circle', label: 'Отменен' }
            };
            const cfg = statusConfig[newStatus] || statusConfig['created'];
            badgeElement.className = `admin-badge admin-badge-${cfg.class}`;
            badgeElement.innerHTML = `<i class="bi ${cfg.icon}"></i> ${cfg.label}`;
            
            // Визуальный фидбек
            badgeElement.style.transform = 'scale(1.05)';
            setTimeout(() => badgeElement.style.transform = 'scale(1)', 200);
        } else {
            alert('Ошибка: ' + (data.message || 'Не удалось изменить статус'));
        }
    })
    .catch(err => {
        alert('Ошибка сети');
        console.error(err);
    });
}
</script>
