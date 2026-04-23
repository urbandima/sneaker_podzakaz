<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product[] $products */
/** @var yii\data\Pagination $pagination */
/** @var array $filters */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\backend\shared\components\AssetOptimizer;
use app\frontend\assets\CatalogAsset;

// Подключаем AssetBundle для каталога (все стили автоматически с версионированием)
CatalogAsset::register($this);

$this->title = isset($h1) ? $h1 : 'Каталог товаров';
$this->registerMetaTag(['name' => 'description', 'content' => 'Оригинальные товары из США и Европы']);

// Измерение производительности (только в dev режиме)
if (YII_ENV_DEV) {
    AssetOptimizer::measurePerformance($this);
}

// Инициализация Lazy Load
$this->registerJs("
// Инициализация lazy load после загрузки DOM
setTimeout(function() {
    // Проверяем доступен ли LazyLoad класс
    if (window.LazyLoad && typeof window.LazyLoad.init === 'function') {
        window.LazyLoad.init();
    } else {
        // Fallback: простая загрузка изображений
        const images = document.querySelectorAll('img[data-src]');
        images.forEach((img) => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.classList.add('lazy-loaded');
            }
        });
    }

    // Управление skeleton grid
    const skeletonGrid = document.getElementById('skeletonGrid');
    const productsContainer = document.getElementById('products');

    if (skeletonGrid && productsContainer) {
        // Показываем skeleton при загрузке
        skeletonGrid.classList.remove('skeleton-grid--hidden');

        // Скрываем skeleton когда товары загружены
        setTimeout(() => {
            skeletonGrid.classList.add('skeleton-grid--hidden');
        }, 1000);
    }
}, 200);

