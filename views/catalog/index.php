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

// КРИТИЧНО: favorites.js должен загружаться в HEAD, чтобы функция была доступна для inline скриптов
$this->registerJsFile('@web/js/favorites.js', [
    'position' => \yii\web\View::POS_HEAD,
]);

// Lazy loading для изображений - обязательно для production
$this->registerJsFile('@web/js/lazy-load.js', [
    'position' => \yii\web\View::POS_HEAD,
    'defer' => true,
]);

// КРИТИЧНО: Загружаем минифицированные стили для максимальной производительности
$this->registerCssFile('@web/css/catalog-inline.min.css', [
    'position' => \yii\web\View::POS_HEAD,
]);

// Infinite scroll settings - КРИТИЧНО: Устанавливаем ПЕРЕД загрузкой ui-enhancements
$this->registerJs("
document.body.dataset.infiniteScroll = 'true'; 
document.body.dataset.totalPages = '{$pagination->pageCount}';

// Инициализация InfiniteScroll после загрузки ui-enhancements.js
if (window.UIEnhancements && window.UIEnhancements.InfiniteScroll) {
    const productsContainer = document.getElementById('products');
    if (productsContainer) {
        new window.UIEnhancements.InfiniteScroll({
            container: productsContainer,
            loadMoreUrl: '/catalog/load-more',
            totalPages: {$pagination->pageCount},
            threshold: 300
        });
        console.log('✅ Infinite Scroll инициализирован: {$pagination->pageCount} страниц');
    }
} else {
    console.warn('⚠️ UIEnhancements.InfiniteScroll не найден, повторная попытка через 500ms');
    setTimeout(() => {
        if (window.UIEnhancements && window.UIEnhancements.InfiniteScroll) {
            const productsContainer = document.getElementById('products');
            if (productsContainer) {
                new window.UIEnhancements.InfiniteScroll({
                    container: productsContainer,
                    loadMoreUrl: '/catalog/load-more',
                    totalPages: {$pagination->pageCount},
                    threshold: 300
                });
                console.log('✅ Infinite Scroll инициализирован (отложенно)');
            }
        }
    }, 500);
}
", \yii\web\View::POS_READY);

// Измерение производительности (только в dev режиме)
if (YII_ENV_DEV) {
    AssetOptimizer::measurePerformance($this);
}
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="/">Главная</a> / 
            <span>Каталог</span>
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
                            <input type="number" id="price-from" value="<?= $filters['priceRange']['min'] ?>" readonly>
                            <span>—</span>
                            <input type="number" id="price-to" value="<?= $filters['priceRange']['max'] ?>" readonly>
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
                
                <!-- Цвет (как на Wildberries/Lamoda) -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span><i class="bi bi-palette-fill"></i> Цвет</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <div class="color-filter-grid">
                            <?php 
                            $colors = [
                                ['name' => 'Черный', 'hex' => '#000000'],
                                ['name' => 'Белый', 'hex' => '#FFFFFF'],
                                ['name' => 'Красный', 'hex' => '#EF4444'],
                                ['name' => 'Синий', 'hex' => '#3B82F6'],
                                ['name' => 'Зеленый', 'hex' => '#10B981'],
                                ['name' => 'Желтый', 'hex' => '#F59E0B'],
                                ['name' => 'Серый', 'hex' => '#6B7280'],
                                ['name' => 'Коричневый', 'hex' => '#92400E'],
                            ];
                            foreach ($colors as $color): ?>
                                <label class="color-filter-item" title="<?= $color['name'] ?>">
                                    <input type="checkbox" name="colors[]" value="<?= $color['hex'] ?>">
                                    <span class="color-circle" style="background:<?= $color['hex'] ?>;<?= $color['hex'] === '#FFFFFF' ? 'border:2px solid #e5e7eb;' : '' ?>"></span>
                                    <span class="color-name"><?= $color['name'] ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
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
                        <label class="filter-item">
                            <input type="checkbox" name="conditions[]" value="new">
                            <span><i class="bi bi-stars"></i> Новинки</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="conditions[]" value="hit">
                            <span><i class="bi bi-fire"></i> Хиты продаж</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="conditions[]" value="free_delivery">
                            <span><i class="bi bi-truck"></i> Бесплатная доставка</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="conditions[]" value="in_stock">
                            <span><i class="bi bi-check-circle-fill"></i> В наличии</span>
                        </label>
                    </div>
                </div>
                
                <!-- Материал -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Материал</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="checkbox" name="material[]" value="leather">
                            <span>Кожа</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="material[]" value="textile">
                            <span>Текстиль</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="material[]" value="synthetic">
                            <span>Синтетика</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="material[]" value="suede">
                            <span>Замша</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="material[]" value="mesh">
                            <span>Сетка</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="material[]" value="canvas">
                            <span>Канвас</span>
                        </label>
                    </div>
                </div>
                
                <!-- Сезон -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Сезон</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="checkbox" name="season[]" value="summer">
                            <span><i class="bi bi-sun"></i> Лето</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="season[]" value="winter">
                            <span><i class="bi bi-snow"></i> Зима</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="season[]" value="demi">
                            <span><i class="bi bi-cloud-rain"></i> Демисезон</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="season[]" value="all">
                            <span><i class="bi bi-globe"></i> Всесезон</span>
                        </label>
                    </div>
                </div>
                
                <!-- Пол -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Пол</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="radio" name="gender" value="male">
                            <span>Мужской</span>
                        </label>
                        <label class="filter-item">
                            <input type="radio" name="gender" value="female">
                            <span>Женский</span>
                        </label>
                        <label class="filter-item">
                            <input type="radio" name="gender" value="unisex">
                            <span>Унисекс</span>
                        </label>
                    </div>
                </div>
                
                <!-- Стиль -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Стиль</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <div class="filter-scroll">
                            <label class="filter-item">
                                <input type="checkbox" name="style[]" value="sport">
                                <span>Спортивный</span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" name="style[]" value="casual">
                                <span>Повседневный</span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" name="style[]" value="street">
                                <span>Уличный (Street)</span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" name="style[]" value="classic">
                                <span>Классический</span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" name="style[]" value="running">
                                <span>Для бега</span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" name="style[]" value="basketball">
                                <span>Баскетбольный</span>
                            </label>
                            <label class="filter-item">
                                <input type="checkbox" name="style[]" value="skate">
                                <span>Скейтбординг</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Технологии -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Технологии</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="checkbox" name="tech[]" value="air">
                            <span>Nike Air</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="tech[]" value="boost">
                            <span>Adidas Boost</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="tech[]" value="gore_tex">
                            <span>Gore-Tex</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="tech[]" value="zoom">
                            <span>Nike Zoom</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="tech[]" value="react">
                            <span>Nike React</span>
                        </label>
                    </div>
                </div>
                
                <!-- Высота -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Высота</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="radio" name="height" value="low">
                            <span>Низкие</span>
                        </label>
                        <label class="filter-item">
                            <input type="radio" name="height" value="mid">
                            <span>Средние</span>
                        </label>
                        <label class="filter-item">
                            <input type="radio" name="height" value="high">
                            <span>Высокие</span>
                        </label>
                    </div>
                </div>
                
                <!-- Застежка -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Застежка</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="checkbox" name="fastening[]" value="laces">
                            <span>Шнурки</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="fastening[]" value="velcro">
                            <span>Липучки</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="fastening[]" value="zipper">
                            <span>Молния</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="fastening[]" value="slip_on">
                            <span>Slip-on (без застежки)</span>
                        </label>
                    </div>
                </div>
                
                <!-- Страна производства -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Страна производства</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="checkbox" name="country[]" value="vietnam">
                            <span>Вьетнам</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="country[]" value="china">
                            <span>Китай</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="country[]" value="indonesia">
                            <span>Индонезия</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="country[]" value="usa">
                            <span>США</span>
                        </label>
                    </div>
                </div>
                
                <!-- Акции и спецпредложения -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span>Акции</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <label class="filter-item">
                            <input type="checkbox" name="promo[]" value="sale">
                            <span><i class="bi bi-gift"></i> Распродажа</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="promo[]" value="bonus">
                            <span><i class="bi bi-trophy"></i> Бонусы</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="promo[]" value="2for1">
                            <span><i class="bi bi-plus-circle"></i> 2+1</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="promo[]" value="exclusive">
                            <span><i class="bi bi-award"></i> Эксклюзив</span>
                        </label>
                    </div>
                </div>
                
                </div><!-- END advanced-filters-wrapper -->
                
                <!-- Кнопка "Показать расширенные фильтры" -->
                <button class="show-advanced-filters-btn" id="showAdvancedBtn" onclick="toggleAdvancedFilters()">
                    <i class="bi bi-sliders"></i>
                    <span>Расширенные фильтры</span>
                    <span class="count">(12)</span>
                    <i class="bi bi-chevron-down toggle-icon"></i>
                </button>

                <!-- Floating Apply Button (Mobile) -->
                <button class="btn-apply-floating" id="applyFloating" onclick="applyFilters()">
                    <i class="bi bi-check-circle"></i>
                    <span>Применить фильтры</span>
                </button>
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
                        if ($brand['count'] > 0): ?>
                        <button type="button" class="quick-chip brand-chip" 
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
                        <button type="button" class="size-nav-btn size-nav-left" onclick="scrollSizes('left')" style="display:none;">
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
                        <button type="button" class="size-nav-btn size-nav-right" onclick="scrollSizes('right')" style="display:none;">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Toolbar -->
                <div class="catalog-toolbar">
                    <div class="toolbar-left">
                        <button class="filter-toggle-btn" type="button">
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

                <!-- Пагинация: доступна вместе с Infinite Scroll -->
                <div class="pagination">
                    <?php if (!empty($products) && $pagination->pageCount > 1): ?>
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                        'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
                        'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
                        'maxButtonCount' => 7,
                    ]) ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<div class="overlay sidebar-overlay" id="overlay"></div>

