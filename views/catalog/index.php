<?php
/** @var yii\web\View $this */
/** @var app\models\Product[] $products */
/** @var yii\data\Pagination $pagination */
/** @var array $filters */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\components\AssetOptimizer;

$this->title = isset($h1) ? $h1 : 'Каталог товаров';
$this->registerMetaTag(['name' => 'description', 'content' => 'Оригинальные товары из США и Европы']);

// ИСПРАВЛЕНО: Отключаем кэширование в dev режиме для корректной загрузки стилей
if (YII_ENV_DEV) {
    $this->registerMetaTag(['http-equiv' => 'Cache-Control', 'content' => 'no-cache, no-store, must-revalidate']);
    $this->registerMetaTag(['http-equiv' => 'Pragma', 'content' => 'no-cache']);
    $this->registerMetaTag(['http-equiv' => 'Expires', 'content' => '0']);
    
    // КРИТИЧНО: Принудительный редирект и очистка кэша
    $this->registerJs("
    // 1. Редирект с trailing slash
    if (window.location.pathname === '/catalog/' || window.location.pathname.endsWith('/catalog/')) {
        const newUrl = window.location.pathname.replace(/\\/catalog\\/$/, '/catalog') + window.location.search + window.location.hash;
        if (newUrl !== window.location.pathname + window.location.search + window.location.hash) {
            window.location.replace(newUrl);
        }
    }
    
    // 2. Проверка загрузки из кэша и принудительная перезагрузка
    if (performance.navigation.type === 2) {
        // Страница загружена из кэша (Back/Forward) - перезагружаем
        console.log('⚠️ Страница загружена из кэша, принудительная перезагрузка...');
        window.location.reload(true);
    }
    
    // 3. Очистка Service Workers (если есть)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.unregister();
                console.log('🧹 Service Worker удалён');
            }
        });
    }
    
    // 4. Проверка, что правильные CSS загружены
    window.addEventListener('load', function() {
        const catalogInlineCSS = document.querySelector('link[href*=\"catalog-inline.css\"]');
        const catalogCardCSS = document.querySelector('link[href*=\"catalog-card.css\"]');
        const containerSystemCSS = document.querySelector('link[href*=\"container-system.css\"]');
        const allLinks = document.querySelectorAll('link[rel=\"stylesheet\"]');
        
        console.group('📊 Загруженные CSS файлы');
        console.log('container-system.css:', !!containerSystemCSS, containerSystemCSS?.href);
        console.log('catalog-inline.css:', !!catalogInlineCSS, catalogInlineCSS?.href);
        console.log('catalog-card.css:', !!catalogCardCSS, catalogCardCSS?.href);
        console.log('Всего CSS файлов:', allLinks.length);
        console.log('Текущий URL:', window.location.href);
        console.groupEnd();
        
        // Проверка ширины контейнера
        const container = document.querySelector('.container');
        if (container) {
            const containerWidth = window.getComputedStyle(container).maxWidth;
            console.log('🔍 Ширина контейнера:', containerWidth);
            
            // Если ширина не 80% или меньше 1400px - проблема с CSS
            if (containerWidth === '1400px') {
                console.error('❌ Старые стили! Контейнер 1400px вместо 80%');
                console.log('🔄 Принудительная перезагрузка CSS...');
                
                // Перезагружаем страницу с очисткой кэша
                window.location.reload(true);
            } else {
                console.log('✅ Новые стили применены (80% ширина)');
            }
        }
        
        if (!catalogInlineCSS || !catalogCardCSS || !containerSystemCSS) {
            console.error('❌ Критичные CSS файлы каталога не загружены!');
            
            // Принудительная перезагрузка через 1 секунду
            setTimeout(function() {
                window.location.reload(true);
            }, 1000);
        } else {
            console.log('✅ Все CSS файлы каталога загружены корректно');
        }
    });
    ", \yii\web\View::POS_HEAD);
}

// ============================================
// ОПТИМИЗАЦИЯ ЗАГРУЗКИ РЕСУРСОВ
// ============================================
// Используем AssetOptimizer для критического CSS, lazy loading и preload стратегий
AssetOptimizer::optimizeCatalogPage($this, [
    'fonts' => [], // Добавить веб-шрифты при наличии
    'images' => [], // Preload для hero изображений
]);