// Load More Logic
function loadMoreProducts() {
    const btn = document.getElementById('btnLoadMore');
    if (!btn) return;
    
    let currentPage = parseInt(btn.dataset.page);
    let totalPages = parseInt(btn.dataset.total);
    
    if (currentPage >= totalPages) return;
    
    const nextPage = currentPage + 1;
    btn.innerHTML = '<i class=\"bi bi-arrow-repeat spinner\"></i> Загрузка...';
    btn.disabled = true;
    
    // Получаем текущий URL и добавляем/меняем параметр page
    const url = new URL(window.location.href);
    url.searchParams.set('page', nextPage);
    
    fetch(url.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.html) {
            const container = document.getElementById('products');
            container.insertAdjacentHTML('beforeend', data.html);
            
            btn.dataset.page = nextPage;
            btn.innerHTML = 'Показать ещё';
            btn.disabled = false;
            
            if (nextPage >= totalPages) {
                btn.style.display = 'none';
            }
        }
    });
}
", \yii\web\View::POS_READY);
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <ul class="breadcrumb">
            <li><a href="/">Главная</a></li>
            <li><a href="/catalog">Каталог</a></li>
            <?php if (isset($h1) && $h1 !== 'Каталог товаров' && $h1 !== 'Каталог'): ?>
                <li class="active"><?= Html::encode($h1) ?></li>
            <?php endif; ?>
        </ul>

        <div class="catalog-layout">
            <!-- Sidebar -->
            <aside class="catalog-sidebar" id="sidebar">
                <div class="sidebar-header">
                    <p class="sidebar-title" role="heading" aria-level="2">Фильтры</p>
                    <button type="button" class="sidebar-close" onclick="closeFilters()" aria-label="Закрыть фильтры">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Price (открыт по умолчанию) -->
                <div class="filter-group open" id="filter-price">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="true">
                        <span>Цена</span>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="filter-content filter-content--open">
                        <div class="price-slider-container">
                            <div class="price-range-slider">
                                <input type="range" id="price-slider-min"
                                       min="<?= $filters['priceRange']['min'] ?? 0 ?>" max="<?= $filters['priceRange']['max'] ?? 10000 ?>"
                                       value="<?= $currentFilters['price_from'] ?? ($filters['priceRange']['min'] ?? 0) ?>"
                                       class="slider-thumb slider-thumb-min"
                                       aria-label="Минимальная цена" aria-valuemin="<?= $filters['priceRange']['min'] ?? 0 ?>" aria-valuemax="<?= $filters['priceRange']['max'] ?? 10000 ?>">
                                <input type="range" id="price-slider-max"
                                       min="<?= $filters['priceRange']['min'] ?? 0 ?>" max="<?= $filters['priceRange']['max'] ?? 10000 ?>"
                                       value="<?= $currentFilters['price_to'] ?? ($filters['priceRange']['max'] ?? 10000) ?>"
                                       class="slider-thumb slider-thumb-max"
                                       aria-label="Максимальная цена" aria-valuemin="<?= $filters['priceRange']['min'] ?? 0 ?>" aria-valuemax="<?= $filters['priceRange']['max'] ?? 10000 ?>">
                                <div class="slider-track" aria-hidden="true"></div>
                                <div class="slider-range" id="slider-range" aria-hidden="true"></div>
                            </div>
                            <div class="price-filter">
                                <label for="price-from" class="sr-only">Цена от</label>
                                <input type="number" class="price-input" id="price-from" name="price_from"
                                       aria-label="Цена от, BYN"
                                       value="<?= $currentFilters['price_from'] ?? ($filters['priceRange']['min'] ?? 0) ?>">
                                <span class="price-separator" aria-hidden="true">—</span>
                                <label for="price-to" class="sr-only">Цена до</label>
                                <input type="number" class="price-input" id="price-to" name="price_to"
                                       aria-label="Цена до, BYN"
                                       value="<?= $currentFilters['price_to'] ?? ($filters['priceRange']['max'] ?? 10000) ?>">
                                <span class="price-currency" aria-hidden="true">BYN</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- В наличии -->
                <div class="filter-group open" id="filter-stock">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="true">
                        <span>Наличие</span>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="filter-content filter-content--open">
                        <label class="filter-checkbox">
                            <input type="checkbox" name="in_stock" value="1" <?= !empty($currentFilters['in_stock']) ? 'checked' : '' ?>>
                            <span class="checkbox-mark"></span>
                            <span>Только в наличии</span>
                        </label>
                    </div>
                </div>

                <!-- Brands (открыт по умолчанию) -->
                <div class="filter-group open" id="filter-brands">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="true">
                        <span>Бренд</span>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="filter-content filter-content--open">
                        <div class="filter-scroll">
                            <?php if (!empty($filters['brands'])): foreach ($filters['brands'] as $brand): ?>
                                <label class="filter-checkbox <?= $brand['count'] == 0 ? 'disabled' : '' ?>">
                                    <input type="checkbox" 
                                           name="brands[]" 
                                           value="<?= $brand['id'] ?>" 
                                           data-slug="<?= $brand['slug'] ?>"
                                           <?= in_array($brand['id'], $currentFilters['brands'] ?? []) ? 'checked' : '' ?>
                                           <?= $brand['count'] == 0 ? 'disabled' : '' ?>>
                                    <span class="checkbox-mark"></span>
                                    <span><?= Html::encode($brand['name']) ?></span>
                                    <span class="filter-count"><?= $brand['count'] ?></span>
                                </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Categories (аккордеон) -->
                <div class="filter-group">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="true">
                        <span>Категория</span>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="filter-content">
                        <div class="filter-scroll">
                            <?php if (!empty($filters['categories'])): foreach ($filters['categories'] as $cat): ?>
                                <?php $catCount = isset($cat['count']) ? $cat['count'] : (isset($cat['products_count']) ? $cat['products_count'] : 0); ?>
                                <label class="filter-checkbox <?= $catCount == 0 ? 'disabled' : '' ?>">
                                    <input type="checkbox" 
                                           name="categories[]" 
                                           value="<?= $cat['id'] ?>" 
                                           data-slug="<?= $cat['slug'] ?>"
                                           <?= in_array($cat['id'], $currentFilters['categories'] ?? []) ? 'checked' : '' ?>
                                           <?= $catCount == 0 ? 'disabled' : '' ?>>
                                    <span class="checkbox-mark"></span>
                                    <span><?= Html::encode($cat['name']) ?></span>
                                    <span class="filter-count"><?= $catCount ?></span>
                                </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- PRIMARY FILTERS END -->

                <!-- Размеры (первичный фильтр — всегда видимый) -->
                <div class="filter-group open" id="filter-sizes">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="true">
                        <span>Размер <span id="sidebarSizeSystem" class="size-system-label">EU</span></span>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="filter-content filter-content--open">
                        <!-- Переключатель систем в сайдбаре -->
                        <div class="size-system-toggle-sidebar">
                            <button type="button" class="size-system-btn-small active" data-system="eu" onclick="switchSidebarSizeSystem('eu')">EU</button>
                            <button type="button" class="size-system-btn-small" data-system="us" onclick="switchSidebarSizeSystem('us')">US</button>
                            <button type="button" class="size-system-btn-small" data-system="uk" onclick="switchSidebarSizeSystem('uk')">UK</button>
                            <button type="button" class="size-system-btn-small" data-system="cm" onclick="switchSidebarSizeSystem('cm')">CM</button>
                        </div>

                        <?php
                        $staticSizes = [
                            'eu' => ['36','37','38','39','40','41','42','43','44','45','46'],
                            'us' => ['4','5','6','7','8','9','10','11','12','13'],
                            'uk' => ['3.5','4','4.5','5','5.5','6','6.5','7','7.5','8','8.5','9','9.5','10','10.5','11','11.5','12'],
                            'cm' => ['22','23','24','25','26','27','28','29','30'],
                        ];
                        foreach ($staticSizes as $system => $sizes): ?>
                            <div class="size-filter-grid sidebar-size-grid" data-system="<?= $system ?>">
                                <?php foreach ($sizes as $size): ?>
                                    <label class="size-filter-btn">
                                        <input type="checkbox" name="sizes[]" value="<?= Html::encode($size) ?>" data-system="<?= $system ?>">
                                        <span><?= Html::encode($size) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ВАЖНЫЕ ХАРАКТЕРИСТИКИ (Пол, Сезон) в основной секции -->
                <?php if (!empty($filters['characteristics'])): foreach ($filters['characteristics'] as $characteristic): ?>
                    <?php if (in_array($characteristic['key'], ['gender', 'season'])): ?>
                        <?= $this->render('_characteristic_filter', [
                            'characteristic' => $characteristic,
                            'currentFilters' => $currentFilters,
                        ]) ?>
                    <?php endif; ?>
                <?php endforeach; endif; ?>

                <!-- ADVANCED FILTERS (скрыты по умолчанию) -->
                <div class="advanced-filters-wrapper" id="advancedFiltersWrapper" style="display:none">
                
                <!-- Цвет -->
                <?php if (!empty($filters['colors'])): ?>
                <div class="filter-group">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="false">
                        <span><i class="bi bi-palette"></i> Цвет</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="filter-content">
                        <div class="color-filter-grid">
                            <?php foreach ($filters['colors'] as $color): ?>
                                <?php 
                                $count = $color['count'] ?? 0;
                                $hex = $color['hex'] ?? '#cccccc';
                                $name = $color['name'] ?? 'Неизвестный';
                                $isChecked = in_array($name, $currentFilters['colors'] ?? []);
                                ?>
                                <label class="color-filter-item <?= $count == 0 ? 'disabled' : '' ?>">
                                    <input type="checkbox" 
                                           name="colors[]" 
                                           value="<?= Html::encode($name) ?>"
                                           data-hex="<?= Html::encode($hex) ?>"
                                           <?= $isChecked ? 'checked' : '' ?>
                                           <?= $count == 0 ? 'disabled' : '' ?>>
                                    <div class="color-circle" style="background: <?= Html::encode($hex) ?>"></div>
                                    <span class="color-name"><?= Html::encode($name) ?></span>
                                    <span class="count">(<?= $count ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Скидка (как на OZON/Wildberries) -->
                <div class="filter-group">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="false">
                        <span>Скидка</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="filter-content">
                        <label class="filter-item">
                            <input type="checkbox" name="discount_any" value="1">
                            <span>Товары со скидкой</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="discount_range[]" value="0-30">
                            <span>До 30%</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="discount_range[]" value="30-50">
                            <span>30% - 50%</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="discount_range[]" value="50-100">
                            <span>Более 50%</span>
                        </label>
                    </div>
                </div>
                
                <!-- Рейтинг (как на OZON/Yandex Market) -->
                <div class="filter-group">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="false">
                        <span>Рейтинг</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="filter-content">
                        <label class="filter-item">
                            <input type="radio" name="rating" value="4">
                            <span><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> и выше</span>
                        </label>
                        <label class="filter-item">
                            <input type="radio" name="rating" value="3">
                            <span><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> и выше</span>
                        </label>
                    </div>
                </div>
                
                <!-- Условия (как на всех топовых) -->
                <div class="filter-group">
                    <button type="button" class="filter-title" onclick="toggleFilterGroup(this)" aria-expanded="false">
                        <span>Условия</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="filter-content">
                        <?php if (!empty($filters['conditions'])): foreach ($filters['conditions'] as $condition): ?>
                            <label class="filter-item">
                                <input type="checkbox" name="conditions[]" value="<?= $condition['value'] ?>">
                                <span><?php if (!empty($condition['icon'])): ?><i class="bi <?= $condition['icon'] ?>"></i> <?php endif; ?><?= Html::encode($condition['label']) ?></span>
                            </label>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                
                <!-- ДИНАМИЧЕСКИЕ ХАРАКТЕРИСТИКИ (кроме Пола и Сезона - они выше) -->
                <?php if (!empty($filters['characteristics'])): foreach ($filters['characteristics'] as $characteristic): ?>
                    <?php if (!in_array($characteristic['key'], ['gender', 'season'])): ?>
                        <?= $this->render('_characteristic_filter', [
                            'characteristic' => $characteristic,
                            'currentFilters' => $currentFilters,
                        ]) ?>
                    <?php endif; ?>
                <?php endforeach; else: ?>
                    <!-- Fallback: если характеристик нет -->
                    <div class="filter-info-notice">
                        <i class="bi bi-info-circle"></i>
                        <strong>Характеристики загружаются...</strong>
                        <p>Добавьте характеристики товаров в админ-панели для расширенной фильтрации.</p>
                    </div>
                <?php endif; ?>
                
                </div><!-- END advanced-filters-wrapper -->
                
                <!-- Кнопка "Показать расширенные фильтры" -->
                <?php 
                $advancedCount = 2; // Скидка, Рейтинг
                // Характеристики (кроме Пола и Сезона, которые в основной секции)
                if (!empty($filters['characteristics'])) {
                    foreach ($filters['characteristics'] as $char) {
                        if (!in_array($char['key'], ['gender', 'season'])) {
                            $advancedCount++;
                        }
                    }
                }
                ?>
                <button type="button" class="show-advanced-filters-btn" id="showAdvancedBtn" onclick="toggleAdvancedFilters()">
                    <i class="bi bi-sliders"></i>
                    <span>Расширенные фильтры</span>
                    <span class="count">(<?= $advancedCount ?>)</span>
                    <i class="bi bi-chevron-down toggle-icon"></i>
                </button>

                <!-- Кнопки управления фильтрами -->
                <div class="filter-actions">
                    <button type="button" class="btn btn-primary btn-filter-apply" onclick="event.preventDefault(); event.stopPropagation(); applyFilters();">
                        <i class="bi bi-check-circle"></i>
                        Применить
                    </button>
                    <button type="button" class="btn btn-outline btn-filter-reset" onclick="event.preventDefault(); event.stopPropagation(); resetFilters();">
                        <i class="bi bi-x-circle"></i>
                        Сбросить
                    </button>
                </div>
                
            </aside>

            <!-- Content -->
            <main class="content">
                <!-- Кнопка открытия фильтра (Desktop) -->
                <button type="button" class="filter-toggle-btn" onclick="toggleFilters()">
                    <i class="bi bi-sliders"></i>
                    <span>Фильтры</span>
                </button>

                <div class="content-header">
                    <h1><?= isset($h1) ? Html::encode($h1) : 'Каталог' ?> <span class="products-count">(<span id="productsCount"><?= $pagination->totalCount ?></span>)</span></h1>
                    <?php if (isset($description) && !empty($description)): ?>
                    <div class="category-description">
                        <p><?= Html::encode($description) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Filters: Бренды -->
                <div class="quick-filters-bar">
                    <?php 
                    // Топ-6 популярных брендов для быстрого доступа
                    $topBrands = !empty($filters['brands']) ? array_slice($filters['brands'], 0, 6) : [];
                    foreach ($topBrands as $brand): 
                        if ($brand['count'] > 0): 
                            $isActive = in_array($brand['id'], $currentFilters['brands'] ?? []); ?>
                        <button type="button" class="quick-chip brand-chip <?= $isActive ? 'active' : '' ?>" 
                                data-brand="<?= $brand['id'] ?>" 
                                onclick="toggleBrandFilter(<?= $brand['id'] ?>, '<?= Html::encode($brand['slug']) ?>')">
                            <span><?= Html::encode($brand['name']) ?></span>
                            <span class="chip-count"><?= $brand['count'] ?></span>
                        </button>
                    <?php endif; endforeach; ?>
                </div>
                
                <!-- Quick Filters: Размеры с переключателем систем -->
                <div class="quick-filters-sizes">
                    <div class="size-system-toggle">
                        <button type="button" class="size-system-btn active" data-system="eu" onclick="switchSizeSystem('eu')">EU</button>
                        <button type="button" class="size-system-btn" data-system="us" onclick="switchSizeSystem('us')">US</button>
                        <button type="button" class="size-system-btn" data-system="uk" onclick="switchSizeSystem('uk')">UK</button>
                        <button type="button" class="size-system-btn" data-system="cm" onclick="switchSizeSystem('cm')">CM</button>
                    </div>
                    
                    <!-- Wrapper для размеров и стрелок -->
                    <div class="sizes-with-nav">
                        <!-- Кнопка прокрутки влево -->
                        <button type="button" class="size-nav-btn size-nav-left" onclick="scrollSizes('left')">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        
                        <div class="sizes-scroll-container" id="sizesScrollContainer">
                            <?php
                            $quickSizes = [
                                'eu' => ['36','37','38','39','40','41','42','43','44','45','46'],
                                'us' => ['4','5','6','7','8','9','10','11','12','13'],
                                'uk' => ['3.5','4','4.5','5','5.5','6','6.5','7','7.5','8','8.5','9','9.5','10','10.5','11','11.5','12'],
                                'cm' => ['22','23','24','25','26','27','28','29','30'],
                            ];
                            foreach ($quickSizes as $system => $sizes): ?>
                                <div class="size-group" data-system="<?= $system ?>">
                                    <?php foreach ($sizes as $size): ?>
                                        <button type="button" class="quick-chip size-chip"
                                                data-size="<?= Html::encode($size) ?>"
                                                data-system="<?= $system ?>"
                                                onclick="toggleSizeFilter('<?= Html::encode($size) ?>', '<?= $system ?>')">
                                            <span><?= Html::encode($size) ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Кнопка прокрутки вправо -->
                        <button type="button" class="size-nav-btn size-nav-right" onclick="scrollSizes('right')">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Toolbar -->
                <div class="catalog-toolbar">
                    <h1 class="toolbar-title"><?= isset($h1) ? Html::encode($h1) : 'Каталог' ?> <span class="filter-count">(<?= $pagination->totalCount ?>)</span></h1>
                    
                    <div class="toolbar-actions">
                        <select class="sort-select" id="sortSelect" onchange="applySort(this.value)">
                            <option value="popular" <?= ($currentFilters['sort'] ?? 'popular') === 'popular' ? 'selected' : '' ?>>По популярности</option>
                            <option value="price_asc" <?= ($currentFilters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Сначала дешевые</option>
                            <option value="price_desc" <?= ($currentFilters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Сначала дорогие</option>
                            <option value="new" <?= ($currentFilters['sort'] ?? '') === 'new' ? 'selected' : '' ?>>Новинки</option>
                            <option value="rating" <?= ($currentFilters['sort'] ?? '') === 'rating' ? 'selected' : '' ?>>По рейтингу</option>
                            <option value="discount" <?= ($currentFilters['sort'] ?? '') === 'discount' ? 'selected' : '' ?>>Со скидкой</option>
                        </select>

                        <select class="per-page-select" id="perPageSelect" onchange="applyPerPage(this.value)">
                            <option value="4" <?= ($pagination->pageSize ?? 4) == 4 ? 'selected' : '' ?>>4 товара</option>
                            <option value="8" <?= ($pagination->pageSize ?? 4) == 8 ? 'selected' : '' ?>>8 товаров</option>
                            <option value="12" <?= ($pagination->pageSize ?? 4) == 12 ? 'selected' : '' ?>>12 товаров</option>
                        </select>

                        <div class="view-toggle" role="group" aria-label="Вид отображения">
                            <button class="view-btn active" data-view="grid" onclick="switchView('grid')" aria-label="Плиткой" aria-pressed="true">
                                <i class="bi bi-grid-fill" aria-hidden="true"></i>
                            </button>
                            <button class="view-btn" data-view="list" onclick="switchView('list')" aria-label="Списком" aria-pressed="false">
                                <i class="bi bi-list-ul" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($activeFilters)): ?>
                <div class="active-filters">
                    <?php foreach ($activeFilters as $filter): ?>
                        <div class="filter-tag">
                            <?= Html::encode($filter['label']) ?>
                            <a href="<?= $filter['removeUrl'] ?>" class="filter-tag-remove"><i class="bi bi-x"></i></a>
                        </div>
                    <?php endforeach; ?>
                    <a href="/catalog/" class="filter-clear-all">Очистить все</a>
                </div>
                <?php endif; ?>

                <div class="products-grid" id="products">
                    <?= $this->render('_products', ['products' => $products]) ?>
                </div>

                <!-- Load More Button -->
                <?php if ($pagination->pageCount > 1 && $pagination->page < $pagination->pageCount - 1): ?>
                <div class="load-more-container">
                    <button type="button" class="btn btn-secondary btn-load-more" 
                            id="btnLoadMore"
                            data-page="<?= $pagination->page ?>" 
                            data-total="<?= $pagination->pageCount ?>"
                            onclick="loadMoreProducts()">
                        Показать ещё
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Статическая пагинация для краулеров -->
                <div class="static-pagination" style="display: none;">
                    <?php
                    $baseUrl = Yii::$app->request->baseUrl;
                    $currentPath = Yii::$app->request->getPathInfo();
                    $queryParams = $_GET;
                    unset($queryParams['page']); // Убираем page из query params
                    
                    $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
                    $pageUrl = $baseUrl . '/' . $currentPath . $queryString;
                    
                    // Генерируем ссылки на все страницы
                    for ($i = 1; $i <= $pagination->pageCount; $i++):
                        $url = $pageUrl . (strpos($pageUrl, '?') !== false ? '&page=' : '?page=') . $i;
                        $rel = '';
                        if ($i == 1) $rel = ' rel="first"';
                        if ($i == $pagination->pageCount) $rel .= ' rel="last"';
                        if ($i == $pagination->page + 1) $rel .= ' rel="next"';
                        if ($i == $pagination->page - 1) $rel .= ' rel="prev"';
                    ?>
                        <a href="<?= Html::encode($url) ?>"<?= $rel ?>>Страница <?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Статическая пагинация для SEO-краулеров -->
<script>
// Определяем, является ли пользователь поисковым краулером
function isCrawler() {
    const crawlers = [
        'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandexbot', 'facebookexternalhit', 'twitterbot', 'rogerbot',
        'linkedinbot', 'embedly', 'quora link preview', 'showyoubot',
        'outbrain', 'pinterest', 'developers.google.com', 'slackbot',
        'vkshare', 'w3c_validator', 'redditbot', 'applebot'
    ];
    
    const userAgent = navigator.userAgent.toLowerCase();
    return crawlers.some(crawler => userAgent.includes(crawler));
}

// Показываем статическую пагинацию только для краулеров
if (isCrawler()) {
    const staticPagination = document.querySelector('.static-pagination');
    if (staticPagination) {
        staticPagination.style.display = 'block';
    }
}
</script>

<div class="overlay sidebar-overlay" id="overlay"></div>

<!-- Quick View Modal -->
<div class="quick-view-modal" id="quickViewModal">
    <div class="qv-content">
        <button type="button" class="qv-close" onclick="closeQuickView()"><i class="bi bi-x"></i></button>
        <div class="qv-grid">
            <div class="qv-gallery">
                <img src="" alt="" id="qvMainImg">
                <div class="qv-thumbs" id="qvThumbs"></div>
            </div>
            <div class="qv-details">
                <div class="qv-brand" id="qvBrand"></div>
                <h2 id="qvName"></h2>
                <div class="qv-price" id="qvPrice"></div>
                <div class="qv-sizes" id="qvSizes"></div>
                <div class="qv-colors" id="qvColors"></div>
                <button type="button" class="btn-order" onclick="addToCart()"><i class="bi bi-cart-plus"></i> В корзину</button>
                <a href="#" id="qvLink" class="qv-full">Подробнее →</a>
            </div>
        </div>
    </div>
</div>

<!-- ОПТИМИЗАЦИЯ: Inline стили вынесены в /web/css/catalog-inline.css для улучшения производительности -->

<script>
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('overlay');

function toggleFilters(){
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Блокировка скролла body когда sidebar открыт
    if(sidebar.classList.contains('active')){
        document.body.style.overflow='hidden';
    } else {
        document.body.style.overflow='';
    }
}
window.toggleFilters = toggleFilters;

// Закрытие по клику на overlay
overlay?.addEventListener('click', toggleFilters);

// Закрытие по ESC
document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape' && sidebar.classList.contains('active')){
        toggleFilters();
    }
});

