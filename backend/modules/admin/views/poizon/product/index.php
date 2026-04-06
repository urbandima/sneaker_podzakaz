<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
use app\models\Product;

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

<input type="hidden" id="exportBaseUrl" value="<?= Url::to(['/admin/product/export']) ?>">
<input type="hidden" id="bulkUpdateUrl" value="<?= Url::to(['/admin/product/bulk-update']) ?>">
<input type="hidden" id="bulkDeleteUrl" value="<?= Url::to(['/admin/product/bulk-delete']) ?>">

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
                        <option value="<?= $size ?>" <?= $pageSize === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
                на странице
            </label>
            <button class="btn-action btn-secondary-action filter-toggle" id="filtersToggle" type="button" onclick="toggleProductFilters()">
                <i class="bi bi-funnel"></i>
                Фильтры
                <?php if ($activeFilterCount): ?>
                    <span class="filter-count"><?= $activeFilterCount ?></span>
                <?php endif; ?>
            </button>
            <div class="export-menu">
                <button class="btn-action btn-secondary-action" type="button" onclick="toggleExportMenu()">
                    <i class="bi bi-download"></i>
                    Экспорт
                </button>
                <div class="export-dropdown" id="exportDropdown">
                    <a href="<?= Url::to(['/admin/product/export', 'format' => 'xlsx']) ?>">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Excel (.xlsx)
                    </a>
                    <a href="<?= Url::to(['/admin/product/export', 'format' => 'csv']) ?>">
                        <i class="bi бі-file-earmark-text"></i> CSV
                    </a>
                </div>
            </div>
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
                                } elseif ($stockStatus === Product::STOCK_OUT_OF_STOCK) {
                                    $stockClass = 'stock-out';
                                    $stockText = 'Нет в наличии';
                                }
                                $sourceClass = $product->poizon_id ? 'poizon' : 'manual';
                                $sourceText = $product->poizon_id ? 'Poizon' : 'Ручной';
                            ?>
                            <tr>
                                <td><input type="checkbox" class="product-checkbox" value="<?= $product->id ?>"></td>
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
                                                <a href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>">
                                                    <?= Html::encode($product->name) ?>
                                                </a>
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
                                <td>
                                    <div class="product-actions">
                                        <a href="<?= Url::to(['/admin/product/view', 'id' => $product->id]) ?>" class="action-btn" title="Просмотр">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= Url::to(['/admin/product/edit', 'id' => $product->id]) ?>" class="action-btn" title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?= Html::a('<i class="bi bi-arrow-repeat"></i>', ['/admin/product/sync', 'id' => $product->id], [
                                            'class' => 'action-btn',
                                            'title' => 'Синхронизировать',
                                            'data-method' => 'post',
                                            'data-confirm' => 'Синхронизировать товар с Poizon?',
                                        ]) ?>
                                        <?= Html::a($product->is_active ? '<i class="bi bi-pause"></i>' : '<i class="bi bi-play"></i>', ['/admin/product/toggle', 'id' => $product->id], [
                                            'class' => 'action-btn',
                                            'title' => $product->is_active ? 'Деактивировать' : 'Активировать',
                                            'data-method' => 'post',
                                        ]) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Товары не найдены. Измените фильтры или добавьте новый товар.</p>
            </div>
        <?php endif; ?>

        <div class="table-bottom">
            <div class="summary">
                Показаны <?= min($pagination->limit, $pagination->totalCount) ?> из <?= $pagination->totalCount ?> товаров
            </div>
            <?= LinkPager::widget([
                'pagination' => $pagination,
                'options' => ['class' => 'pagination pagination-sm'],
            ]) ?>
        </div>
    </div>
</div>

