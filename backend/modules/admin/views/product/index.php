<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
use app\backend\modules\catalog\models\Product;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */
/** @var array $brands */
/** @var array $categories */
/** @var int $pageSize */
/** @var array $pageSizeOptions */

$this->title = 'Управление товарами';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/index']];

$products = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$activeFilterCount = count(array_filter([
    $filterSearch ?? null,
    $filterBrand ?? null,
    $filterCategory ?? null,
    $filterSource ?? null,
    $filterStatus ?? null,
    $filterStock ?? null,
], fn($value) => $value !== null && $value !== ''));
$filtersCollapsed = $activeFilterCount === 0;
$brandOptions = ArrayHelper::map($brands, 'id', 'name');
$categoryOptions = ArrayHelper::map($categories, 'id', 'name');
?>

<style>
/* ===== Import Info Card ===== */
.import-info-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.import-info-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.import-info-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6, #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
}

.import-info-title {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
}

.import-info-text {
    font-size: 14px;
    line-height: 1.6;
    color: #4b5563;
    margin: 0 0 20px;
}

.import-info-text strong {
    color: #111827;
}

/* Stats */
.import-info-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 20px;
}

.import-stat-item {
    text-align: center;
}

.import-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.import-stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

/* Actions */
.import-info-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: auto;
}

.btn-import-info {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.btn-import-info.primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn-import-info.primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-import-info.secondary {
    background: #f3f4f6;
    color: #374151;
    border-color: #e5e7eb;
}

.btn-import-info.secondary:hover {
    background: #e5e7eb;
}

.btn-import-info.ghost {
    background: transparent;
    color: #6b7280;
    border-color: #e5e7eb;
}

.btn-import-info.ghost:hover {
    background: #f9fafb;
    color: #374151;
}

/* Responsive */
@media (max-width: 768px) {
    .import-info-stats {
        grid-template-columns: 1fr;
    }
    .import-info-actions {
        flex-direction: column;
    }
    .btn-import-info {
        width: 100%;
        justify-content: center;
    }
}

/* Legacy styles below */
.products-page {
    background: #ffffff;
    padding: 1.5rem;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.products-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.products-header p {
    margin: 0.25rem 0 0;
    color: #6b7280;
    font-size: 0.9rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border-radius: 8px;
    border: 1px solid transparent;
    padding: 0.6rem 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    background: #f3f4f6;
    color: #111827;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-action:hover {
    background: #e5e7eb;
}

.btn-primary-action {
    background: #111827;
    color: #ffffff;
}

.btn-primary-action:hover {
    background: #000000;
}

.btn-poizon-import {
    background: #4472C4;
    color: #ffffff;
}

.btn-poizon-import:hover {
    background: #3a5cb8;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.stat-card {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 1rem;
    background: #ffffff;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.stat-label {
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.08em;
    color: #9ca3af;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
}

.stat-sub {
    color: #6b7280;
    font-size: 0.85rem;
}

.filters-panel {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.25rem;
    background: #f9fafb;
}

.filters-panel.collapsed {
    display: none;
}

.filters-form {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.85rem;
}

.filter-group label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.35rem;
}

.filter-input {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0.55rem 0.75rem;
    background: #ffffff;
    font-size: 0.85rem;
}

.filter-input:focus {
    border-color: #111827;
    outline: none;
    box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.15);
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-toggle .filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
    border-radius: 999px;
    background: #111827;
    color: #ffffff;
    font-size: 0.75rem;
    padding: 0 0.4rem;
}

.export-menu {
    position: relative;
}

.export-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 0.5rem;
    min-width: 180px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.15);
    display: none;
    z-index: 20;
}

.export-dropdown.visible {
    display: block;
}

.export-dropdown a {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.35rem;
    color: #111827;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.85rem;
}

.export-dropdown a:hover {
    background: #f3f4f6;
}

.table-top,
.table-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.table-top .summary,
.table-bottom .summary {
    color: #6b7280;
    font-size: 0.85rem;
}