// Библиотеки (nouislider) - отложенная загрузка
$this->registerLinkTag([
    'rel' => 'preload',
    'as' => 'style',
    'href' => 'https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css',
    'onload' => "this.onload=null;this.rel='stylesheet'",
]);

$this->registerJsFile('https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.js', [
    'position' => \yii\web\View::POS_HEAD,
    'defer' => true,
]);

// КРИТИЧНО: Сначала global-helpers.js (wrapper функции), затем favorites.js (основная логика)
$this->registerJsFile('@web/js/global-helpers.js', [
    'position' => \yii\web\View::POS_HEAD,
]);

$this->registerJsFile('@web/js/favorites.js', [
    'position' => \yii\web\View::POS_HEAD,
]);

// Lazy loading для изображений - обязательно для production
$this->registerJsFile('@web/js/lazy-load.js', [
    'position' => \yii\web\View::POS_HEAD,
    'defer' => true,
]);

// Catalog functionality (filters, AJAX, sorting)
$this->registerJsFile('@web/js/catalog.js', [
    'position' => \yii\web\View::POS_HEAD,
    'defer' => true,
]);

// UI Enhancements (Infinite Scroll, Skeleton, Sticky Filters)
$this->registerJsFile('@web/js/ui-enhancements.js', [
    'position' => \yii\web\View::POS_HEAD,
    'defer' => true,
]);

// КРИТИЧНО: Critical CSS удален - все стили в catalog-inline.css для избежания конфликтов

// НОВОЕ: Мобильные фиксы для 370-1206px (после основных стилей)
$this->registerCssFile('@web/css/catalog-mobile-fixes.css', [
    'position' => \yii\web\View::POS_HEAD,
    'depends' => [\app\assets\AppAsset::class],
]);

// Загружаем полные стили после critical CSS (с версионированием для сброса кэша)
// ИСПРАВЛЕНО: Используем filemtime для версионирования (обновляется только при изменении файла)
$catalogInlinePath = Yii::getAlias('@webroot/css/catalog-inline.css');
$catalogCardPath = Yii::getAlias('@webroot/css/catalog-card.css');
$catalogInlineVersion = file_exists($catalogInlinePath) ? filemtime($catalogInlinePath) : '4.0';
$catalogCardVersion = file_exists($catalogCardPath) ? filemtime($catalogCardPath) : '3.0';

// КРИТИЧНО: Принудительная загрузка стилей каталога БЕЗ кэширования в dev режиме
// ИСПРАВЛЕНО: Всегда используем timestamp для гарантии загрузки свежих стилей
if (YII_ENV_DEV) {
    // Отключаем кэширование полностью для CSS
    $catalogInlineVersion = time();
    $catalogCardVersion = time();
    
    // Также обновляем версию container-system.css через AppAsset
    // Это гарантирует, что все стили загрузятся заново
}

$this->registerCssFile('@web/css/catalog-inline.css?v=' . $catalogInlineVersion, [
    'position' => \yii\web\View::POS_HEAD,
]);

// Стили карточек товаров
$this->registerCssFile('@web/css/catalog-card.css?v=' . $catalogCardVersion, [
    'position' => \yii\web\View::POS_HEAD,
]);

