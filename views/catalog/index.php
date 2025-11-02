<?php
/** @var yii\web\View $this */
/** @var app\models\Product[] $products */
/** @var yii\data\Pagination $pagination */
/** @var array $filters */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = isset($h1) ? $h1 : 'Каталог товаров';
$this->registerMetaTag(['name' => 'description', 'content' => 'Оригинальные товары из США и Европы']);

// Mobile-first CSS
$this->registerCssFile('@web/css/mobile-first.css', ['position' => \yii\web\View::POS_HEAD]);
// NOTE: catalog-clean.css отключён - используем inline стили ниже для сохранения оригинального дизайна

// Libs
$this->registerCssFile('https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.js', ['position' => \yii\web\View::POS_HEAD]);

// App JS
$this->registerJsFile('@web/js/product-swipe.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/ui-enhancements.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/catalog.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/cart.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/view-history.js', ['position' => \yii\web\View::POS_END]);

// Infinite scroll settings
$this->registerJs("document.body.dataset.infiniteScroll = 'true'; document.body.dataset.totalPages = '{$pagination->pageCount}';", \yii\web\View::POS_READY);
?>

<div class="catalog-page">
    <!-- Breadcrumbs -->
    <nav class="breadcrumbs-nav">
        <div class="container">
            <ol class="breadcrumbs">
                <li><a href="/"><i class="bi bi-house"></i> Главная</a></li>
                <li><i class="bi bi-chevron-right"></i></li>
                <li class="active">Каталог</li>
            </ol>
        </div>
    </nav>

    <div class="container">
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
                
                <!-- Размеры (как на Wildberries/Lamoda) -->
                <div class="filter-group">
                    <h4 class="filter-title" onclick="toggleFilterGroup(this)">
                        <span><i class="bi bi-rulers"></i> Размер</span>
                        <i class="bi bi-chevron-down"></i>
                    </h4>
                    <div class="filter-content" style="display:none">
                        <div class="size-filter-grid">
                            <?php 
                            $sizes = ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'];
                            foreach ($sizes as $size): ?>
                                <label class="size-filter-btn">
                                    <input type="checkbox" name="sizes[]" value="<?= $size ?>">
                                    <span><?= $size ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
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
                            <span>⭐⭐⭐⭐ и выше</span>
                        </label>
                        <label class="filter-item">
                            <input type="radio" name="rating" value="3">
                            <span>⭐⭐⭐ и выше</span>
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
                            <span>🌟 Новинки</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="conditions[]" value="hit">
                            <span>🔥 Хиты продаж</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="conditions[]" value="free_delivery">
                            <span>🚚 Бесплатная доставка</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="conditions[]" value="in_stock">
                            <span>✅ В наличии</span>
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
                            <span>☀️ Лето</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="season[]" value="winter">
                            <span>❄️ Зима</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="season[]" value="demi">
                            <span>🍂 Демисезон</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="season[]" value="all">
                            <span>🌍 Всесезон</span>
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
                            <span>🎉 Распродажа</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="promo[]" value="bonus">
                            <span>🎁 Бонусы</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="promo[]" value="2for1">
                            <span>💥 2+1</span>
                        </label>
                        <label class="filter-item">
                            <input type="checkbox" name="promo[]" value="exclusive">
                            <span>⭐ Эксклюзив</span>
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
                    <div class="header-title">
                        <h1><?= isset($h1) ? Html::encode($h1) : 'Каталог' ?></h1>
                        <p class="subtitle">Найдено товаров: <strong id="productsCount"><?= $pagination->totalCount ?></strong></p>
                    </div>
                </div>
                
                <!-- Quick Filters (Быстрые чипсы) - НОВОЕ -->
                <div class="quick-filters-bar">
                    <button class="quick-chip" data-filter="discount_any" onclick="toggleQuickFilter('discount_any')">
                        <i class="bi bi-percent"></i>
                        <span>Скидки</span>
                    </button>
                    <button class="quick-chip" data-filter="new" onclick="toggleQuickFilter('new')">
                        <i class="bi bi-star-fill"></i>
                        <span>Новинки</span>
                    </button>
                    <button class="quick-chip" data-filter="in_stock" onclick="toggleQuickFilter('in_stock')">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>В наличии</span>
                    </button>
                    <button class="quick-chip" data-filter="hit" onclick="toggleQuickFilter('hit')">
                        <i class="bi bi-fire"></i>
                        <span>Хиты</span>
                    </button>
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

                <!-- Infinite Scroll: обычная пагинация скрыта -->
                <div class="pagination" style="display:none;">
                    <?php if (!empty($products) && $pagination->pageCount > 1): ?>
                    <?= LinkPager::widget([
                        'pagination' => $pagination,
                        'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
                        'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
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

<style>
/* ============================================
   CATALOG PREMIUM STYLES - Mobile First
   ============================================ */

/* Убран Header - используем breadcrumbs */

/* Layout */
.catalog-layout{display:grid;grid-template-columns:1fr;gap:1.5rem;padding:0}

/* Sidebar - всплывающая панель СЛЕВА (всегда) */
.sidebar{position:fixed;top:0;left:-100%;width:90%;max-width:420px;height:100vh;background:#fff;z-index:200;transition:left 0.35s cubic-bezier(0.4, 0, 0.2, 1);overflow-y:auto;box-shadow:4px 0 32px rgba(0,0,0,0.15)}
.sidebar.active{left:0}
.sidebar-header{display:flex;justify-content:space-between;align-items:center;padding:1.75rem 1.5rem;border-bottom:2px solid #e5e7eb;background:linear-gradient(135deg,#f9fafb 0%,#fff 100%);position:sticky;top:0;z-index:10;backdrop-filter:blur(10px)}
.sidebar-header h3{font-size:1.375rem;font-weight:800;display:flex;align-items:center;gap:0.625rem;color:#111}
.sidebar-header h3::before{content:'🎯';font-size:1.75rem}
.close-btn{background:#f3f4f6;border:none;width:42px;height:42px;border-radius:50%;cursor:pointer;color:#666;display:flex;align-items:center;justify-content:center;font-size:1.375rem;transition:all 0.25s;box-shadow:0 2px 8px rgba(0,0,0,0.05)}
.close-btn:hover{background:#000;color:#fff;transform:rotate(90deg) scale(1.05);box-shadow:0 4px 12px rgba(0,0,0,0.15)}

/* Аккордеон фильтров */
.filter-group{border-bottom:1px solid #e5e7eb;transition:background 0.2s}
.filter-group:hover{background:#fafbfc}
.filter-title{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;font-size:0.8125rem;font-weight:700;text-transform:uppercase;color:#111;cursor:pointer;margin:0;user-select:none;letter-spacing:0.5px;transition:all 0.2s}
.filter-title:hover{background:rgba(0,0,0,0.02)}
.filter-title i{font-size:1rem;color:#666;transition:transform 0.3s,color 0.2s}
.filter-group.open .filter-title i{transform:rotate(180deg);color:#000}
.filter-content{display:none;padding:0 1.25rem 1.25rem;max-height:0;overflow:hidden;transition:max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1)}
.filter-group.open .filter-content{display:block;max-height:500px}

/* Поиск в фильтре */
.filter-search{width:100%;padding:0.5rem;border:1px solid #e5e7eb;border-radius:6px;font-size:0.8125rem;margin-bottom:0.75rem}
.filter-search:focus{outline:none;border-color:#000}

/* Скролл контейнер */
.filter-scroll{max-height:280px;overflow-y:auto;margin-right:-0.5rem;padding-right:0.5rem}
.filter-scroll::-webkit-scrollbar{width:4px}
.filter-scroll::-webkit-scrollbar-track{background:#f1f1f1;border-radius:2px}
.filter-scroll::-webkit-scrollbar-thumb{background:#ccc;border-radius:2px}
.filter-scroll::-webkit-scrollbar-thumb:hover{background:#999}

.filter-item{display:flex;align-items:center;padding:0.5rem 0.75rem;gap:0.625rem;cursor:pointer;font-size:0.875rem;border-radius:6px;transition:all 0.15s;margin-bottom:0.25rem;position:relative}
.filter-item:hover{background:#f3f4f6}
.filter-item input{width:20px;height:20px;cursor:pointer;accent-color:#000}
.filter-item span:nth-child(2){flex:1;font-weight:500;transition:all 0.2s}
.filter-item input:checked ~ span:nth-child(2){font-weight:600;color:#000}
.filter-item:has(input:checked){background:#f0f9ff;border-left:3px solid #3b82f6;padding-left:calc(0.75rem - 3px)}
.filter-item .count{color:#666;font-size:0.8125rem;background:#f3f4f6;padding:0.125rem 0.5rem;border-radius:12px;font-weight:600;min-width:28px;text-align:center;transition:all 0.2s}
.filter-item:has(input:checked) .count{background:#3b82f6;color:#fff}
.filter-item.disabled{opacity:0.5;cursor:not-allowed}
.filter-item.disabled input{cursor:not-allowed}
.filter-item.hidden{display:none}

/* Size Filter Grid (как на Wildberries) */
.size-filter-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem;margin-top:0.5rem}
.size-filter-btn{position:relative;cursor:pointer}
.size-filter-btn input{position:absolute;opacity:0;pointer-events:none}
.size-filter-btn span{display:flex;align-items:center;justify-content:center;padding:0.625rem;border:2px solid #e5e7eb;border-radius:6px;font-weight:600;font-size:0.875rem;transition:all 0.2s;background:#fff}
.size-filter-btn:hover span{border-color:#000;transform:scale(1.05)}
.size-filter-btn input:checked + span{border-color:#000;background:#000;color:#fff}

/* Color Filter Grid (как на Lamoda/Wildberries) */
.color-filter-grid{display:grid;gap:0.75rem;margin-top:0.5rem}
.color-filter-item{display:flex;align-items:center;gap:0.75rem;padding:0.5rem;border-radius:6px;cursor:pointer;transition:background 0.2s}
.color-filter-item:hover{background:#f3f4f6}
.color-filter-item input{width:18px;height:18px;cursor:pointer}
.color-circle{width:28px;height:28px;border-radius:50%;flex-shrink:0;box-shadow:0 0 0 1px rgba(0,0,0,0.1) inset}
.color-name{font-size:0.875rem;flex:1}
.color-filter-item input:checked ~ .color-circle{box-shadow:0 0 0 3px #000,0 0 0 1px rgba(0,0,0,0.1) inset}

.price-inputs{display:flex;gap:0.5rem;align-items:center;margin-top:1rem}
.price-inputs input{flex:1;padding:0.625rem;border:1px solid #e5e7eb;border-radius:6px;text-align:center;font-size:0.875rem;font-weight:600}
#price-slider{margin:1rem 0}
.btn-apply{width:calc(100% - 2.5rem);padding:1.25rem;background:linear-gradient(135deg,#000,#1f2937);color:#fff;border:none;border-radius:14px;font-weight:700;font-size:1.0625rem;cursor:pointer;margin:1.5rem 1.25rem;position:sticky;bottom:1rem;box-shadow:0 -8px 32px rgba(0,0,0,0.15),0 4px 12px rgba(0,0,0,0.1);display:flex;align-items:center;justify-content:center;gap:0.75rem;transition:all 0.25s;backdrop-filter:blur(10px)}
.btn-apply:hover{transform:translateY(-3px);box-shadow:0 -12px 40px rgba(0,0,0,0.2),0 6px 20px rgba(0,0,0,0.15);background:linear-gradient(135deg,#1f2937,#374151)}
.btn-apply:active{transform:translateY(-1px)}
.btn-apply::before{content:'✓';font-size:1.375rem;font-weight:900}

/* НОВОЕ: Quick Filters Bar */
.quick-filters-bar{display:flex;flex-wrap:wrap;gap:0.5rem;padding:1rem 0;margin-bottom:0.5rem;background:#fafbfc;border-radius:8px;padding:1rem}
.quick-chip{display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;background:#fff;border:1.5px solid #e5e7eb;border-radius:20px;font-size:0.875rem;font-weight:600;cursor:pointer;transition:all 0.2s;color:#111}
.quick-chip i{font-size:1rem;transition:transform 0.2s}
.quick-chip:hover{background:#000;color:#fff;border-color:#000;transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.15)}
.quick-chip:hover i{transform:scale(1.1)}
.quick-chip.active{background:#000;color:#fff;border-color:#000}

/* НОВОЕ: Кнопка расширенных фильтров */
.show-advanced-filters-btn{width:calc(100% - 2.5rem);display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;margin:1rem 1.25rem;background:#f9fafb;border:2px dashed #e5e7eb;border-radius:10px;cursor:pointer;transition:all 0.25s;font-weight:700;font-size:0.9375rem;color:#111}
.show-advanced-filters-btn:hover{background:#fff;border-color:#000;transform:translateY(-2px)}
.show-advanced-filters-btn .count{color:#666;font-size:0.8125rem;margin-left:0.5rem}
.show-advanced-filters-btn .toggle-icon{transition:transform 0.3s}
.show-advanced-filters-btn.active .toggle-icon{transform:rotate(180deg)}

/* НОВОЕ: Advanced Filters Wrapper */
.advanced-filters-wrapper{animation:slideDown 0.3s ease-out}
@keyframes slideDown{from{opacity:0;max-height:0}to{opacity:1;max-height:3000px}}

/* Content */
.content-header{margin-bottom:0.5rem;padding:0.5rem 0 0 0}
.header-title h1{font-size:1.25rem;font-weight:900;margin-bottom:0.15rem;letter-spacing:-0.5px}
.subtitle{color:#666;font-size:0.75rem}
.subtitle strong{color:#000;font-size:0.8125rem;font-weight:700}

/* Catalog Toolbar - Sticky (mobile optimized) */
.catalog-toolbar{display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,0.98);padding:0.75rem 1rem;margin-bottom:1rem;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-bottom:1px solid #e5e7eb;position:sticky;top:0;z-index:90;backdrop-filter:blur(10px);transition:all 0.2s}

.toolbar-left{display:flex;align-items:center;gap:1rem}

.filter-toggle-btn{display:flex;align-items:center;gap:0.5rem;background:#000;color:#fff;border:none;padding:0.75rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.9375rem;cursor:pointer;transition:all 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.15)}
.filter-toggle-btn:hover{background:#1f2937;transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.2)}
.filter-toggle-btn:active{transform:translateY(0)}
.filter-toggle-btn .filters-count{background:#ef4444;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:0.75rem;margin-left:0.375rem;font-weight:800;animation:pulse 2s infinite;box-shadow:0 0 0 2px rgba(239,68,68,0.2)}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}

.toolbar-right{display:flex;align-items:center;gap:1rem}

.sort-select{display:flex;align-items:center;gap:0.5rem}
.sort-select label{font-size:0.8125rem;color:#666;font-weight:600;display:none;align-items:center;gap:0.375rem}
.sort-select select{padding:0.625rem 0.875rem;border:1px solid #e5e7eb;border-radius:8px;font-size:0.8125rem;background:#fff;cursor:pointer;min-width:150px;font-weight:500;transition:all 0.2s}
.sort-select select:hover{border-color:#000}
.sort-select select:focus{outline:none;border-color:#000;box-shadow:0 0 0 2px rgba(0,0,0,0.05)}

/* Active Filters - под toolbar по best practices */
.active-filters{display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem;padding:1rem;background:#f9fafb;border-radius:8px}
.tag{display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 0.875rem;background:#f3f4f6;border-radius:20px;font-size:0.8125rem}
.tag a{color:#666;text-decoration:none}
.tag a:hover{color:#000}
.clear-all{color:#ef4444;text-decoration:none;font-weight:600;font-size:0.8125rem;display:inline-flex;align-items:center;padding:0.5rem 0.875rem}

/* Loading Overlay */
.loading-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.9);z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center}
.spinner{width:50px;height:50px;border:4px solid #e5e7eb;border-top:4px solid #000;border-radius:50%;animation:spin 0.8s linear infinite}
@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
.loading-overlay p{margin-top:1rem;font-weight:600;color:#000}

/* Disabled filter items */
.filter-item.disabled{opacity:0.4;cursor:not-allowed}
.filter-item.disabled input{cursor:not-allowed;pointer-events:none}
.filter-item.disabled .count{color:#999}

/* Skeleton Loading */
.skeleton-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:2rem}
.product-skeleton{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.04)}
.skeleton-img{padding-top:125%;background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 50%,#f3f4f6 75%);background-size:200% 100%;animation:shimmer 1.5s infinite}
.skeleton-info{padding:1rem}
.skeleton-line{height:14px;background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 50%,#f3f4f6 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:4px;margin-bottom:0.75rem}
.skeleton-line.short{width:40%}
.skeleton-line.medium{width:70%}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* Floating Apply Button */
.btn-apply-floating{display:none;position:fixed;bottom:20px;left:1rem;right:1rem;z-index:201;background:linear-gradient(135deg,#000,#1f2937);color:#fff;border:none;padding:1.125rem;border-radius:12px;font-weight:700;font-size:1rem;cursor:pointer;box-shadow:0 8px 32px rgba(0,0,0,0.3);align-items:center;justify-content:center;gap:0.75rem;transition:all 0.3s;animation:slideUp 0.3s ease-out}
.btn-apply-floating:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,0.4)}
.btn-apply-floating:active{transform:translateY(-2px)}
@keyframes slideUp{from{transform:translateY(100px);opacity:0}to{transform:translateY(0);opacity:1}}

/* УЛУЧШЕННЫЙ GRID */
/* Mobile: 2 колонки */
.products{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;transition:all 0.3s}

/* Grid 3 */
.products.grid-3{grid-template-columns:repeat(2,1fr)}

/* Grid 4 */
.products.grid-4{grid-template-columns:repeat(2,1fr)}

/* Grid 5 */
.products.grid-5{grid-template-columns:repeat(2,1fr)}

/* List view */
.products.list{grid-template-columns:1fr}
.products.list .product{display:grid;grid-template-columns:200px 1fr;gap:1.5rem}
.products.list .product .img{padding-top:0;height:200px}
.products.list .product .info{padding:1.5rem}
.product{background:#fff;border-radius:10px;overflow:hidden;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 2px 6px rgba(0,0,0,0.04)}
.product:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,0.1)}
.product a{text-decoration:none;color:inherit;display:block}
.product .img{position:relative;padding-top:110%;background:#f9fafb}
.product .img img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover}

/* Product Badges */
.product-badges{position:absolute;top:0.75rem;left:0.75rem;display:flex;flex-direction:column;gap:0.5rem;z-index:2}
.badge{padding:0.375rem 0.75rem;border-radius:6px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;box-shadow:0 2px 8px rgba(0,0,0,0.15)}
.badge-new{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.badge-sale{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}
.badge-bonus{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.badge-exclusive{background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff}

.product .fav{position:absolute;top:0.75rem;right:0.75rem;background:rgba(255,255,255,0.95);border:none;width:36px;height:36px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.125rem;transition:all 0.2s;z-index:2;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.product .fav:hover{transform:scale(1.1);background:#fff}
.product .fav.active{color:#ef4444}
.product .quick-view{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.9);color:#fff;border:none;padding:0.875rem;font-size:0.875rem;font-weight:700;cursor:pointer;opacity:0;transform:translateY(100%);transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:0.5rem}
.product:hover .quick-view{opacity:1;transform:translateY(0)}
.product .info{padding:1.125rem}
.product .brand{font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:0.375rem;letter-spacing:0.5px}
.product h3{font-size:0.9375rem;font-weight:600;margin-bottom:0.75rem;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product .price{display:flex;align-items:center;gap:0.625rem;margin-bottom:0.625rem}
.product .old{font-size:0.875rem;color:#9ca3af;text-decoration:line-through}
.product .current{font-size:1.25rem;font-weight:800;color:#000}
.product .stock{font-size:0.8125rem;font-weight:600}
.product .stock.in{color:#10b981}
.product .stock.out{color:#ef4444}

/* Product Footer - кнопка В корзину */
.product-footer{padding:1rem;border-top:1px solid #f3f4f6;background:#fafbfc}
.btn-add-to-cart{width:100%;background:linear-gradient(135deg,#000,#1f2937);color:#fff;border:none;padding:0.875rem;border-radius:10px;font-weight:700;font-size:0.9375rem;display:flex;align-items:center;justify-content:center;gap:0.5rem;cursor:pointer;transition:all 0.2s}
.btn-add-to-cart:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.2);background:linear-gradient(135deg,#1f2937,#374151)}
.btn-add-to-cart:active{transform:translateY(0)}
.btn-add-to-cart i{font-size:1.125rem}

/* Empty */
.empty{grid-column:1/-1;text-align:center;padding:4rem 1rem}
.empty i{font-size:4rem;color:#e5e7eb;margin-bottom:1rem}
.empty h3{margin-bottom:1rem;color:#666}
.empty button{background:#000;color:#fff;border:none;padding:0.75rem 2rem;border-radius:6px;font-weight:600;cursor:pointer}

/* Pagination (скрыта при infinite scroll) */
.pagination{margin-top:2rem;display:flex;justify-content:center}
.pagination ul{display:flex;gap:0.5rem;list-style:none}
.pagination a{display:flex;align-items:center;justify-content:center;width:40px;height:40px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;text-decoration:none;color:#000;font-weight:600}
.pagination .active a{background:#000;color:#fff;border-color:#000}

/* Infinite Scroll Loading */
.infinite-scroll-loading{display:flex;align-items:center;justify-content:center;padding:3rem 1rem;min-height:120px}
.infinite-scroll-loading .spinner{width:50px;height:50px;border:4px solid #e5e7eb;border-top:4px solid #000;border-radius:50%;animation:spin 0.8s linear infinite}
@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
.infinite-scroll-loading p{font-size:0.9375rem;color:#666;font-weight:500}

/* Overlay - улучшенный */
.overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:150;backdrop-filter:blur(6px);animation:fadeIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);cursor:pointer}
.overlay.active{display:block}
@keyframes fadeIn{from{opacity:0;backdrop-filter:blur(0)}to{opacity:1;backdrop-filter:blur(6px)}}

/* RESPONSIVE GRID IMPROVEMENTS */

/* Small tablets - 3 колонки */
@media (min-width:640px){
.products{grid-template-columns:repeat(3,1fr);gap:1.25rem}
.products.grid-3{grid-template-columns:repeat(3,1fr)}
.products.grid-4{grid-template-columns:repeat(3,1fr)}
.products.grid-5{grid-template-columns:repeat(3,1fr)}
.skeleton-grid{grid-template-columns:repeat(3,1fr);gap:1.25rem}
.product h3{font-size:0.9375rem}
}

/* Tablet */
@media (min-width:768px){
.breadcrumbs-nav{margin-top:0}
.products{grid-template-columns:repeat(3,1fr);gap:1.5rem}
.products.grid-3{grid-template-columns:repeat(3,1fr)}
.products.grid-4{grid-template-columns:repeat(3,1fr)}
.products.grid-5{grid-template-columns:repeat(3,1fr)}
.skeleton-grid{grid-template-columns:repeat(3,1fr);gap:1.5rem}
.catalog-layout{grid-template-columns:1fr;padding:1.5rem 0}
.sidebar{max-width:520px}
.filter-group.open .filter-content{max-height:600px}
.filter-scroll{max-height:450px}
.btn-apply-floating{display:none}
.content h1{font-size:2.25rem}
.sort-select label{display:flex}
.filter-toggle-btn span{display:inline}
}

/* Desktop - 5 колонок, компактные карточки */
@media (min-width:1024px){
.products{grid-template-columns:repeat(4,1fr);gap:1.25rem}
.products.grid-3{grid-template-columns:repeat(3,1fr);gap:1.5rem}
.products.grid-4{grid-template-columns:repeat(4,1fr);gap:1.25rem}
.products.grid-5{grid-template-columns:repeat(5,1fr);gap:1rem}
.skeleton-grid{grid-template-columns:repeat(5,1fr);gap:1rem}
.catalog-toolbar{padding:0.75rem 1rem}
.header-title h1{font-size:1.25rem}
.product h3{font-size:0.8125rem}
.product .info{padding:0.75rem}
.product .brand{font-size:0.625rem}
.product .current{font-size:1rem}
.product .fav{width:32px;height:32px;font-size:1rem}
.filter-scroll{max-height:500px}
.btn-apply-floating{display:none}
.filter-toggle-btn{display:flex !important}
}

/* Large Desktop - wider gaps */
@media (min-width:1280px){
.products{gap:2rem}
.products.grid-4{gap:2rem}
.skeleton-grid{gap:2rem}
.sidebar{max-width:480px}
}

/* XL Desktop - 5 колонок */
@media (min-width:1536px){
.container{width:80%;max-width:1920px;padding:0 2rem}
.products{grid-template-columns:repeat(5,1fr);gap:2rem}
.sidebar{max-width:520px}
}

/* Rating on card */
.product .rating{display:flex;align-items:center;gap:0.375rem;margin-bottom:0.5rem;font-size:0.75rem}
.product .rating .stars{display:flex;gap:1px;color:#fbbf24}
.product .rating .stars i{font-size:0.75rem}
.product .rating-text{color:#666;font-size:0.6875rem}

/* Colors & Sizes on card */
.product .colors{display:flex;align-items:center;gap:0.375rem;margin-bottom:0.5rem}
.product .colors .dot{width:18px;height:18px;border-radius:50%;border:2px solid transparent;cursor:pointer;transition:all 0.2s}
.product .colors .dot:hover{border-color:#000;transform:scale(1.15)}
.product .colors .dot[style*="fff"]{border:2px solid #ccc}
.product .colors .more{font-size:0.6875rem;color:#666;font-weight:600}

/* Sizes quick select */
.product .sizes-quick{display:flex;flex-wrap:wrap;gap:0.375rem;margin-bottom:0.5rem}
.product .size-badge{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:24px;padding:0 0.5rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:4px;font-size:0.6875rem;font-weight:600;cursor:pointer;transition:all 0.2s}
.product .size-badge:hover{background:#000;color:#fff;border-color:#000;transform:scale(1.05)}
.product .size-more{font-size:0.6875rem;color:#666;font-weight:600;display:inline-flex;align-items:center}

/* Quick View Modal */
.quick-view-modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:300;align-items:center;justify-content:center;padding:1rem}
.quick-view-modal.active{display:flex}
.qv-content{background:#fff;border-radius:12px;max-width:900px;width:100%;max-height:90vh;overflow-y:auto;position:relative}
.qv-close{position:absolute;top:1rem;right:1rem;width:40px;height:40px;background:#fff;border:none;border-radius:50%;cursor:pointer;font-size:1.5rem;z-index:10;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.qv-grid{display:grid;grid-template-columns:1fr;gap:1.5rem;padding:1.5rem}
.qv-gallery img{width:100%;border-radius:8px}
.qv-thumbs{display:flex;gap:0.5rem;margin-top:1rem}
.qv-thumbs img{width:80px;height:80px;object-fit:cover;border-radius:6px;cursor:pointer;border:2px solid transparent}
.qv-thumbs img:hover{border-color:#000}
.qv-brand{font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:0.5rem}
.qv-details h2{font-size:1.5rem;font-weight:800;margin-bottom:1rem}
.qv-price{font-size:1.75rem;font-weight:900;margin-bottom:1.5rem}
.qv-sizes,.qv-colors{margin-bottom:1rem}
.qv-sizes h4,.qv-colors h4{font-size:0.875rem;font-weight:700;margin-bottom:0.5rem}
.qv-full{display:inline-block;margin-top:1rem;color:#666;text-decoration:none;font-weight:600}
@media (min-width:768px){.qv-grid{grid-template-columns:1fr 1fr;gap:2rem;padding:2rem}}

/* View History - История просмотров */
.view-history-section{margin:3rem 0;padding:2rem;background:#fafbfc;border-radius:16px}
.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
.section-header h2{font-size:1.5rem;font-weight:800;display:flex;align-items:center;gap:0.625rem}
.section-header h2 i{font-size:1.75rem;color:#3b82f6}
.btn-clear-history{background:#f3f4f6;border:none;padding:0.5rem 1rem;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:0.375rem;transition:all 0.2s}
.btn-clear-history:hover{background:#ef4444;color:#fff}
.view-history-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}
.history-product{background:#fff;border-radius:12px;overflow:hidden;transition:transform 0.2s,box-shadow 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.04)}
.history-product:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.12)}
.history-product a{text-decoration:none;color:inherit;display:block}
.history-img{padding-top:100%;position:relative;background:#f9fafb}
.history-img img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover}
.history-info{padding:1rem}
.history-info .brand{font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:0.25rem}
.history-info h4{font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.history-info .price{font-size:1rem;font-weight:800;color:#000}
.loading-history{text-align:center;padding:2rem;color:#666;font-size:1.125rem}
@media (min-width:640px){.view-history-grid{grid-template-columns:repeat(4,1fr)}}
@media (min-width:1024px){.view-history-grid{grid-template-columns:repeat(6,1fr)}}

/* noUiSlider Custom */
.noUi-target{background:#e5e7eb;border:none;box-shadow:none;height:4px;border-radius:2px}
.noUi-connect{background:#000}
.noUi-handle{width:18px;height:18px;border-radius:50%;background:#000;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.15);cursor:pointer;top:-7px}
.noUi-handle:before,.noUi-handle:after{display:none}

/* НОВОЕ: Современные карточки товаров */
.modern-card{height:100%;display:flex;flex-direction:column}
.modern-card:hover{transform:translateY(-8px);box-shadow:0 12px 24px rgba(0,0,0,0.12)}
.product-image-wrapper{position:relative;padding-top:125%;overflow:hidden;background:#f9fafb}
.product-image{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transition:opacity 0.3s}
.product-image.secondary{opacity:0}
.modern-card:hover .product-image.primary{opacity:0}
.modern-card:hover .product-image.secondary{opacity:1}

/* Компактные бейджи */
.product-badges-compact{position:absolute;top:0.75rem;right:0.75rem;display:flex;flex-direction:column;gap:0.5rem;z-index:2}
.badge-discount{padding:0.375rem 0.625rem;background:rgba(239,68,68,0.95);color:#fff;font-size:0.75rem;font-weight:700;border-radius:6px;backdrop-filter:blur(4px);box-shadow:0 2px 8px rgba(239,68,68,0.3)}
.badge-new{padding:0.375rem 0.625rem;background:rgba(16,185,129,0.95);color:#fff;font-size:0.75rem;font-weight:700;border-radius:6px;backdrop-filter:blur(4px)}

/* Quick Actions */
.quick-actions{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);display:flex;gap:0.5rem;opacity:0;transition:opacity 0.3s;z-index:3}
.modern-card:hover .quick-actions{opacity:1}
.action-btn{width:44px;height:44px;background:rgba(255,255,255,0.95);border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.125rem;color:#111;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:all 0.2s;backdrop-filter:blur(4px)}
.action-btn:hover{background:#000;color:#fff;transform:scale(1.1)}
.action-btn.favorite.active{color:#ef4444}
.action-btn.compare.active{background:#3b82f6;color:#fff}

/* Loading dots анимация */
.loading-dots{display:inline-flex;align-items:center;gap:2px}
.loading-dots .dot{animation:dotPulse 1.4s infinite;opacity:0}
.loading-dots .dot:nth-child(2){animation-delay:0.2s}
.loading-dots .dot:nth-child(3){animation-delay:0.4s}
.loading-dots .dot:nth-child(4){animation-delay:0.6s}
@keyframes dotPulse{0%,80%,100%{opacity:0}40%{opacity:1}}

/* Mobile: скрываем Quick Actions */
@media (max-width:768px){.quick-actions{display:none}}

/* НОВОЕ: Mobile UX - Bottom Sheet улучшения */
@media (max-width:768px){
    /* Sidebar как Bottom Sheet на mobile */
    .sidebar{
        top:auto;
        bottom:-100%;
        left:0;
        width:100%;
        max-width:100%;
        height:85vh;
        border-radius:20px 20px 0 0;
        transition:bottom 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sidebar.active{
        bottom:0;
        left:0;
    }
    
    /* Drag handle для свайпа */
    .sidebar-header::before{
        content:'';
        position:absolute;
        top:-12px;
        left:50%;
        transform:translateX(-50%);
        width:40px;
        height:4px;
        background:rgba(0,0,0,0.2);
        border-radius:2px;
    }
    
    /* Quick Filters всегда видимы на mobile */
    .quick-filters-bar{
        position:sticky;
        top:0;
        z-index:80;
        background:#fff;
        padding:0.75rem;
        margin:0 -1rem;
        box-shadow:0 2px 8px rgba(0,0,0,0.06);
    }
    
    /* Floating Apply Button */
    .btn-apply-floating{
        display:flex;
        width:calc(100% - 2rem);
        position:fixed;
        bottom:1rem;
        left:1rem;
        right:1rem;
        z-index:100;
        padding:1rem;
        background:linear-gradient(135deg,#000,#1f2937);
        color:#fff;
        border:none;
        border-radius:12px;
        font-weight:700;
        font-size:1rem;
        justify-content:center;
        align-items:center;
        gap:0.5rem;
        box-shadow:0 8px 24px rgba(0,0,0,0.25);
    }
}

/* Responsive grid adjustments */
@media (max-width:640px){
    .products.grid-5{grid-template-columns:repeat(2,1fr);gap:0.75rem}
    .catalog-toolbar{flex-wrap:wrap;gap:0.625rem;padding:0.625rem 0.875rem}
    .toolbar-left{flex:1;min-width:0}
    .toolbar-right{flex:1;min-width:0}
    .sort-select{justify-content:flex-end}
    .sort-select select{min-width:0;max-width:150px;font-size:0.75rem;padding:0.5rem 0.625rem}
    .filter-toggle-btn{padding:0.625rem 1rem;font-size:0.875rem}
    .filter-toggle-btn span{display:none}
    .filter-toggle-btn .filters-count{margin-left:0.25rem}
}

@media (min-width:641px) and (max-width:1024px){
    .products.grid-5{grid-template-columns:repeat(3,1fr)}
}

@media (min-width:1025px){
    .products.grid-5{grid-template-columns:repeat(5,1fr)}
}
</style>

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

function toggleFav(e,id){
    e.preventDefault();
    e.stopPropagation();
    // Вызываем правильную функцию из catalog.js
    if(typeof toggleFavorite === 'function'){
        toggleFavorite(e, id);
    } else {
        // Fallback - просто переключаем класс
        e.currentTarget.classList.toggle('active');
        console.warn('toggleFavorite function not found, using fallback');
    }
}
function resetFilters(){window.location.href='/catalog/'}

// НОВОЕ: Быстрые фильтры
function toggleQuickFilter(filterType) {
    const button = event.currentTarget;
    const isActive = button.classList.contains('active');
    
    // Переключаем визуальное состояние
    button.classList.toggle('active');
    
    // Находим соответствующий чекбокс в sidebar
    let checkbox;
    switch(filterType) {
        case 'discount_any':
            checkbox = document.querySelector('input[name="discount_any"]');
            break;
        case 'new':
            checkbox = document.querySelector('input[name="conditions[]"][value="new"]');
            break;
        case 'in_stock':
            checkbox = document.querySelector('input[name="conditions[]"][value="in_stock"]');
            break;
        case 'hit':
            checkbox = document.querySelector('input[name="conditions[]"][value="hit"]');
            break;
    }
    
    if (checkbox) {
        checkbox.checked = !isActive;
    }
    
    // Применяем фильтры
    setTimeout(() => {
        if (typeof applyFilters === 'function') {
            applyFilters();
        }
    }, 100);
}

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
    console.log('Товаров в сравнении:', count);
}

function openQuickView(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    // Открываем Quick View modal
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.classList.add('active');
        // Загрузка данных товара через AJAX
        console.log('Opening quick view for product:', productId);
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

// Размеры
const sizes = Array.from(document.querySelectorAll('input[name="sizes[]"]:checked')).map(cb => cb.value);
if (sizes.length > 0) params.set('sizes', sizes.join(','));

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
// Обновляем бренды
filters.brands.forEach(brand => {
const checkbox = document.querySelector(`input[name="brands[]"][value="${brand.id}"]`);
if (checkbox) {
const label = checkbox.closest('.filter-item');
const countSpan = label.querySelector('.count');
if (countSpan) countSpan.textContent = brand.count;

// Disabled если count = 0
if (brand.count == 0) {
label.classList.add('disabled');
checkbox.disabled = true;
} else {
label.classList.remove('disabled');
checkbox.disabled = false;
}
}
});

// Обновляем категории
filters.categories.forEach(cat => {
const checkbox = document.querySelector(`input[name="categories[]"][value="${cat.id}"]`);
if (checkbox) {
const label = checkbox.closest('.filter-item');
const countSpan = label.querySelector('.count');
if (countSpan) countSpan.textContent = cat.count;

// Disabled если count = 0
if (cat.count == 0) {
label.classList.add('disabled');
checkbox.disabled = true;
} else {
label.classList.remove('disabled');
checkbox.disabled = false;
}
}
});
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