// УДАЛЕНО: toggleFav, resetFilters, toggleBrandFilter перенесены в global-helpers.js для устранения дублирования

// КРИТИЧНО: Определяем функции ДО использования в HTML

// Переключение расширенных фильтров
function toggleAdvancedFilters() {
    const wrapper = document.getElementById('advancedFiltersWrapper');
    const button = document.getElementById('showAdvancedBtn');
    
    if (wrapper.style.display === 'none' || !wrapper.style.display) {
        wrapper.style.display = 'block';
        button.classList.add('active');
        button.querySelector('span:nth-child(2)').textContent = 'Скрыть расширенные';
    } else {
        wrapper.style.display = 'none';
        button.classList.remove('active');
        button.querySelector('span:nth-child(2)').textContent = 'Расширенные фильтры';
    }
}
window.toggleAdvancedFilters = toggleAdvancedFilters;

// Аккордеон фильтров
function toggleFilterGroup(titleEl) {
    const group = titleEl.closest('.filter-group');
    const content = group.querySelector('.filter-content');
    const icon = titleEl.querySelector('.bi-chevron-down, .bi-chevron-up');
    
    if (group.classList.contains('open')) {
        group.classList.remove('open');
        if (content) content.style.display = 'none';
        if (icon) {
            icon.classList.remove('bi-chevron-up');
            icon.classList.add('bi-chevron-down');
        }
    } else {
        group.classList.add('open');
        if (content) content.style.display = 'block';
        if (icon) {
            icon.classList.remove('bi-chevron-down');
            icon.classList.add('bi-chevron-up');
        }
    }
}
window.toggleFilterGroup = toggleFilterGroup;