// КРИТИЧНО: Гарантируем видимость header + УДАЛЕНИЕ nav-menu на мобильной
$this->registerCss('
.ecom-header,
.main-header {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
.main-header {
    position: sticky !important;
    top: 0 !important;
    z-index: 1000 !important;
}
/* КРИТИЧНО: nav-menu УДАЛЕНО на мобильной */
@media (max-width: 1199px) {
    .main-nav,
    .nav-menu {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        position: absolute !important;
        left: -9999px !important;
    }
}
');

// Infinite scroll settings - КРИТИЧНО: Устанавливаем ПЕРЕД загрузкой ui-enhancements
$this->registerJs("
document.body.dataset.infiniteScroll = 'true'; 
document.body.dataset.totalPages = '{$pagination->pageCount}';

// Инициализация InfiniteScroll после загрузки ui-enhancements.js
function initInfiniteScrollCatalog() {
    if (window.UIEnhancements && window.UIEnhancements.InfiniteScroll) {
        const productsContainer = document.getElementById('products');
        
        if (productsContainer) {
            window.catalogInfiniteScroll = new window.UIEnhancements.InfiniteScroll({
                container: productsContainer,
                loadMoreUrl: '/catalog/load-more',
                totalPages: {$pagination->pageCount},
                threshold: 300
            });
        }
    } else {
        setTimeout(initInfiniteScrollCatalog, 500);
    }
}

// Запускаем инициализацию
initInfiniteScrollCatalog();
", \yii\web\View::POS_READY);

// Измерение производительности (только в dev режиме)
if (YII_ENV_DEV) {
    AssetOptimizer::measurePerformance($this);
    
    // Удаление DEBUG блока пагинации (если он создаётся динамически)
    $this->registerJs("
    // Удаляем DEBUG блок пагинации
    function removeDebugBlock() {
        const debugBlocks = document.querySelectorAll('[style*=\"background:#fef3c7\"], [style*=\"background: #fef3c7\"]');
        debugBlocks.forEach(block => {
            if (block.textContent.includes('DEBUG MODE') || block.textContent.includes('пагинации')) {
                block.remove();
                console.log('✅ DEBUG блок удалён');
            }
        });
    }
    
    // Удаляем сразу и через интервалы (на случай динамического создания)
    removeDebugBlock();
    setTimeout(removeDebugBlock, 100);
    setTimeout(removeDebugBlock, 500);
    setTimeout(removeDebugBlock, 1000);
    ", \yii\web\View::POS_READY);
}
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="/">Главная</a> / 
            <a href="/catalog">Каталог</a>
            <?php if (isset($h1) && $h1 !== 'Каталог товаров' && $h1 !== 'Каталог'): ?>
                / <span><?= Html::encode($h1) ?></span>
            <?php endif; ?>
        </nav>

        <div class="catalog-layout">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <h3>Фильтры</h3>
                    <button class="close-btn" type="button"><i class="bi bi-x"></i></button>
                </div>

                <!-- Price (открыт по умолчанию) -->
                <div class="filter-group open" id="filter-price">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span><i class="bi bi-currency-dollar"></i> Цена</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:block">
                        <div id="price-slider"></div>
                        <div class="price-inputs">
                            <input type="number" id="price-from" name="price_from" value="<?= $filters['priceRange']['min'] ?>" readonly>
                            <span>—</span>
                            <input type="number" id="price-to" name="price_to" value="<?= $filters['priceRange']['max'] ?>" readonly>
                        </div>
                    </div>
                </div>

                <!-- Brands (открыт по умолчанию) -->
                <div class="filter-group open" id="filter-brands">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span><i class="bi bi-tags-fill"></i> Бренд</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:block">
                        <?php if (count($filters['brands']) > 8): ?>
                            <input type="text" class="filter-search" placeholder="Поиск бренда..." oninput="searchInFilter(this, '.brand-item')">
                        <?php endif; ?>
                        <div class="filter-scroll">
                            <?php foreach ($filters['brands'] as $brand): ?>
                                <label class="filter-item brand-item <?= $brand['count'] == 0 ? 'disabled' : '' ?>">
                                    <input type="checkbox" 
                                           name="brands[]" 
                                           value="<?= $brand['id'] ?>" 
                                           data-slug="<?= $brand['slug'] ?>"
                                           <?= in_array($brand['id'], $currentFilters['brands']) ? 'checked' : '' ?>
                                           <?= $brand['count'] == 0 ? 'disabled' : '' ?>>
                                    <span><?= Html::encode($brand['name']) ?></span>
                                    <span class="count"><?= $brand['count'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Categories (аккордеон) -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span><i class="bi bi-grid-3x3-gap"></i> Категория</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <?php if (count($filters['categories']) > 8): ?>
                            <input type="text" class="filter-search" placeholder="Поиск категории..." oninput="searchInFilter(this, '.cat-item')">
                        <?php endif; ?>
                        <div class="filter-scroll">
                            <?php foreach ($filters['categories'] as $cat): ?>
                                <?php $catCount = isset($cat['count']) ? $cat['count'] : (isset($cat['products_count']) ? $cat['products_count'] : 0); ?>
                                <label class="filter-item cat-item <?= $catCount == 0 ? 'disabled' : '' ?>">
                                    <input type="checkbox" 
                                           name="categories[]" 
                                           value="<?= $cat['id'] ?>" 
                                           data-slug="<?= $cat['slug'] ?>"
                                           <?= in_array($cat['id'], $currentFilters['categories']) ? 'checked' : '' ?>
                                           <?= $catCount == 0 ? 'disabled' : '' ?>>
                                    <span><?= Html::encode($cat['name']) ?></span>
                                    <span class="count"><?= $catCount ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- PRIMARY FILTERS END -->
                
                <!-- ВАЖНЫЕ ХАРАКТЕРИСТИКИ (Пол, Сезон) в основной секции -->
                <?php if (!empty($filters['characteristics'])): ?>
                    <?php foreach ($filters['characteristics'] as $characteristic): ?>
                        <?php if (in_array($characteristic['key'], ['gender', 'season'])): ?>
                            <?= $this->render('_characteristic_filter', [
                                'characteristic' => $characteristic,
                                'currentFilters' => $currentFilters,
                            ]) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- ADVANCED FILTERS (скрыты по умолчанию) -->
                <div class="advanced-filters-wrapper" id="advancedFiltersWrapper" style="display:none">
                
                <!-- Размеры (все системы измерения) -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span><i class="bi bi-rulers"></i> Размер <span id="sidebarSizeSystem">EU</span></span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <!-- Переключатель систем в сайдбаре -->
                        <div class="size-system-toggle-sidebar" style="margin-bottom: 0.75rem;">
                            <button type="button" class="size-system-btn-small active" data-system="eu" onclick="switchSidebarSizeSystem('eu')">EU</button>
                            <button type="button" class="size-system-btn-small" data-system="us" onclick="switchSidebarSizeSystem('us')">US</button>
                            <button type="button" class="size-system-btn-small" data-system="uk" onclick="switchSidebarSizeSystem('uk')">UK</button>
                            <button type="button" class="size-system-btn-small" data-system="cm" onclick="switchSidebarSizeSystem('cm')">CM</button>
                        </div>
                        
                        <?php 
                        // Динамическая загрузка всех доступных размеров для каждой системы
                        if (!empty($filters['sizes'])):
                            $sizeSystems = ['eu', 'us', 'uk', 'cm'];
                            foreach ($sizeSystems as $system): 
                                if (!empty($filters['sizes'][$system])): ?>
                                    <div class="size-filter-grid sidebar-size-grid" data-system="<?= $system ?>" style="<?= $system !== 'eu' ? 'display:none;' : '' ?>">
                                        <?php foreach ($filters['sizes'][$system] as $sizeData): 
                                            $size = $sizeData['size'];
                                            $count = $sizeData['count'];
                                            ?>
                                            <label class="size-filter-btn" title="<?= $count ?> товаров">
                                                <input type="checkbox" name="sizes[]" value="<?= Html::encode($size) ?>" data-system="<?= $system ?>">
                                                <span><?= Html::encode($size) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif;
                            endforeach;
                        else: ?>
                            <p style="padding: 1rem; color: #6b7280; font-size: 0.875rem;">Размеры не найдены</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Цвет -->
                <?php if (!empty($filters['colors'])): ?>
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span><i class="bi bi-palette"></i> Цвет</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
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
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Скидка</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
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
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Рейтинг</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
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
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Условия</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <?php foreach ($filters['conditions'] as $condition): ?>
                            <label class="filter-item">
                                <input type="checkbox" name="conditions[]" value="<?= $condition['value'] ?>">
                                <span><?php if (!empty($condition['icon'])): ?><i class="bi <?= $condition['icon'] ?>"></i> <?php endif; ?><?= Html::encode($condition['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- ДИНАМИЧЕСКИЕ ХАРАКТЕРИСТИКИ (кроме Пола и Сезона - они выше) -->
                <?php if (!empty($filters['characteristics'])): ?>
                    <?php foreach ($filters['characteristics'] as $characteristic): ?>
                        <?php if (!in_array($characteristic['key'], ['gender', 'season'])): ?>
                            <?= $this->render('_characteristic_filter', [
                                'characteristic' => $characteristic,
                                'currentFilters' => $currentFilters,
                            ]) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback: если характеристик нет -->
                    <div class="alert alert-info" style="margin: 1rem; padding: 1rem; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                        <i class="bi bi-info-circle"></i>
                        <strong>Характеристики загружаются...</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #666;">
                            Добавьте характеристики товаров в админ-панели для расширенной фильтрации.
                        </p>
                    </div>
                <?php endif; ?>
                
                </div><!-- END advanced-filters-wrapper -->
                
                <!-- Кнопка "Показать расширенные фильтры" -->
                <?php 
                $advancedCount = 3; // Размеры, Скидка, Рейтинг
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
                    <button type="button" class="btn btn-primary" style="flex: 1; padding: 0.75rem; border-radius: 8px; border: none; background: #2563eb; color: white; cursor: pointer; font-weight: 500;" onclick="event.preventDefault(); event.stopPropagation(); applyFilters();">
                        <i class="bi bi-check-circle"></i>
                        Применить
                    </button>
                    <button type="button" class="btn btn-outline" style="flex: 1; padding: 0.75rem; border-radius: 8px; border: 1px solid #d1d5db; background: white; color: #374151; cursor: pointer; font-weight: 500;" onclick="event.preventDefault(); event.stopPropagation(); resetFilters();">
                        <i class="bi bi-x-circle"></i>
                        Сбросить
                    </button>
                </div>
                
            </aside>

            <!-- Content -->
            <main class="content">
                <div class="content-header">
                    <h1><?= isset($h1) ? Html::encode($h1) : 'Каталог' ?> <span class="products-count">(<span id="productsCount"><?= $pagination->totalCount ?></span>)</span></h1>
                </div>
                
                <!-- Quick Filters: Бренды -->
                <div class="quick-filters-bar">
                    <?php 
                    // Топ-6 популярных брендов для быстрого доступа
                    $topBrands = array_slice($filters['brands'], 0, 6);
                    foreach ($topBrands as $brand): 
                        if ($brand['count'] > 0): 
                            $isActive = in_array($brand['id'], $currentFilters['brands']); ?>
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
                            // Все доступные размеры по всем системам измерения
                            if (!empty($filters['sizes'])): 
                                $sizeSystems = ['eu', 'us', 'uk', 'cm'];
                                foreach ($sizeSystems as $system): 
                                    if (!empty($filters['sizes'][$system])): ?>
                                        <div class="size-group" data-system="<?= $system ?>" style="<?= $system !== 'eu' ? 'display:none;' : '' ?>">
                                            <?php foreach ($filters['sizes'][$system] as $sizeData): 
                                                $size = $sizeData['size'];
                                                $count = $sizeData['count'];
                                                ?>
                                                <button type="button" class="quick-chip size-chip" 
                                                        data-size="<?= Html::encode($size) ?>" 
                                                        data-system="<?= $system ?>"
                                                        onclick="toggleSizeFilter('<?= Html::encode($size) ?>', '<?= $system ?>')"
                                                        title="<?= $count ?> товаров">
                                                    <span><?= Html::encode($size) ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif;
                                endforeach;
                            endif; ?>
                        </div>
                        
                        <!-- Кнопка прокрутки вправо -->
                        <button type="button" class="size-nav-btn size-nav-right" onclick="scrollSizes('right')">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Toolbar -->
                <div class="catalog-toolbar">
                    <div class="toolbar-left">
                        <button class="filter-toggle-btn" type="button" onclick="toggleFilters()">
                            <i class="bi bi-funnel"></i>
                            <span>Фильтры</span>
                            <?php if (!empty($activeFilters)): ?>
                                <span class="filters-count" id="filtersCountBadge"><?= count($activeFilters) ?></span>
                            <?php else: ?>
                                <span class="filters-count" id="filtersCountBadge" style="display:none">0</span>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <div class="toolbar-right">
                        <div class="sort-select">
                            <label><i class="bi bi-sort-down"></i> Сортировка:</label>
                            <select id="sortSelect" onchange="applySort(this.value)">
                                <option value="popular">Популярные</option>
                                <option value="price_asc">Цена: по возрастанию</option>
                                <option value="price_desc">Цена: по убыванию</option>
                                <option value="new">Новинки</option>
                                <option value="rating">По рейтингу</option>
                                <option value="discount">Скидки</option>
                            </select>
                        </div>
                    </div>
                </div>

                <?php if (!empty($activeFilters)): ?>
                <div class="active-filters">
                    <?php foreach ($activeFilters as $filter): ?>
                        <div class="tag">
                            <?= Html::encode($filter['label']) ?>
                            <a href="<?= $filter['removeUrl'] ?>"><i class="bi bi-x"></i></a>
                        </div>
                    <?php endforeach; ?>
                    <a href="/catalog/" class="clear-all">Сбросить все</a>
                </div>
                <?php endif; ?>

                <!-- Skeleton Loading -->
                <div class="skeleton-grid" id="skeletonGrid" style="display:none">
                    <?php for($i=0; $i<8; $i++): ?>
                    <div class="product-skeleton">
                        <div class="skeleton-img"></div>
                        <div class="skeleton-info">
                            <div class="skeleton-line short"></div>
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line medium"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <div class="products grid-5" id="products">
                    <?= $this->render('_products', ['products' => $products]) ?>
                </div>

                <!-- Пагинация (показывается только если страниц > 1) -->
                <?php if (!empty($products) && $pagination->pageCount > 1): ?>
                <div class="pagination">
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                        'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
                        'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
                        'maxButtonCount' => 7,
                        'options' => ['class' => 'pagination'],
                    ]) ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

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
        grid.style.display = grid.dataset.system === system ? '' : 'none';
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
        grid.style.display = grid.dataset.system === system ? '' : 'none';
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
        console.log('❌ Элементы не найдены:', { 
            container: !!container, 
            leftBtn: !!leftBtn, 
            rightBtn: !!rightBtn 
        });
        return;
    }
    
    // Проверяем, есть ли переполнение (контент шире контейнера)
    const hasOverflow = container.scrollWidth > container.clientWidth;
    
    console.log('🔍 Проверка переполнения:', {
        scrollWidth: container.scrollWidth,
        clientWidth: container.clientWidth,
        hasOverflow: hasOverflow,
        scrollLeft: container.scrollLeft,
        screenWidth: window.innerWidth
    });
    
    if (!hasOverflow) {
        leftBtn.style.display = 'none';
        rightBtn.style.display = 'none';
        console.log('⚠️ Переполнения нет - стрелки скрыты');
        return;
    }
    
    // Показываем/скрываем стрелки в зависимости от позиции прокрутки
    const isAtStart = container.scrollLeft <= 5;
    const isAtEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 5;
    
    leftBtn.style.display = isAtStart ? 'none' : 'flex';
    rightBtn.style.display = isAtEnd ? 'none' : 'flex';
    
    console.log('✅ Стрелки обновлены:', { 
        left: leftBtn.style.display, 
        right: rightBtn.style.display,
        isAtStart: isAtStart,
        isAtEnd: isAtEnd
    });
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
    console.log('🚀 Инициализация навигации размеров...');
    
    // Восстанавливаем последнюю выбранную систему размеров
    const preferredSystem = localStorage.getItem('preferredSizeSystem') || 'eu';
    if (preferredSystem !== 'eu') {
        switchSizeSystem(preferredSystem);
        switchSidebarSizeSystem(preferredSystem);
    }
    
    // Проверяем необходимость стрелок при загрузке (с задержкой для рендеринга)
    setTimeout(() => {
        console.log('⏰ Запуск checkSizesOverflow через 100ms...');
        checkSizesOverflow();
    }, 100);
    
    // Дополнительная проверка через 500ms (на случай медленной загрузки)
    setTimeout(() => {
        console.log('⏰ Повторная проверка через 500ms...');
        checkSizesOverflow();
    }, 500);
    
    // Синхронизация размеров
    syncSizeSelection();
    
    // Переключаем при изменении размера окна (debounce 200ms)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            console.log('📐 Resize - обновление стрелок');
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
    
    products.className = 'products ' + view;
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

// AJAX фильтрация с Skeleton Loading + ВСЕ новые фильтры
let filterTimeout;
function applyFiltersAjax() {
const params = new URLSearchParams();

// Собираем бренды
const brands = Array.from(document.querySelectorAll('input[name="brands[]"]:checked')).map(cb => cb.value);
if (brands.length > 0) params.set('brands', brands.join(','));

// Собираем категории
const categories = Array.from(document.querySelectorAll('input[name="categories[]"]:checked')).map(cb => cb.value);
if (categories.length > 0) params.set('categories', categories.join(','));

// Размеры с учетом системы измерения
const sizes = Array.from(document.querySelectorAll('input[name="sizes[]"]:checked')).map(cb => cb.value);
if (sizes.length > 0) {
    params.set('sizes', sizes.join(','));
    // Добавляем текущую систему размеров
    const currentSizeSystem = localStorage.getItem('preferredSizeSystem') || 'eu';
    params.set('size_system', currentSizeSystem);
    
    // DEBUG
    console.log('Фильтр размеров:', {
        sizes: sizes,
        system: currentSizeSystem,
        params: params.toString()
    });
}

// Цвета
const colors = Array.from(document.querySelectorAll('input[name="colors[]"]:checked')).map(cb => cb.value);
if (colors.length > 0) params.set('colors', colors.join(','));

// Скидка
const discountAny = document.querySelector('input[name="discount_any"]:checked');
if (discountAny) params.set('discount_any', '1');

const discountRanges = Array.from(document.querySelectorAll('input[name="discount_range[]"]:checked')).map(cb => cb.value);
if (discountRanges.length > 0) params.set('discount_range', discountRanges.join(','));

// Рейтинг
const rating = document.querySelector('input[name="rating"]:checked')?.value;
if (rating) params.set('rating', rating);

// Условия
const conditions = Array.from(document.querySelectorAll('input[name="conditions[]"]:checked')).map(cb => cb.value);
if (conditions.length > 0) params.set('conditions', conditions.join(','));

// Материал
const materials = Array.from(document.querySelectorAll('input[name="material[]"]:checked')).map(cb => cb.value);
if (materials.length > 0) params.set('material', materials.join(','));

// Сезон
const seasons = Array.from(document.querySelectorAll('input[name="season[]"]:checked')).map(cb => cb.value);
if (seasons.length > 0) params.set('season', seasons.join(','));

// Пол
const gender = document.querySelector('input[name="gender"]:checked')?.value;
if (gender) params.set('gender', gender);

// Стиль
const styles = Array.from(document.querySelectorAll('input[name="style[]"]:checked')).map(cb => cb.value);
if (styles.length > 0) params.set('style', styles.join(','));

// Технологии
const techs = Array.from(document.querySelectorAll('input[name="tech[]"]:checked')).map(cb => cb.value);
if (techs.length > 0) params.set('tech', techs.join(','));

// Высота
const height = document.querySelector('input[name="height"]:checked')?.value;
if (height) params.set('height', height);

// Застежка
const fastenings = Array.from(document.querySelectorAll('input[name="fastening[]"]:checked')).map(cb => cb.value);
if (fastenings.length > 0) params.set('fastening', fastenings.join(','));

// Страна
const countries = Array.from(document.querySelectorAll('input[name="country[]"]:checked')).map(cb => cb.value);
if (countries.length > 0) params.set('country', countries.join(','));

// Акции
const promos = Array.from(document.querySelectorAll('input[name="promo[]"]:checked')).map(cb => cb.value);
if (promos.length > 0) params.set('promo', promos.join(','));

// Цена
const priceFrom = document.getElementById('price-from')?.value;
const priceTo = document.getElementById('price-to')?.value;
if (priceFrom) params.set('price_from', priceFrom);
if (priceTo) params.set('price_to', priceTo);

// Показываем skeleton вместо spinner
document.getElementById('products').style.display = 'none';
document.getElementById('skeletonGrid').style.display = 'grid';

// AJAX запрос
fetch('/catalog/filter?' + params.toString())
.then(r => r.json())
.then(data => {
// Обновляем товары
document.getElementById('products').innerHTML = data.html;

// Обновляем счетчики фильтров (умное сужение)
updateFilterCounts(data.filters);

// Обновляем subtitle
document.getElementById('productsCount').textContent = data.totalCount;

// Обновляем URL без перезагрузки
history.pushState({filters: params.toString()}, '', '/catalog?' + params.toString());

// Скрываем skeleton, показываем товары
document.getElementById('skeletonGrid').style.display = 'none';
document.getElementById('products').style.display = 'grid';

// Скрываем sidebar на mobile
if (window.innerWidth < 768) {
toggleFilters();
}
})
.catch(err => {
console.error('Ошибка фильтрации:', err);
document.getElementById('skeletonGrid').style.display = 'none';
document.getElementById('products').style.display = 'grid';
alert('Произошла ошибка при применении фильтров');
});
}

// Обновление счетчиков фильтров (умное сужение)
function updateFilterCounts(filters) {
    // ИСПРАВЛЕНО: Проверка на существование filters
    if (!filters) {
        return;
    }

    // Обновляем бренды
    if (filters.brands && Array.isArray(filters.brands)) {
        filters.brands.forEach(brand => {
            const checkbox = document.querySelector(`input[name="brands[]"][value="${brand.id}"]`);
            if (checkbox) {
                const label = checkbox.closest('.filter-item');
                const countSpan = label ? label.querySelector('.count') : null;
                if (countSpan) countSpan.textContent = brand.count;

                // Disabled если count = 0
                if (brand.count == 0) {
                    if (label) label.classList.add('disabled');
                    checkbox.disabled = true;
                } else {
                    if (label) label.classList.remove('disabled');
                    checkbox.disabled = false;
                }
            }
        });
    }

    // Обновляем категории
    if (filters.categories && Array.isArray(filters.categories)) {
        filters.categories.forEach(cat => {
            const checkbox = document.querySelector(`input[name="categories[]"][value="${cat.id}"]`);
            if (checkbox) {
                const label = checkbox.closest('.filter-item');
                const countSpan = label ? label.querySelector('.count') : null;
                if (countSpan) countSpan.textContent = cat.count;

                // Disabled если count = 0
                if (cat.count == 0) {
                    if (label) label.classList.add('disabled');
                    checkbox.disabled = true;
                } else {
                    if (label) label.classList.remove('disabled');
                    checkbox.disabled = false;
                }
            }
        });
    }
}

// Применение фильтров (кнопка)
function applyFilters() {
applyFiltersAjax();
}

// Мгновенное применение при изменении чекбокса
document.addEventListener('DOMContentLoaded', () => {
document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
cb.addEventListener('change', () => {
// Debounce чтобы не спамить запросами
clearTimeout(filterTimeout);
filterTimeout = setTimeout(() => {
applyFiltersAjax();
}, 500);
});
});

// Восстановление при кнопке "Назад"
window.addEventListener('popstate', (event) => {
location.reload(); // Или можно сделать AJAX восстановление
});
});

// УДАЛЕНО: toggleFilterGroup перенесен выше для раннего определения

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
function openQuickView(e,id){
e.preventDefault();e.stopPropagation();
fetch(`/catalog/product-quick/${id}`).then(r=>r.json()).then(data=>{
document.getElementById('qvMainImg').src=data.image;
document.getElementById('qvBrand').textContent=data.brand;
document.getElementById('qvName').textContent=data.name;
document.getElementById('qvPrice').innerHTML=data.price;
document.getElementById('qvLink').href=data.url;
let thumbsHtml='';
if(data.images){data.images.forEach(img=>{thumbsHtml+=`<img src="${img}" onclick="document.getElementById('qvMainImg').src='${img}'">`})}
document.getElementById('qvThumbs').innerHTML=thumbsHtml;
let sizesHtml='<h4>Размер</h4><div style="display:flex;gap:0.5rem;flex-wrap:wrap">';
if(data.sizes){data.sizes.forEach(s=>{sizesHtml+=`<span style="padding:0.5rem 1rem;border:2px solid #e5e7eb;border-radius:6px;cursor:pointer">${s}</span>`})}
sizesHtml+='</div>';
document.getElementById('qvSizes').innerHTML=sizesHtml;
qvModal.classList.add('active');
}).catch(err=>console.error(err));
}
function closeQuickView(){qvModal.classList.remove('active')}
function addToCart(){alert('Функция корзины будет добавлена');closeQuickView()}

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