<!-- Quick View Modal -->
<div class="quick-view-modal" id="quickViewModal">
    <div class="qv-content">
        <button class="qv-close" onclick="closeQuickView()"><i class="bi bi-x"></i></button>
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
                <button class="btn-order" onclick="addToCart()"><i class="bi bi-cart-plus"></i> В корзину</button>
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

// Закрытие по клику на overlay
overlay?.addEventListener('click', toggleFilters);

// Закрытие по ESC
document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape' && sidebar.classList.contains('active')){
        toggleFilters();
    }
});

// ИСПРАВЛЕНО (Проблема #15): Унифицированный wrapper
function toggleFav(e, id) {
    e.preventDefault();
    e.stopPropagation();
    // Вызываем глобальную функцию с правильными параметрами
    if (typeof window.toggleFavorite === 'function') {
        window.toggleFavorite(e, id);
    } else {
        console.error('toggleFavorite function not found. Make sure catalog.js is loaded.');
    }
}
function resetFilters(){window.location.href='/catalog/'}

// Фильтр по брендам
function toggleBrandFilter(brandId, brandSlug) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const button = event.currentTarget;
    const isActive = button.classList.contains('active');
    
    button.classList.toggle('active');
    
    const checkbox = document.querySelector(`input[name="brands[]"][value="${brandId}"]`);
    if (checkbox) {
        checkbox.checked = !isActive;
        if (typeof window.applyFilters === 'function') {
            window.applyFilters();
        }
    }
}