// НОВОЕ: Переключение размерных сеток в quick-filters
function switchSizeSystem(system) {
    // Сохраняем выбор в localStorage
    localStorage.setItem('preferredSizeSystem', system);
    // Синхронизируем отображение названия системы в сайдбаре
    const sidebarLabel = document.getElementById('sidebarSizeSystem');
    if (sidebarLabel) {
        sidebarLabel.textContent = system.toUpperCase();
    }

    // Обновляем состояние кнопок в сайдбаре
    document.querySelectorAll('.size-system-btn-small').forEach(btn => {
        btn.classList.remove('active');
    });
    const sidebarBtn = document.querySelector(`.size-system-btn-small[data-system="${system}"]`);
    if (sidebarBtn) {
        sidebarBtn.classList.add('active');
    }

    // Переключаем отображение сеток размеров в сайдбаре
    document.querySelectorAll('.sidebar-size-grid').forEach(grid => {
        grid.style.display = grid.dataset.system === system ? 'grid' : 'none';
    });

    // Сбрасываем горизонтальную прокрутку и обновляем стрелки
    const quickSizesContainer = document.getElementById('sizesScrollContainer');
    if (quickSizesContainer) {
        quickSizesContainer.scrollLeft = 0;
    }
    
    // Переключаем активную кнопку
    document.querySelectorAll('.size-system-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.size-system-btn[data-system="${system}"]`).classList.add('active');
    
    // Показываем/скрываем группы размеров
    document.querySelectorAll('.size-group').forEach(group => {
        group.style.display = group.dataset.system === system ? '' : 'none';
    });
    
    // Синхронизируем active состояние чипов с выбранными чекбоксами для ТЕКУЩЕЙ системы
    document.querySelectorAll('.size-chip').forEach(chip => {
        chip.classList.remove('active');
        
        const chipSystem = chip.dataset.system;
        const chipSize = chip.dataset.size;
        
        if (chipSystem === system) {
            // Проверяем, выбран ли соответствующий чекбокс в sidebar
            const checkbox = document.querySelector(
                `.sidebar input[name="sizes[]"][value="${chipSize}"][data-system="${chipSystem}"]`
            );
            if (checkbox && checkbox.checked) {
                chip.classList.add('active');
            }
        }
    });
    
    // Обновляем видимость стрелок после смены системы (с задержкой для рендеринга)
    setTimeout(() => {
        if (typeof updateScrollButtons === 'function') {
            updateScrollButtons();
        }
    }, 100);
    
    // НЕ применяем фильтры автоматически - только при нажатии "Применить"
}
window.switchSizeSystem = switchSizeSystem;

// Переключение системы в sidebar
function switchSidebarSizeSystem(system) {
    // Сохраняем выбор в localStorage
    localStorage.setItem('preferredSizeSystem', system);
    
    // Обновляем текст в заголовке
    const sidebarLabel = document.getElementById('sidebarSizeSystem');
    if (sidebarLabel) {
        sidebarLabel.textContent = system.toUpperCase();
    }
    
    // Переключаем активную кнопку
    document.querySelectorAll('.size-system-btn-small').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.size-system-btn-small[data-system="${system}"]`).classList.add('active');
    
    // Показываем/скрываем grid размеров
    document.querySelectorAll('.sidebar-size-grid').forEach(grid => {
        grid.style.display = grid.dataset.system === system ? 'grid' : 'none';
    });
    
    // Синхронизируем с quick-filters
    document.querySelectorAll('.size-system-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const quickBtn = document.querySelector(`.size-system-btn[data-system="${system}"]`);
    if (quickBtn) quickBtn.classList.add('active');
    document.querySelectorAll('.size-group').forEach(group => {
        group.style.display = group.dataset.system === system ? '' : 'none';
    });
    
    // Синхронизируем active состояние чипов с выбранными чекбоксами для ТЕКУЩЕЙ системы
    document.querySelectorAll('.size-chip').forEach(chip => {
        chip.classList.remove('active');
        
        const chipSystem = chip.dataset.system;
        const chipSize = chip.dataset.size;
        
        if (chipSystem === system) {
            // Проверяем, выбран ли соответствующий чекбокс в sidebar
            const checkbox = document.querySelector(
                `.sidebar input[name="sizes[]"][value="${chipSize}"][data-system="${chipSystem}"]`
            );
            if (checkbox && checkbox.checked) {
                chip.classList.add('active');
            }
        }
    });
    
    // Обновляем видимость стрелок навигации
    setTimeout(() => {
        if (typeof updateScrollButtons === 'function') {
            updateScrollButtons();
        }
    }, 100);
}

const quickSizesContainer = document.getElementById('sizesScrollContainer');
if (quickSizesContainer) {
    quickSizesContainer.scrollLeft = 0;
}

// Фильтр по размерам (оптимизирован)
function toggleSizeFilter(size, system) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const button = event.currentTarget;
    const isActive = button.classList.contains('active');
    
    button.classList.toggle('active');
    
    // Находим соответствующий чекбокс в sidebar для данной системы
    const checkbox = document.querySelector(
        `.sidebar input[name="sizes[]"][value="${size}"][data-system="${system}"]`
    );
    
    if (checkbox) {
        checkbox.checked = !isActive;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    // Автоматически запускаем AJAX-фильтрацию для мгновенного отклика
}
window.toggleSizeFilter = toggleSizeFilter;

// Прокрутка размеров стрелками (для десктопа)
function scrollSizes(direction) {
    const container = document.getElementById('sizesScrollContainer');
    if (!container) return;
    
    const scrollAmount = 200; // Прокрутка на 200px
    
    if (direction === 'left') {
        container.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else {
        container.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
    
    // Обновляем видимость стрелок после прокрутки
    setTimeout(() => updateScrollButtons(), 300);
}

// Проверка необходимости отображения стрелок
function updateScrollButtons() {
    const container = document.getElementById('sizesScrollContainer');
    const leftBtn = document.querySelector('.size-nav-left');
    const rightBtn = document.querySelector('.size-nav-right');
    
    if (!container || !leftBtn || !rightBtn) {
        return;
    }

    // Проверяем, есть ли переполнение (контент шире контейнера)
    const hasOverflow = container.scrollWidth > container.clientWidth;

    if (!hasOverflow) {
        leftBtn.style.display = 'none';
        rightBtn.style.display = 'none';
        return;
    }

    // Показываем/скрываем стрелки в зависимости от позиции прокрутки
    const isAtStart = container.scrollLeft <= 5;
    const isAtEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 5;

    leftBtn.style.display = isAtStart ? 'none' : 'flex';
    rightBtn.style.display = isAtEnd ? 'none' : 'flex';
}

// Проверка переполнения контейнера размеров
function checkSizesOverflow() {
    const container = document.getElementById('sizesScrollContainer');
    if (!container) return;
    
    // Обновляем видимость стрелок
    updateScrollButtons();
    
    // Добавляем слушатель события прокрутки для динамического обновления стрелок (только один раз)
    if (!container.dataset.scrollListenerAdded) {
        container.addEventListener('scroll', updateScrollButtons);
        container.dataset.scrollListenerAdded = 'true';
    }
}

// Синхронизация размеров между sidebar и quick filter
function syncSizeSelection() {
    // 1. Слушаем изменения в sidebar чекбоксах → обновляем quick chips
    document.querySelectorAll('.sidebar input[name="sizes[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const size = this.value;
            const system = this.dataset.system;
            const isChecked = this.checked;
            
            // Находим соответствующий quick chip
            const quickChip = document.querySelector(`.quick-chip.size-chip[data-size="${size}"][data-system="${system}"]`);
            if (quickChip) {
                if (isChecked) {
                    quickChip.classList.add('active');
                } else {
                    quickChip.classList.remove('active');
                }
            }
            
            // НЕ применяем фильтры автоматически - только по кнопке "Применить"
        });
    });
    
    // 2. Синхронизируем при загрузке страницы (восстанавливаем из URL)
    const urlParams = new URLSearchParams(window.location.search);
    const sizesParam = urlParams.get('sizes');
    if (sizesParam) {
        const selectedSizes = sizesParam.split(',');
        const currentSystem = localStorage.getItem('preferredSizeSystem') || 'eu';
        
        selectedSizes.forEach(size => {
            // Активируем quick chip
            const quickChip = document.querySelector(`.quick-chip.size-chip[data-size="${size}"][data-system="${currentSystem}"]`);
            if (quickChip) {
                quickChip.classList.add('active');
            }
            
            // Активируем checkbox в sidebar
            const checkbox = document.querySelector(`.sidebar input[name="sizes[]"][value="${size}"][data-system="${currentSystem}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', () => {
    // Восстанавливаем последнюю выбранную систему размеров
    const preferredSystem = localStorage.getItem('preferredSizeSystem') || 'eu';
    if (preferredSystem !== 'eu') {
        switchSizeSystem(preferredSystem);
        switchSidebarSizeSystem(preferredSystem);
    }

    // Проверяем необходимость стрелок при загрузке (с задержкой для рендеринга)
    setTimeout(() => {
        checkSizesOverflow();
    }, 100);

    // Дополнительная проверка через 500ms (на случай медленной загрузки)
    setTimeout(() => {
        checkSizesOverflow();
    }, 500);

    // Синхронизация размеров
    syncSizeSelection();

    // Переключаем при изменении размера окна (debounce 200ms)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            checkSizesOverflow();
        }, 200);
    });
});

