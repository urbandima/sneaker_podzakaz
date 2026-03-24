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

<!-- Статистика по статусам -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= $totalCount ?></p>
        <p class="admin-stat-label">Всего заказов</p>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-success);">
        <p class="admin-stat-number"><?= $processedCount ?></p>
        <p class="admin-stat-label">Обработано</p>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-warning);">
        <p class="admin-stat-number"><?= $shippedCount ?></p>
        <p class="admin-stat-label">Отправлено</p>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-info);">
        <p class="admin-stat-number"><?= $customsClearedCount ?></p>
        <p class="admin-stat-label">Таможня пройдена</p>
    </div>
</div>

<!-- Фильтры -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-funnel"></i>
        Фильтры
    </h2>
    
    <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
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
</div>

<!-- Таблица заказов -->
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 class="admin-card-title">
            <i class="bi bi-cart3"></i>
            Список заказов
        </h2>
        <div style="display: flex; gap: 0.5rem;">
            <select id="pageSizeSelect" onchange="changePageSize(this.value)" class="form-control" style="width: auto;">
                <?php foreach ($pageSizeOptions as $size): ?>
                    <option value="<?= $size ?>" <?= $pageSize === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Email</th>
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
                        <tr>
                            <td><input type="checkbox" class="order-checkbox" value="<?= $order->id ?>"></td>
                            <td style="font-weight: 600;"><?= $order->id ?></td>
                            <td><?= Html::encode($order->client_name) ?></td>
                            <td><?= Html::encode($order->client_phone) ?></td>
                            <td><?= Html::encode($order->client_email) ?></td>
                            <td style="font-weight: 600;"><?= number_format($order->total_amount, 2) ?> BYN</td>
                            <td>
                                <span class="admin-badge admin-badge-<?= $order->status === 'completed' ? 'success' : ($order->status === 'processing' ? 'warning' : 'info') ?>">
                                    <?= Html::encode($statuses[$order->status] ?? $order->status) ?>
                                </span>
                            </td>
                            <td><?= Html::encode($logistMap[$order->assigned_logist] ?? '—') ?></td>
                            <td><?= Yii::$app->formatter->asDatetime($order->created_at) ?></td>
                            <td>
                                <a href="<?= Url::to(['view', 'id' => $order->id]) ?>" class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 2rem; color: var(--admin-text-secondary);">
                            Нет заказов по текущим фильтрам
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
</script>
