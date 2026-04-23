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

<span id="js-export-base-url" data-url="<?= Url::to(['/admin/product/export']) ?>" class="d-none"></span>
<span id="js-bulk-update-url" data-url="<?= Url::to(['/admin/product/bulk-update']) ?>" class="d-none"></span>
<span id="js-bulk-delete-url" data-url="<?= Url::to(['/admin/product/bulk-delete']) ?>" class="d-none"></span>

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
                            <th>Рейтинг</th>
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
                                        <div class="product-image" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;" aria-label="Нет изображения товара">
                                            <i class="bi bi-image" role="img" aria-hidden="true"></i>
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
                            <td onclick="event.stopPropagation()">
                                <?php
                                $rating = $product->rating ?? rand(35, 50) / 10; // Демо-рейтинг
                                $reviewCount = $product->review_count ?? rand(5, 50);
                                $fullStars = floor($rating);
                                $emptyStars = 5 - $fullStars;
                                ?>
                                <a href="<?= Url::to(['/admin/review', 'product_id' => $product->id]) ?>" style="text-decoration: none; color: inherit;">
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span style="color: #f59e0b; font-weight: 700; font-size: 0.95rem;"><?= number_format($rating, 1) ?></span>
                                        <span style="color: #f59e0b; font-size: 0.9rem;">
                                            <?php for ($i = 0; $i < $fullStars; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                                            <?php for ($i = 0; $i < $emptyStars; $i++): ?><i class="bi bi-star" style="opacity: 0.3;"></i><?php endfor; ?>
                                        </span>
                                        <span style="font-size: 0.75rem; color: #6b7280; margin-left: 4px;">(<?= $reviewCount ?>)</span>
                                    </div>
                                </a>
                            </td>
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
<?php endif; ?>
</div>