// УДАЛЕНО: toggleAdvancedFilters перенесен выше для раннего определения

// НОВОЕ: Сравнение товаров
let compareProducts = JSON.parse(localStorage.getItem('compareProducts') || '[]');

function toggleCompare(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    const button = event.currentTarget;
    const index = compareProducts.indexOf(productId);
    
    if (index > -1) {
        compareProducts.splice(index, 1);
        button.classList.remove('active');
    } else {
        if (compareProducts.length >= 4) {
            alert('Максимум 4 товара для сравнения');
            return;
        }
        compareProducts.push(productId);
        button.classList.add('active');
    }
    
    localStorage.setItem('compareProducts', JSON.stringify(compareProducts));
    updateCompareCount();
}

function updateCompareCount() {
    const count = compareProducts.length;
}

function openQuickView(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    // Открываем Quick View modal
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.classList.add('active');
        // Загрузка данных товара через AJAX
    }
}

function closeQuickView() {
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Открытие/закрытие группы фильтров - см. ниже (удалено дублирование)

// ОТКЛЮЧЕНО: Автоматическое применение при изменении параметров
// Теперь фильтры применяются только при нажатии кнопки "Применить"

// View Mode Switcher
document.querySelectorAll('.view-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    const view = btn.getAttribute('data-view');
    const products = document.getElementById('products');
    
    if (view === 'list') {
        products.classList.remove('products-grid');
        products.classList.add('products-list');
    } else {
        products.classList.remove('products-list');
        products.classList.add('products-grid');
    }
    localStorage.setItem('catalogView', view);
  });
});

