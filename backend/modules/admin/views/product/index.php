<?php

/**
 * Каталог товаров — redesigned to match order/customer list pattern
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */
/** @var array $brands */
/** @var array $categories */
/** @var int $pageSize */
/** @var array $pageSizeOptions */

$this->title = 'Каталог товаров';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/index']];

$products   = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$totalCount = $pagination->totalCount;

$brandOptions    = ArrayHelper::map($brands, 'id', 'name');
$categoryOptions = ArrayHelper::map($categories, 'id', 'name');

// Sort helpers
$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#2563eb);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#2563eb);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

// Expanded filter row 2
$row2Active = ($filterGender ?? '') || ($filterSeason ?? '') || ($filterPriceFrom ?? '') || ($filterPriceTo ?? '') || ($filterSource ?? '');
$row2Count  = count(array_filter([$filterGender ?? '', $filterSeason ?? '', $filterPriceFrom ?? '', $filterPriceTo ?? '', $filterSource ?? '']));

// Status funnel items
$funnelItems = [
    ''             => ['label' => 'Все',            'count' => $stats['total'],      'dot' => '#6b7280'],
    'active'       => ['label' => 'Активные',       'count' => $stats['active'],     'dot' => '#16a34a'],
    'archived'     => ['label' => 'Архив',          'count' => $stats['inactive'],   'dot' => '#6b7280',  'title' => 'Неактивные товары (is_active = false)'],
    'out_of_stock' => ['label' => 'Нет в наличии',  'count' => $stats['outOfStock'], 'dot' => '#dc2626'],
    'zero_price'   => ['label' => 'Без цены',       'count' => $stats['zeroPrice'],  'dot' => '#f59e0b',  'title' => 'Товары без цены — большинство также находятся в архиве'],
];

// Column definitions for selector
$columnDefs = [
    'article'     => 'Артикул',
    'brand'       => 'Бренд',
    'price'       => 'Цена',
    'sizes'       => 'Размеры',
    'stock_total' => 'Склад',
    'status'      => 'Статус',
];
?>