// НОВОЕ: Переключение размерных сеток в quick-filters
function switchSizeSystem(system) {
    // Сохраняем выбор в localStorage
    localStorage.setItem('preferredSizeSystem', system);
    
    // Переключаем активную кнопку
    document.querySelectorAll('.size-system-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.size-system-btn[data-system="${system}"]`).classList.add('active');
    
    // Сбрасываем активные чипы размеров (так как системы разные)
    document.querySelectorAll('.size-chip').forEach(chip => {
        chip.classList.remove('active');
    });
    
    // Показываем/скрываем группы размеров
    document.querySelectorAll('.size-group').forEach(group => {
        group.style.display = group.dataset.system === system ? '' : 'none';
    });
    
    // Сбрасываем все чекбоксы размеров и применяем фильтры
    document.querySelectorAll('input[name="sizes[]"]').forEach(cb => {
        cb.checked = false;
    });
    
    // Применяем фильтры для обновления каталога
    if (typeof applyFilters === 'function') {
        applyFilters();
    }
}

// Переключение системы в sidebar
function switchSidebarSizeSystem(system) {
    // Сохраняем выбор в localStorage
    localStorage.setItem('preferredSizeSystem', system);
    
    // Обновляем текст в заголовке
    document.getElementById('sidebarSizeSystem').textContent = system.toUpperCase();
    
    // Переключаем активную кнопку
    document.querySelectorAll('.size-system-btn-small').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`.size-system-btn-small[data-system="${system}"]`).classList.add('active');
    
    // Сбрасываем активные чипы
    document.querySelectorAll('.size-chip').forEach(chip => {
        chip.classList.remove('active');
    });
    
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
    
    // Сбрасываем все чекбоксы размеров
    document.querySelectorAll('input[name="sizes[]"]').forEach(cb => {
        cb.checked = false;
    });
    
    // Применяем фильтры для обновления каталога
    if (typeof applyFilters === 'function') {
        applyFilters();
    }
    
    // Обновляем видимость стрелок навигации
    setTimeout(() => {
        if (typeof updateScrollButtons === 'function') {
            updateScrollButtons();
        }
    }, 100);
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
    
    const visibleGrid = document.querySelector(`.sidebar-size-grid[data-system="${system}"]`);
    const checkbox = visibleGrid 
        ? visibleGrid.querySelector(`input[name="sizes[]"][value="${size}"]`)
        : document.querySelector(`input[name="sizes[]"][value="${size}"][data-system="${system}"]`);
    
    if (checkbox) {
        checkbox.checked = !isActive;
    }
    
    if (typeof window.applyFilters === 'function') {
        window.applyFilters();
    }
}

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
    
    if (!container || !leftBtn || !rightBtn) return;
    
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