// Restore view mode
const savedView = localStorage.getItem('catalogView');
if (savedView) {
  document.querySelectorAll('.view-btn').forEach(btn => {
    if (btn.getAttribute('data-view') === savedView) {
      btn.click();
    }
  });
}

// Sort functionality
function applySort(sortValue) {
  const params = new URLSearchParams(window.location.search);
  params.set('sort', sortValue);
  window.location.href = '/catalog?' + params.toString();
}

// Per page functionality
function applyPerPage(perPageValue) {
  const params = new URLSearchParams(window.location.search);
  params.set('per_page', perPageValue);
  params.set('page', '1'); // Reset to first page
  window.location.href = '/catalog?' + params.toString();
}
window.applyPerPage = applyPerPage;

// View switcher function
function switchView(view) {
  document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
  document.querySelector(`.view-btn[data-view="${view}"]`).classList.add('active');
  
  const products = document.getElementById('products');
  if (view === 'list') {
    products.classList.remove('products-grid');
    products.classList.add('products-list');
  } else {
    products.classList.remove('products-list');
    products.classList.add('products-grid');
  }
  localStorage.setItem('catalogView', view);
}
window.switchView = switchView;

// Price Range Slider
const priceSliderMin = document.getElementById('price-slider-min');
const priceSliderMax = document.getElementById('price-slider-max');
const priceInputMin = document.getElementById('price-from');
const priceInputMax = document.getElementById('price-to');
const sliderRange = document.getElementById('slider-range');