.products-table-wrapper {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 0 1rem 1rem;
    background: #ffffff;
}

.table-scroll {
    overflow-x: auto;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.products-table th {
    text-align: left;
    font-weight: 600;
    padding: 0.75rem 0.5rem;
    border-bottom: 2px solid #e5e7eb;
    color: #111827;
    background: #f9fafb;
}

.products-table td {
    padding: 0.65rem 0.5rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.product-info {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.product-image {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: cover;
    background: #f3f4f6;
}

.product-meta {
    color: #6b7280;
    font-size: 0.75rem;
}

.status-badge,
.stock-pill,
.source-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 999px;
    font-size: 0.75rem;
    padding: 0.2rem 0.8rem;
    font-weight: 600;
}

.status-active { background: #d1fae5; color: #065f46; }
.status-inactive { background: #fee2e2; color: #991b1b; }
.stock-in { background: #ecfdf5; color: #047857; }
.stock-low { background: #fef3c7; color: #92400e; }
.stock-out { background: #fee2e2; color: #b91c1c; }
.source-pill.poizon { background: #e0e7ff; color: #312e81; }
.source-pill.manual { background: #f1f5f9; color: #1f2937; }

.product-actions {
    display: flex;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.action-btn {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.35rem 0.5rem;
    background: #ffffff;
    color: #111827;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: #f3f4f6;
}

.bulk-actions {
    border: 1px dashed #9ca3af;
    border-radius: 12px;
    padding: 0.75rem;
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.bulk-actions.show {
    display: flex;
}

.bulk-actions .selected-count {
    font-weight: 600;
}

.bulk-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #6b7280;
}

.page-size-control {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.85rem;
    color: #6b7280;
}

.page-size-control select {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.3rem 0.4rem;
    font-size: 0.85rem;
    background: #fff;
}

.product-row:hover {
    background: #f9fafb;
}

.customer-row:hover {
    background: #f9fafb;
}

.order-row:hover {
    background: #f9fafb;
}

@media (max-width: 768px) {
    .products-page {
        padding: 1rem;
    }
    .header-actions {
        flex-direction: column;
        align-items: stretch;
    }
    .filters-grid {
        grid-template-columns: 1fr;
    }
    .product-info {
        flex-direction: column;
        align-items: flex-start;
    }
    .table-top,
    .table-bottom,
    .bulk-actions {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="products-page">
    <div class="products-header">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>Всего в каталоге: <?= Html::encode($stats['total'] ?? 0) ?> товаров</p>
        </div>
        <div class="header-actions">
            <label class="page-size-control">
                Показывать
                <select id="pageSizeSelect" onchange="changeProductPageSize(this.value)">
                    <?php foreach ($pageSizeOptions as $size): ?>
                        <option value="<?= $size ?>" <?= $size == $pageSize ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                на странице
            </label>
            
            <div class="export-group">
                <a href="<?= Url::to(['/admin/product/export']) ?>" class="btn-action">
                    <i class="bi bi-download"></i>
                    Экспорт
                </a>
                <button type="button" class="btn-action" onclick="showBulkActions()">
                    <i class="bi bi-check2-square"></i>
                    Массовые действия
                </button>
            </div>
            
            <a href="<?= Url::to(['/admin/import']) ?>" class="btn-action btn-poizon-import">
                <i class="fas fa-download"></i>
                Импорт товаров
            </a>
            
            <a href="<?= Url::to(['/admin/product/create']) ?>" class="btn-action btn-primary-action">
                <i class="bi bi-plus-lg"></i>
                Новый товар
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Всего товаров</div>
            <div class="stat-value"><?= number_format($stats['total'] ?? 0) ?></div>
            <div class="stat-sub">В каталоге сейчас</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Активные</div>
            <div class="stat-value"><?= number_format($stats['active'] ?? 0) ?></div>
            <div class="stat-sub">Неактивные: <?= number_format($stats['inactive'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Источник Poizon</div>
            <div class="stat-value"><?= number_format($stats['poizon'] ?? 0) ?></div>
            <div class="stat-sub">Ручные: <?= number_format($stats['manual'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">В наличии</div>
            <div class="stat-value"><?= number_format($stats['inStock'] ?? 0) ?></div>
            <div class="stat-sub">Нет в наличии: <?= number_format($stats['outOfStock'] ?? 0) ?></div>
        </div>
    </div>

    <!-- Виджет импорта -->
    <div class="row mb-4">
        <div class="col-lg-4">
            <?= \app\backend\modules\admin\widgets\ImportWidget::widget() ?>
        </div>
        <div class="col-lg-8">
            <!-- Информация об импорте -->
            <div class="import-info-card">
                <div class="import-info-header">
                    <div class="import-info-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="import-info-title">Информация об импорте</div>
                </div>
                <p class="import-info-text">
                    Система импорта позволяет автоматически и вручную добавлять товары из различных источников: 
                    <strong>Lamoda, Dewu, Zalando, StockX</strong>. Поддерживаются форматы <strong>JSON, CSV и Excel</strong>.
                </p>
                <div class="import-info-stats">
                    <div class="import-stat-item">
                        <div class="import-stat-value" id="import-stat-total">0</div>
                        <div class="import-stat-label">Товаров импортировано</div>
                    </div>
                    <div class="import-stat-item">
                        <div class="import-stat-value" id="import-stat-today">0</div>
                        <div class="import-stat-label">Сегодня</div>
                    </div>
                    <div class="import-stat-item">
                        <div class="import-stat-value" id="import-stat-sources">4</div>
                        <div class="import-stat-label">Источников</div>
                    </div>
                </div>
                <div class="import-info-actions">
                    <a href="<?= Url::to(['/admin/import']) ?>" class="btn-import-info primary">
                        <i class="bi bi-bar-chart-line"></i> Статистика импорта
                    </a>
                    <a href="<?= Url::to(['/admin/import/logs']) ?>" class="btn-import-info secondary">
                        <i class="bi bi-journal-text"></i> Журнал логов
                    </a>
                    <a href="<?= Url::to(['/admin/import/settings']) ?>" class="btn-import-info ghost">
                        <i class="bi bi-gear"></i> Настройки
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="filters-panel <?= $filtersCollapsed ? 'collapsed' : '' ?>" id="filtersPanel">
        <?= Html::beginForm(['/admin/product/index'], 'get', ['class' => 'filters-form']) ?>
        <div class="filters-grid">
            <div class="filter-group">
                <label>Поиск</label>
                <?= Html::textInput('search', $filterSearch, [
                    'class' => 'filter-input',
                    'placeholder' => 'Название, артикул, Poizon ID',
                ]) ?>
            </div>
            <div class="filter-group">
                <label>Бренд</label>
                <?= Html::dropDownList('brand', $filterBrand, ['' => 'Все бренды'] + $brandOptions, ['class' => 'filter-input']) ?>
            </div>
            <div class="filter-group">
                <label>Категория</label>
                <?= Html::dropDownList('category', $filterCategory, ['' => 'Все категории'] + $categoryOptions, ['class' => 'filter-input']) ?>
            </div>
            <div class="filter-group">
                <label>Статус</label>
                <?= Html::dropDownList('status', $filterStatus, [
                    '' => 'Все статусы',
                    'active' => 'Активные',
                    'inactive' => 'Неактивные',
                ], ['class' => 'filter-input']) ?>
            </div>
            <div class="filter-group">
                <label>Наличие</label>
                <?= Html::dropDownList('stock', $filterStock, [
                    '' => 'Все',
                    'in' => 'В наличии',
                    'out' => 'Нет в наличии',
                ], ['class' => 'filter-input']) ?>
            </div>
            <div class="filter-group">
                <label>Источник</label>
                <?= Html::dropDownList('source', $filterSource, [
                    '' => 'Все источники',
                    'poizon' => 'Poizon',
                    'manual' => 'Ручные',
                ], ['class' => 'filter-input']) ?>
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-action btn-primary-action">
                <i class="bi bi-search"></i>
                Применить
            </button>
            <a href="<?= Url::to(['/admin/product/index']) ?>" class="btn-action">
                <i class="bi bi-x-circle"></i>
                Сбросить
            </a>
        </div>
        <?= Html::endForm(); ?>
    </div>

    <div class="products-table-wrapper">
        <div class="table-top">
            <div class="summary">
                Показаны <?= $pagination->offset + 1 ?>–<?= min($pagination->limit, $pagination->totalCount) ?> из <?= $pagination->totalCount ?>
            </div>
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options' => ['class' => 'pagination pagination-sm'],
            ]) ?>
        </div>

        <div class="bulk-actions" id="bulkActions">
            <div class="selected-count"><span id="selectedCount">0</span> выбрано</div>
            <div class="bulk-buttons">
                <button class="btn-action" type="button" onclick="bulkUpdateProducts('is_active', 1)">Активировать</button>
                <button class="btn-action" type="button" onclick="bulkUpdateProducts('is_active', 0)">Деактивировать</button>
                <button class="btn-action" type="button" onclick="confirmBulkDelete()">Удалить</button>
                <button class="btn-action" type="button" onclick="bulkExportSelected()">Экспорт</button>
            </div>
        </div>

        <?php if (!empty($products)): ?>
            <div class="table-scroll">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAllProducts"></th>
                            <th>Товар</th>
                            <th>Бренд</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>Наличие</th>
                            <th>Статус</th>
                            <th>Источник</th>
                            <th style="width:140px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                            $imageUrl = $product->getMainImageUrl();
                            $stockStatus = $product->stock_status;
                            $stockClass = 'stock-in';
                            $stockText = 'В наличии';
                            if ($stockStatus === 'low_stock') {
                                $stockClass = 'stock-low';
                                $stockText = 'Мало';
                            } elseif ($stockStatus === \app\backend\modules\catalog\models\Product::STOCK_OUT_OF_STOCK) {
                                $stockClass = 'stock-out';
                                $stockText = 'Нет в наличии';
                            }
                            $sourceClass = $product->poizon_id ? 'poizon' : 'manual';
                            $sourceText = $product->poizon_id ? 'Poizon' : 'Ручной';
                        ?>
                        <tr onclick='location.href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>"' style="cursor:pointer" class="product-row">
                            <td onclick="event.stopPropagation()"><input type="checkbox" class="product-checkbox" value="<?= $product->id ?>"></td>
                            <td>
                                <div class="product-info">
                                    <?php if ($imageUrl): ?>
                                        <img src="<?= $imageUrl ?>" alt="<?= Html::encode($product->name) ?>" class="product-image" loading="lazy">
                                    <?php else: ?>
                                        <div class="product-image" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="product-name">
                                            <?= Html::encode($product->name) ?>
                                        </div>
                                        <div class="product-meta">
                                            Артикул: <?= Html::encode($product->vendor_code ?: '—') ?> ·
                                            Poizon ID: <?= Html::encode($product->poizon_id ?: '—') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><?= Html::encode($product->brand->name ?? '—') ?></td>
                            <td><?= Html::encode($product->category->name ?? '—') ?></td>
                            <td><?= Yii::$app->formatter->asCurrency($product->price ?? 0, 'BYN') ?></td>
                            <td><span class="stock-pill <?= $stockClass ?>"><?= $stockText ?></span></td>
                            <td>
                                <span class="status-badge <?= $product->is_active ? 'status-active' : 'status-inactive' ?>">
                                    <?= $product->is_active ? 'Активен' : 'Неактивен' ?>
                                </span>
                            </td>
                            <td><span class="source-pill <?= $sourceClass ?>"><?= $sourceText ?></span></td>
                            <td onclick="event.stopPropagation()">
                                <div class="product-actions">
                                    <a href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>" class="action-btn" title="Просмотр" onclick="event.stopPropagation()">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= Url::to(['/admin/product/edit', 'id' => $product->id]) ?>" class="action-btn" title="Редактировать" onclick="event.stopPropagation()">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?= Html::a('<i class="bi bi-arrow-repeat"></i>', ['/admin/product/sync', 'id' => $product->id], [
                                        'class' => 'action-btn',
                                        'title' => 'Синхронизировать',
                                        'data-method' => 'post',
                                        'data-confirm' => 'Синхронизировать товар с Poizon?',
                                        'onclick' => 'event.stopPropagation()',
                                    ]) ?>
                                    <?= Html::a($product->is_active ? '<i class="bi bi-pause"></i>' : '<i class="bi bi-play"></i>', ['/admin/product/toggle', 'id' => $product->id], [
                                        'class' => 'action-btn',
                                        'title' => $product->is_active ? 'Деактивировать' : 'Активировать',
                                        'data-method' => 'post',
                                        'onclick' => 'event.stopPropagation()',
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const filtersPanel = document.getElementById('filtersPanel');
const exportDropdown = document.getElementById('exportDropdown');
const selectAllCheckbox = document.getElementById('selectAllProducts');
const productCheckboxes = document.querySelectorAll('.product-checkbox');
const bulkActions = document.getElementById('bulkActions');
const selectedCountEl = document.getElementById('selectedCount');
const exportBaseUrl = '<?= Url::to(['/admin/product/export']) ?>';
const bulkUpdateUrl = '<?= Url::to(['/admin/product/bulk-update']) ?>';
const bulkDeleteUrl = '<?= Url::to(['/admin/product/bulk-delete']) ?>';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

function toggleProductFilters() {
    filtersPanel.classList.toggle('collapsed');
}

function toggleExportMenu() {
    exportDropdown.classList.toggle('visible');
}

document.addEventListener('click', (event) => {
    if (!event.target.closest('.export-menu')) {
        exportDropdown.classList.remove('visible');
    }
});

selectAllCheckbox?.addEventListener('change', function() {
    productCheckboxes.forEach(cb => cb.checked = this.checked);
    updateBulkState();
});

productCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkState));

function updateBulkState() {
    const selected = document.querySelectorAll('.product-checkbox:checked');
    selectedCountEl.textContent = selected.length;
    if (selected.length > 0) {
        bulkActions.classList.add('show');
    } else {
        bulkActions.classList.remove('show');
    }
}

function getSelectedProductIds() {
    return Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
}

function changeProductPageSize(size) {
    const url = new URL(window.location.href);
    url.searchParams.set('per-page', size);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function bulkUpdateProducts(field, value) {
    const ids = getSelectedProductIds();
    if (!ids.length) {
        alert('Выберите товары');
        return;
    }

    const formData = new FormData();
    formData.append('ids', JSON.stringify(ids));
    formData.append('field', field);
    formData.append('value', value);
    formData.append('_csrf', csrfToken);

    fetch(bulkUpdateUrl, {
        method: 'POST',
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Не удалось обновить товары.');
            }
        })
        .catch(() => alert('Ошибка сети'));
}

function confirmBulkDelete() {
    const ids = getSelectedProductIds();
    if (!ids.length) {
        alert('Выберите товары');
        return;
    }
    if (confirm(`Удалить ${ids.length} товаров? Действие необратимо.`)) {
        bulkDeleteProducts(ids);
    }
}

function bulkDeleteProducts(ids) {
    const formData = new FormData();
    formData.append('ids', JSON.stringify(ids));
    formData.append('_csrf', csrfToken);

    fetch(bulkDeleteUrl, {
        method: 'POST',
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Не удалось удалить товары.');
            }
        })
        .catch(() => alert('Ошибка сети'));
}

function bulkExportSelected() {
    const ids = getSelectedProductIds();
    if (!ids.length) {
        window.location.href = exportBaseUrl;
        return;
    }
    window.location.href = `${exportBaseUrl}?ids=${ids.join(',')}&format=xlsx`;
}
</script>