// Восстановление выбранной системы размеров при загрузке
document.addEventListener('DOMContentLoaded', () => {
    const preferredSystem = localStorage.getItem('preferredSizeSystem') || 'eu';
    if (preferredSystem !== 'eu') {
        switchSizeSystem(preferredSystem);
        switchSidebarSizeSystem(preferredSystem);
    }
    
    // Проверяем необходимость стрелок при загрузке
    setTimeout(() => updateScrollButtons(), 100);
    
    // Обновляем стрелки при изменении размера окна
    window.addEventListener('resize', updateScrollButtons);
    
    // Обновляем стрелки при прокрутке
    const container = document.getElementById('sizesScrollContainer');
    if (container) {
        container.addEventListener('scroll', updateScrollButtons);
    }
});

// НОВОЕ: Переключение расширенных фильтров
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

// Открытие/закрытие группы фильтров
function toggleFilterGroup(title) {
  const group = title.closest('.filter-group');
  const isOpen = group.classList.contains('open');
  
  // Можно закрыть другие (если нужно accordion behavior)
  // document.querySelectorAll('.filter-group').forEach(g => g.classList.remove('open'));
  
  if (isOpen) {
    group.classList.remove('open');
  } else {
    group.classList.add('open');
  }
}

// Автоматическое применение фильтра при изменении
document.addEventListener('DOMContentLoaded', function() {
  // Применяем фильтр при изменении чекбокса/радио
  const filterInputs = document.querySelectorAll('.sidebar input[type="checkbox"], .sidebar input[type="radio"]');
  filterInputs.forEach(input => {
    input.addEventListener('change', function() {
      // Задержка для лучшего UX
      clearTimeout(window.filterTimeout);
      window.filterTimeout = setTimeout(() => {
        applyFilters();
      }, 500);
    });
  });
});

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

// Аккордеон фильтров
function toggleFilterGroup(titleEl) {
const group = titleEl.closest('.filter-group');
const content = group.querySelector('.filter-content');
const icon = titleEl.querySelector('i');
if (group.classList.contains('open')) {
group.classList.remove('open');
content.style.display = 'none';
icon.classList.remove('bi-chevron-up');
icon.classList.add('bi-chevron-down');
} else {
group.classList.add('open');
content.style.display = 'block';
icon.classList.remove('bi-chevron-down');
icon.classList.add('bi-chevron-up');
}
}

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

document.addEventListener('DOMContentLoaded',()=>{
const slider=document.getElementById('price-slider');
if(slider&&typeof noUiSlider!=='undefined'){
const min=<?= $filters['priceRange']['min'] ?>,max=<?= $filters['priceRange']['max'] ?>;
noUiSlider.create(slider,{start:[min,max],connect:true,range:{'min':min,'max':max},step:1,format:{to:v=>Math.round(v),from:v=>Number(v)}});
const pf=document.getElementById('price-from'),pt=document.getElementById('price-to');
slider.noUiSlider.on('update',values=>{if(pf)pf.value=values[0];if(pt)pt.value=values[1]});
}
});
</script>