if (priceSliderMin && priceSliderMax) {
  const minPrice = parseInt(priceSliderMin.min);
  const maxPrice = parseInt(priceSliderMin.max);
  
  function updateSlider() {
    let minVal = parseInt(priceSliderMin.value);
    let maxVal = parseInt(priceSliderMax.value);
    
    if (maxVal - minVal < 100) {
      if (event.target === priceSliderMin) {
        priceSliderMin.value = maxVal - 100;
      } else {
        priceSliderMax.value = minVal + 100;
      }
    }
    
    minVal = parseInt(priceSliderMin.value);
    maxVal = parseInt(priceSliderMax.value);
    
    const percent1 = ((minVal - minPrice) / (maxPrice - minPrice)) * 100;
    const percent2 = ((maxVal - minPrice) / (maxPrice - minPrice)) * 100;
    
    sliderRange.style.left = percent1 + '%';
    sliderRange.style.width = (percent2 - percent1) + '%';
    
    priceInputMin.value = minVal;
    priceInputMax.value = maxVal;
  }
  
  function updateInputs() {
    let minVal = parseInt(priceInputMin.value) || minPrice;
    let maxVal = parseInt(priceInputMax.value) || maxPrice;
    
    if (minVal < minPrice) minVal = minPrice;
    if (maxVal > maxPrice) maxVal = maxPrice;
    if (minVal > maxVal) minVal = maxVal;
    if (maxVal < minVal) maxVal = minVal;
    
    priceSliderMin.value = minVal;
    priceSliderMax.value = maxVal;
    
    updateSlider();
  }
  
  priceSliderMin.addEventListener('input', updateSlider);
  priceSliderMax.addEventListener('input', updateSlider);
  priceInputMin.addEventListener('change', updateInputs);
  priceInputMax.addEventListener('change', updateInputs);
  
  // Initialize
  updateSlider();
}