<style>
/* === Status Funnel === */
.catalog-funnel{display:flex;gap:6px;flex-wrap:wrap;align-items:center;padding:12px 16px;background:var(--admin-surface,#fff);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:12px}
.funnel-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6b7280);font-size:.8125rem;font-weight:500;text-decoration:none;transition:all .18s ease;white-space:nowrap;border:1.5px solid transparent}
.funnel-pill:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1);color:var(--admin-text-primary,#111);text-decoration:none}
.funnel-pill--active{background:var(--admin-primary,#2563eb)!important;color:#fff!important;border-color:var(--admin-primary,#2563eb);font-weight:700}
.funnel-pill--active .funnel-pill-count{background:rgba(255,255,255,.25);color:#fff}
.funnel-pill-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.funnel-pill-count{font-size:.75rem;font-weight:700;background:rgba(0,0,0,.07);color:inherit;padding:1px 6px;border-radius:10px;min-width:20px;text-align:center}

/* === Filter bar === */
.filter-wrap{margin-bottom:14px}
.compact-filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:var(--admin-surface,#fff)}
.filter-row1{border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.filter-row1.has-row2{border-radius:12px 12px 0 0}
.filter-row2{border-radius:0 0 12px 12px;border-top:1px solid var(--admin-border,#e5e7eb);box-shadow:0 2px 4px rgba(0,0,0,.05)}
.compact-filter-input,.compact-filter-select{height:32px;border:1.5px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:0 10px;font-size:.8125rem;color:var(--admin-text-primary,#111);background:var(--admin-surface,#fff);outline:none;transition:border-color .15s}
.compact-filter-input:focus,.compact-filter-select:focus{border-color:var(--admin-primary,#2563eb)}
.compact-filter-input{flex:1;min-width:160px;max-width:280px}
.compact-filter-input[type=number]{flex:0 0 auto;min-width:88px;max-width:110px}
.compact-filter-select{flex:0 0 auto;min-width:110px}
.compact-filter-btn{height:32px;padding:0 12px;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6b7280)}
.compact-filter-btn:hover{background:#e5e7eb;color:#374151;text-decoration:none}
.compact-filter-btn--apply{background:var(--admin-primary,#2563eb);color:#fff}
.compact-filter-btn--apply:hover{background:#1d4ed8;color:#fff}
.compact-filter-btn--reset:hover{background:#fee2e2;color:#dc2626}
.compact-filter-btn--expand.is-active{background:#eff6ff;color:var(--admin-primary,#2563eb);border:1.5px solid #bfdbfe}

/* === Column selector === */
.col-selector-wrap{position:relative;flex-shrink:0}
.col-selector-dropdown{position:absolute;right:0;top:calc(100% + 6px);background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e5e7eb);border-radius:10px;padding:12px;z-index:300;min-width:190px;max-height:370px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.12)}
.col-selector-item{display:flex;align-items:center;gap:8px;padding:4px 2px;font-size:.8125rem;cursor:pointer;color:var(--admin-text-primary,#111);user-select:none;border-radius:4px}
.col-selector-item:hover{background:var(--admin-surface-hover,#f3f4f6)}
.col-selector-item input{width:15px;height:15px;cursor:pointer;accent-color:var(--admin-primary,#2563eb);flex-shrink:0}

/* === Status pill === */
.status-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;line-height:1.6}

/* === Product thumbnail === */
.product-thumb{width:44px;height:44px;border-radius:8px;object-fit:cover;background:#f3f4f6;flex-shrink:0}
.product-thumb--empty{display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:1.1rem}

/* === Sortable headers === */
th[data-sort]{cursor:pointer;user-select:none;white-space:nowrap}
th[data-sort]:hover{background:var(--admin-surface-hover,#f3f4f6)!important}

/* === Clickable row === */
#catalogTable tbody tr{cursor:pointer;transition:background .1s}
#catalogTable tbody tr:hover{background:var(--admin-surface-hover,#f5f7fa)!important}

/* === Sticky thead === */
#catalogTable thead th{position:sticky;top:0;z-index:10;background:var(--admin-surface,#fff);box-shadow:0 1px 0 var(--admin-border,#e5e7eb)}

/* === Infinite scroll === */
#scroll-sentinel{height:1px;margin-top:8px}
#scroll-loading{display:none;justify-content:center;align-items:center;padding:16px;gap:8px;color:var(--admin-text-secondary,#6b7280);font-size:.875rem}
@keyframes spin{to{transform:rotate(360deg)}}
.scroll-spinner{width:18px;height:18px;border:2px solid #e5e7eb;border-top-color:var(--admin-primary,#2563eb);border-radius:50%;animation:spin .7s linear infinite}

/* === View toggle === */
.view-toggle{display:inline-flex;border:1.5px solid var(--admin-border,#e5e7eb);border-radius:8px;overflow:hidden}
.view-toggle-btn{padding:5px 12px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;background:var(--admin-surface,#fff);color:var(--admin-text-secondary,#6b7280);display:inline-flex;align-items:center;gap:5px;transition:all .15s}
.view-toggle-btn:hover{background:var(--admin-surface-hover,#f3f4f6)}
.view-toggle-btn.active{background:var(--admin-primary,#2563eb);color:#fff}

/* === Grid view === */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;padding:4px 0}
.product-card{display:flex;flex-direction:column;background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e5e7eb);border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;transition:all .18s ease}
.product-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1);border-color:#c7d2fe;text-decoration:none;color:inherit}
.product-card__img-wrap{position:relative;width:100%;aspect-ratio:1;background:#f9fafb;overflow:hidden}
.product-card__img{width:100%;height:100%;object-fit:cover}
.product-card__img--empty{display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:#f3f4f6}
.product-card__status{position:absolute;top:8px;left:8px;padding:2px 8px;border-radius:12px;font-size:10px;font-weight:700;line-height:1.6}
.product-card__body{padding:12px;display:flex;flex-direction:column;gap:4px;flex:1}
.product-card__name{font-weight:600;font-size:.8125rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-card__meta{font-size:.75rem;color:var(--admin-text-secondary,#6b7280)}
.product-card__bottom{display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:8px}
.product-card__price{font-weight:700;font-size:.875rem}
.product-card__sizes{font-size:.75rem;color:var(--admin-text-secondary,#6b7280);background:var(--admin-surface-hover,#f3f4f6);padding:2px 8px;border-radius:10px}

/* === Empty state === */
.catalog-empty{padding:3rem;text-align:center}
.catalog-empty i{font-size:2.5rem;opacity:.35;display:block;margin-bottom:.75rem}
</style>

<!-- Status Funnel -->
<div class="catalog-funnel">
    <?php foreach ($funnelItems as $sKey => $sItem):
        $isAct = (string)($filterStatus ?? '') === (string)$sKey;
        $url = $sKey === '' ? Url::to(['/admin/catalog']) : Url::to(['/admin/catalog', 'status' => $sKey]);
    ?>
    <a href="<?= $url ?>" class="funnel-pill <?= $isAct ? 'funnel-pill--active' : '' ?>"<?= isset($sItem['title']) ? ' title="' . Html::encode($sItem['title']) . '"' : '' ?>>
        <span class="funnel-pill-dot" style="background:<?= $sItem['dot'] ?>"></span>
        <span class="funnel-pill-label"><?= Html::encode($sItem['label']) ?></span>
        <span class="funnel-pill-count"><?= $sItem['count'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter Bar -->
<form method="get" id="filterForm" class="filter-wrap">
    <!-- Row 1 -->
    <div class="compact-filter-bar filter-row1 <?= $row2Active ? 'has-row2' : '' ?>">
        <?php if ($filterStatus): ?>
        <input type="hidden" name="status" value="<?= Html::encode($filterStatus) ?>">
        <?php endif; ?>

        <input type="text" name="search" class="compact-filter-input"
               placeholder="&#128269; Название, артикул, SKU, штрихкод…"
               value="<?= Html::encode($filterSearch ?? '') ?>">

        <select name="brand" class="compact-filter-select">
            <option value="">Бренд ▾</option>
            <?php foreach ($brandOptions as $id => $name): ?>
                <option value="<?= $id ?>" <?= ($filterBrand ?? '') == $id ? 'selected' : '' ?>><?= Html::encode($name) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="category" class="compact-filter-select">
            <option value="">Категория ▾</option>
            <?php foreach ($categoryOptions as $id => $name): ?>
                <option value="<?= $id ?>" <?= ($filterCategory ?? '') == $id ? 'selected' : '' ?>><?= Html::encode($name) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="stock" class="compact-filter-select">
            <option value="">Наличие ▾</option>
            <option value="in"  <?= ($filterStock ?? '') === 'in' ? 'selected' : '' ?>>В наличии</option>
            <option value="out" <?= ($filterStock ?? '') === 'out' ? 'selected' : '' ?>>Нет в наличии</option>
        </select>

        <!-- Expand row 2 -->
        <button type="button" id="filterExpandBtn"
                class="compact-filter-btn compact-filter-btn--expand <?= $row2Active ? 'is-active' : '' ?>"
                onclick="toggleFilterRow2()">
            <i class="bi bi-sliders"></i> Ещё
            <?php if ($row2Count > 0): ?>
            <span style="background:var(--admin-primary,#2563eb);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700"><?= $row2Count ?></span>
            <?php endif; ?>
        </button>

        <button type="submit" class="compact-filter-btn compact-filter-btn--apply">
            <i class="bi bi-search"></i> Найти
        </button>
        <a href="<?= Url::to(['/admin/catalog']) ?>" class="compact-filter-btn compact-filter-btn--reset">
            <i class="bi bi-x-lg"></i> Сбросить
        </a>

        <!-- Column selector -->
        <div class="col-selector-wrap" style="margin-left:auto">
            <button type="button" class="compact-filter-btn" onclick="toggleColSelector(event)">
                <i class="bi bi-layout-three-columns"></i> Столбцы
            </button>
            <div id="colSelector" class="col-selector-dropdown" style="display:none">
                <div style="font-weight:700;margin-bottom:8px;font-size:.8125rem">Показать столбцы:</div>
                <?php foreach ($columnDefs as $colKey => $colLabel): ?>
                <label class="col-selector-item">
                    <input type="checkbox" data-col="<?= $colKey ?>"
                           onchange="toggleColumn('<?= $colKey ?>', this.checked)" checked>
                    <?= Html::encode($colLabel) ?>
                </label>
                <?php endforeach; ?>
                <div style="margin-top:8px;border-top:1px solid var(--admin-border,#e5e7eb);padding-top:8px;display:flex;gap:6px">
                    <button type="button" onclick="selectAllCols(true)"  class="compact-filter-btn compact-filter-btn--apply"  style="flex:1;height:26px;font-size:11px;padding:0 8px">Все</button>
                    <button type="button" onclick="selectAllCols(false)" class="compact-filter-btn compact-filter-btn--reset" style="flex:1;height:26px;font-size:11px;padding:0 8px">Скрыть</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: expandable -->
    <div id="filterRow2" class="compact-filter-bar filter-row2"
         style="<?= $row2Active ? 'display:flex' : 'display:none' ?>">
        <select name="gender" class="compact-filter-select">
            <option value="">Пол ▾</option>
            <option value="male"   <?= ($filterGender ?? '') === 'male' ? 'selected' : '' ?>>Мужской</option>
            <option value="female" <?= ($filterGender ?? '') === 'female' ? 'selected' : '' ?>>Женский</option>
            <option value="unisex" <?= ($filterGender ?? '') === 'unisex' ? 'selected' : '' ?>>Унисекс</option>
            <option value="kids"   <?= ($filterGender ?? '') === 'kids' ? 'selected' : '' ?>>Детский</option>
        </select>
        <select name="season" class="compact-filter-select">
            <option value="">Сезон ▾</option>
            <option value="winter"    <?= ($filterSeason ?? '') === 'winter' ? 'selected' : '' ?>>Зима</option>
            <option value="spring"    <?= ($filterSeason ?? '') === 'spring' ? 'selected' : '' ?>>Весна</option>
            <option value="summer"    <?= ($filterSeason ?? '') === 'summer' ? 'selected' : '' ?>>Лето</option>
            <option value="autumn"    <?= ($filterSeason ?? '') === 'autumn' ? 'selected' : '' ?>>Осень</option>
            <option value="demi"      <?= ($filterSeason ?? '') === 'demi' ? 'selected' : '' ?>>Демисезон</option>
            <option value="all"       <?= ($filterSeason ?? '') === 'all' ? 'selected' : '' ?>>Всесезон</option>
        </select>
        <select name="source" class="compact-filter-select">
            <option value="">Источник ▾</option>
            <option value="poizon" <?= ($filterSource ?? '') === 'poizon' ? 'selected' : '' ?>>Poizon</option>
            <option value="manual" <?= ($filterSource ?? '') === 'manual' ? 'selected' : '' ?>>Ручные</option>
        </select>
        <input type="number" name="price_from" class="compact-filter-input"
               placeholder="Цена от" value="<?= Html::encode($filterPriceFrom ?? '') ?>" step="0.01" min="0">
        <input type="number" name="price_to" class="compact-filter-input"
               placeholder="Цена до" value="<?= Html::encode($filterPriceTo ?? '') ?>" step="0.01" min="0">
    </div>
</form>

<!-- Main Card -->
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
        <h2 class="admin-card-title" style="margin:0;">
            <i class="bi bi-grid-3x3-gap"></i> Каталог
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6b7280);margin-left:8px"><?= $totalCount ?></span>
        </h2>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <div class="view-toggle">
                <button class="view-toggle-btn active" id="btnTable" onclick="switchView('table')">
                    <i class="bi bi-table"></i> Таблица
                </button>
                <button class="view-toggle-btn" id="btnGrid" onclick="switchView('grid')">
                    <i class="bi bi-grid-3x3-gap"></i> Сетка
                </button>
            </div>
            <a href="<?= Url::to(['/admin/product/export']) ?>" class="compact-filter-btn" style="height:30px">
                <i class="bi bi-download"></i> Экспорт
            </a>
            <a href="<?= Url::to(['/admin/product/create']) ?>" class="compact-filter-btn compact-filter-btn--apply" style="height:30px">
                <i class="bi bi-plus-lg"></i> Новый товар
            </a>
        </div>
    </div>

    <!-- TABLE VIEW -->
    <div id="tableView">
        <div style="overflow-x:auto;">
            <table class="admin-table" id="catalogTable" style="font-size:.8125rem">
                <thead>
                    <tr>
                        <th style="width:56px">Фото</th>
                        <th data-sort="name" onclick="sortBy('name')" title="Сортировать по названию">Название <?= $sortIcon('name') ?></th>
                        <th data-col="article">Артикул</th>
                        <th data-col="brand" data-sort="brand_name" onclick="sortBy('brand_name')" title="Сортировать по бренду">Бренд <?= $sortIcon('brand_name') ?></th>
                        <th data-col="price" data-sort="price" onclick="sortBy('price')" title="Сортировать по цене" style="white-space:nowrap">Цена <?= $sortIcon('price') ?></th>
                        <th data-col="sizes" style="text-align:center">Размеры</th>
                        <th data-col="stock_total" style="text-align:center">Склад</th>
                        <th data-col="status" data-sort="is_active" onclick="sortBy('is_active')" title="Сортировать по статусу">Статус <?= $sortIcon('is_active') ?></th>
                        <th style="width:44px">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <?= $this->render('_product_row', ['product' => $product]) ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="padding:0">
                                <div class="catalog-empty">
                                    <i class="bi bi-inbox"></i>
                                    <h3 style="margin:.5rem 0 .35rem">Товары не найдены</h3>
                                    <p style="margin:0;color:var(--admin-text-secondary,#6b7280)">Попробуйте изменить параметры поиска</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /tableView -->

    <!-- GRID VIEW -->
    <div id="gridView" style="display:none;">
        <?php if (!empty($products)): ?>
            <div class="product-grid" id="productGrid">
                <?php foreach ($products as $product): ?>
                    <?= $this->render('_product_card', ['product' => $product]) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="catalog-empty">
                <i class="bi bi-inbox"></i>
                <h3 style="margin:.5rem 0 .35rem">Товары не найдены</h3>
                <p style="margin:0;color:var(--admin-text-secondary,#6b7280)">Попробуйте изменить параметры поиска</p>
            </div>
        <?php endif; ?>
    </div><!-- /gridView -->

    <!-- Infinite scroll sentinel -->
    <div id="scroll-sentinel"></div>
    <div id="scroll-loading">
        <div class="scroll-spinner"></div>
        <span>Загрузка…</span>
    </div>
</div>

<script>
/* ──────────────────────────────────────────
   View toggle (table / grid)
────────────────────────────────────────── */
function switchView(mode) {
    var table = document.getElementById('tableView');
    var grid  = document.getElementById('gridView');
    var btnT  = document.getElementById('btnTable');
    var btnG  = document.getElementById('btnGrid');
    if (mode === 'grid') {
        table.style.display = 'none';
        grid.style.display  = 'block';
        btnT.classList.remove('active');
        btnG.classList.add('active');
    } else {
        table.style.display = 'block';
        grid.style.display  = 'none';
        btnT.classList.add('active');
        btnG.classList.remove('active');
    }
    try { localStorage.setItem('catalogViewMode', mode); } catch(e){}
}
/* Restore view mode */
(function() {
    try {
        var saved = localStorage.getItem('catalogViewMode');
        if (saved === 'grid') switchView('grid');
    } catch(e){}
})();

/* ──────────────────────────────────────────
   Column selector
────────────────────────────────────────── */
function toggleColSelector(e) {
    e.stopPropagation();
    var d = document.getElementById('colSelector');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    var w = document.querySelector('.col-selector-wrap');
    if (w && !w.contains(e.target)) {
        var d = document.getElementById('colSelector');
        if (d) d.style.display = 'none';
    }
});
function toggleColumn(col, show) {
    document.querySelectorAll('[data-col="' + col + '"]').forEach(function(el) {
        el.style.display = show ? '' : 'none';
    });
    var saved = JSON.parse(localStorage.getItem('catalogColumns') || '{}');
    saved[col] = show;
    localStorage.setItem('catalogColumns', JSON.stringify(saved));
}
function selectAllCols(show) {
    document.querySelectorAll('#colSelector input[type=checkbox]').forEach(function(cb) {
        cb.checked = show;
        toggleColumn(cb.dataset.col, show);
    });
}
/* Restore column visibility */
(function() {
    var saved = JSON.parse(localStorage.getItem('catalogColumns') || '{}');
    Object.entries(saved).forEach(function(entry) {
        var col = entry[0], show = entry[1];
        if (show === false) {
            var cb = document.querySelector('#colSelector input[data-col="' + col + '"]');
            if (cb) {
                cb.checked = false;
                toggleColumn(col, false);
            }
        }
    });
})();

/* ──────────────────────────────────────────
   Expandable filter row 2
────────────────────────────────────────── */
function toggleFilterRow2() {
    var r2  = document.getElementById('filterRow2');
    var btn = document.getElementById('filterExpandBtn');
    var row1 = document.querySelector('.filter-row1');
    var open = r2.style.display !== 'none';
    r2.style.display = open ? 'none' : 'flex';
    btn.classList.toggle('is-active', !open);
    row1.classList.toggle('has-row2', !open);
}

/* ──────────────────────────────────────────
   Sortable columns
────────────────────────────────────────── */
function sortBy(col) {
    var url = new URL(window.location.href);
    var cur = url.searchParams.get('sort') || '';
    url.searchParams.set('sort', cur === col ? ('-' + col) : col);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

/* ──────────────────────────────────────────
   Infinite scroll
────────────────────────────────────────── */
(function() {
    var loading  = false;
    var hasMore  = <?= $pagination->pageCount > 1 ? 'true' : 'false' ?>;
    var nextPage = 2;

    var sentinel = document.getElementById('scroll-sentinel');
    var spinner  = document.getElementById('scroll-loading');

    if (!hasMore || !sentinel) return;

    function applyHiddenColumns(rows) {
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem('catalogColumns') || '{}'); } catch(e){}
        Object.keys(saved).forEach(function(col) {
            if (saved[col] === false) {
                rows.forEach(function(row) {
                    row.querySelectorAll('[data-col="' + col + '"]').forEach(function(el) {
                        el.style.display = 'none';
                    });
                });
            }
        });
    }

    function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        if (spinner) spinner.style.display = 'flex';

        var url = new URL(window.location.href);
        url.searchParams.set('scroll', '1');
        url.searchParams.set('page', nextPage);

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (spinner) spinner.style.display = 'none';
            loading = false;

            // Append table rows
            if (data.rows) {
                var tbody = document.querySelector('#catalogTable tbody');
                var temp  = document.createElement('table');
                temp.innerHTML = '<tbody>' + data.rows + '</tbody>';
                var newRows = Array.from(temp.querySelector('tbody').children);
                newRows.forEach(function(row) { tbody.appendChild(row); });
                applyHiddenColumns(newRows);
            }

            // Append grid cards
            if (data.cards) {
                var grid = document.getElementById('productGrid');
                if (grid) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.cards;
                    Array.from(tempDiv.children).forEach(function(card) {
                        grid.appendChild(card);
                    });
                }
            }

            hasMore  = !!data.hasMore;
            nextPage = data.nextPage || (nextPage + 1);

            if (!hasMore) sentinel.style.display = 'none';
        })
        .catch(function() {
            loading = false;
            if (spinner) spinner.style.display = 'none';
        });
    }

    if (typeof IntersectionObserver !== 'undefined') {
        new IntersectionObserver(function(entries) {
            entries.forEach(function(e) { if (e.isIntersecting) loadMore(); });
        }, { rootMargin: '300px' }).observe(sentinel);
    } else {
        window.addEventListener('scroll', function() {
            var r = sentinel.getBoundingClientRect();
            if (r.top < window.innerHeight + 400) loadMore();
        }, { passive: true });
    }
})();

/* ──────────────────────────────────────────
   Click row to open product
────────────────────────────────────────── */
(function() {
    var tbody = document.querySelector('#catalogTable tbody');
    if (!tbody) return;
    tbody.addEventListener('click', function(e) {
        if (e.target.closest('a, button, input, select, label, [onclick]')) return;
        var row = e.target.closest('tr');
        if (!row || !row.dataset.href) return;
        window.location.href = row.dataset.href;
    });
})();
</script>