// УДАЛЕНО: устаревший inline applyFiltersAjax — используется catalog.js

// Поиск в фильтре
function searchInFilter(input, itemClass) {
    const query = input.value.toLowerCase();
    const items = input.closest('.filter-content').querySelectorAll(itemClass);
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(query)) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });
}
window.searchInFilter = searchInFilter;

// Color preview on hover
let originalImages = new Map();
function changeColorPreview(dot, defaultImg) {
const card = dot.closest('.product');
const img = card.querySelector('.img img');
if (!originalImages.has(card)) {
originalImages.set(card, img.src);
}
// В реальности здесь был бы AJAX запрос за фото этого цвета
// Пока просто масштабируем как эффект
img.style.filter = 'brightness(1.05)';
}
function resetColorPreview(dot) {
const card = dot.closest('.product');
const img = card.querySelector('.img img');
img.style.filter = 'none';
if (originalImages.has(card)) {
img.src = originalImages.get(card);
}
}

// Quick size select
function selectQuickSize(e, productId, size) {
e.preventDefault();
e.stopPropagation();
openQuickView(e, productId);
setTimeout(() => {
const sizeButtons = document.querySelectorAll('#qvSizes span');
sizeButtons.forEach(btn => {
if (btn.textContent === size) {
btn.style.background = '#000';
btn.style.color = '#fff';
}
});
}, 300);
}

// Quick View
const qvModal=document.getElementById('quickViewModal');
let qvProductId=null, qvSelectedSize=null;
function openQuickView(e,id){
    e.preventDefault();e.stopPropagation();
    qvProductId=id; qvSelectedSize=null;
    fetch(`/catalog/product-quick/${id}`).then(r=>r.json()).then(data=>{
        document.getElementById('qvMainImg').src=data.image;
        document.getElementById('qvBrand').textContent=data.brand;
        document.getElementById('qvName').textContent=data.name;
        document.getElementById('qvPrice').innerHTML=data.price;
        document.getElementById('qvLink').href=data.url;
        let thumbsHtml='';
        if(data.images){data.images.forEach(img=>{thumbsHtml+=`<img src="${img}" onclick="document.getElementById('qvMainImg').src='${img}'">`;})}
        document.getElementById('qvThumbs').innerHTML=thumbsHtml;
        let sizesHtml='<h4>Размер</h4><div style="display:flex;gap:0.5rem;flex-wrap:wrap">';
        if(data.sizes){data.sizes.forEach(s=>{sizesHtml+=`<button type="button" class="size-chip" onclick="qvSelectSize(this,'${s}')">${s}</button>`;});}
        sizesHtml+='</div>';
        document.getElementById('qvSizes').innerHTML=sizesHtml;
        qvModal.classList.add('active');
    }).catch(err=>console.error(err));
}
function qvSelectSize(el,size){
    qvSelectedSize=size;
    document.querySelectorAll('#quickViewModal .size-chip').forEach(b=>b.classList.remove('active'));
    el.classList.add('active');
}
function closeQuickView(){qvModal.classList.remove('active');qvProductId=null;qvSelectedSize=null;}
function addToCart(){
    if(!qvProductId){closeQuickView();return;}
    var csrfMeta=document.querySelector('meta[name="csrf-token"]');
    var csrf=csrfMeta?csrfMeta.getAttribute('content'):'';
    var btn=qvModal.querySelector('.btn-order');
    if(btn){btn.disabled=true;btn.textContent='Добавляем...';}
    fetch('/cart/add',{method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':csrf,'X-Requested-With':'XMLHttpRequest'},
        body:'product_id='+qvProductId+'&quantity=1'+(qvSelectedSize?'&size='+encodeURIComponent(qvSelectedSize):'')
    }).then(r=>r.json()).then(data=>{
        if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-cart-plus"></i> В корзину';}
        if(data.success){
            closeQuickView();
            if(typeof updateCartCount==='function')updateCartCount();
            if(typeof showNotification==='function')showNotification('Товар добавлен в корзину','success');
        } else {
            if(typeof showNotification==='function')showNotification(data.message||'Ошибка','error');
        }
    }).catch(()=>{if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-cart-plus"></i> В корзину';}});
}

// Быстрое добавление в корзину с карточки
function quickAddToCart(e, productId) {
    e.preventDefault();
    e.stopPropagation();
    
    const button = e.currentTarget;
    const originalText = button.innerHTML;
    
    // Показываем загрузку
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Добавление...</span>';
    button.disabled = true;
    
    // Используем функцию из cart.js
    if (typeof addToCart === 'function') {
        // Вызываем addToCart с callback
        $.ajax({
            url: '/cart/add',
            method: 'POST',
            data: {
                productId: productId,
                quantity: 1
            },
            success: function(response) {
                // Анимация успеха
                button.innerHTML = '<i class="bi bi-check-circle"></i> <span>Добавлено!</span>';
                button.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                
                // Обновляем счетчик корзины
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                }
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                    button.disabled = false;
                }, 1500);
            },
            error: function(error) {
                button.innerHTML = '<i class="bi bi-x-circle"></i> <span>Ошибка</span>';
                button.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                    button.disabled = false;
                }, 1500);
            }
        });
    } else {
        // Fallback без cart.js
        setTimeout(() => {
            button.innerHTML = '<i class="bi bi-check-circle"></i> <span>Добавлено!</span>';
            button.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 1500);
        }, 500);
    }
}

// Инициализация слайдера цены перенесена в price-slider.js
</script>
