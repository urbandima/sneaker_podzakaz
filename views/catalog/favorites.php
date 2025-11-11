<?php
/** @var yii\web\View $this */
/** @var app\models\ProductFavorite[] $favorites */

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\AssetOptimizer;

$this->title = 'Избранное - СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'description', 'content' => 'Ваши избранные товары']);

// ============================================
// ОПТИМИЗАЦИЯ ЗАГРУЗКИ РЕСУРСОВ (как в каталоге)
// ============================================
AssetOptimizer::optimizeCatalogPage($this, [
    'fonts' => [],
    'images' => [],
]);

// КРИТИЧНО: favorites.js должен загружаться в HEAD
$this->registerJsFile('@web/js/favorites.js', [
    'position' => \yii\web\View::POS_HEAD,
]);

// Lazy loading
$this->registerJsFile('@web/js/lazy-load.js', [
    'position' => \yii\web\View::POS_HEAD,
    'defer' => true,
]);

// Catalog functionality
$this->registerJsFile('@web/js/catalog.js', [
    'position' => \yii\web\View::POS_HEAD,
    'defer' => true,
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

// КРИТИЧНО: Critical CSS inline для гарантии правильной сетки (как в каталоге)
$this->registerCss("
/* CRITICAL CATALOG GRID - ВСЕГДА ПРИМЕНЯЕТСЯ ПЕРВЫМ */
.products {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 1rem !important;
    width: 100% !important;
}

/* Tablet 640px+ */
@media (min-width: 640px) {
    .products {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.25rem !important;
    }
}

/* Desktop 1024px+ */
@media (min-width: 1024px) {
    .products {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 1.25rem !important;
    }
}

/* Large Desktop 1280px+ */
@media (min-width: 1280px) {
    .products {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 2rem !important;
    }
    .products.grid-5 {
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 1.5rem !important;
    }
}

/* XL Desktop 1536px+ */
@media (min-width: 1536px) {
    .products {
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 2rem !important;
    }
}
", ['type' => 'text/css']);

// Загружаем полные стили после critical CSS (с версионированием для сброса кэша)
$this->registerCssFile('@web/css/catalog-inline.css?v=2.0', [
    'position' => \yii\web\View::POS_HEAD,
]);

// Стили карточек товаров
$this->registerCssFile('@web/css/catalog-card.css?v=2.0', [
    'position' => \yii\web\View::POS_HEAD,
]);

// ИСПРАВЛЕНО: Подключаем глобальные хелперы вместо дублирования кода
$this->registerJsFile('@web/js/global-helpers.js', [
    'position' => \yii\web\View::POS_HEAD,
]);

$this->registerJsFile('@web/js/favorites.js', [
    'position' => \yii\web\View::POS_HEAD,
]);
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs (как в каталоге) -->
        <nav class="breadcrumbs">
            <a href="/">Главная</a> / 
            <a href="/catalog">Каталог</a> / 
            <span>Избранное</span>
        </nav>

        <!-- Content (без sidebar - на всю ширину) -->
        <div style="width: 100%;">
            <main class="content" style="max-width: 100%;">
                <div class="content-header">
                    <h1>Избранное <span class="products-count">(<span id="productsCount"><?= count($favorites) ?></span>)</span></h1>
                </div>
                
                <?php if (empty($favorites)): ?>
                    <!-- Empty State (улучшенный дизайн) -->
                    <div style="text-align:center;padding:4rem 2rem;background:#fff;border-radius:12px;border:1px solid #e5e7eb;">
                        <div style="font-size:4rem;margin-bottom:1rem;opacity:0.3;">
                            <i class="bi bi-heart"></i>
                        </div>
                        <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;color:#111;">Избранное пустое</h3>
                        <p style="color:#6b7280;margin-bottom:2rem;">Вы еще не добавили ни одного товара в избранное</p>
                        <a href="/catalog" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.875rem 1.75rem;background:#000;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.2s;">
                            <i class="bi bi-grid-3x3-gap"></i>
                            Перейти в каталог
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Toolbar (упрощенный, только сортировка) -->
                    <div class="catalog-toolbar">
                        <div class="toolbar-left">
                            <span style="color:#6b7280;font-size:0.875rem;">
                                <i class="bi bi-heart-fill" style="color:#ef4444;"></i> 
                                <?= count($favorites) ?> <?= count($favorites) === 1 ? 'товар' : (count($favorites) < 5 ? 'товара' : 'товаров') ?>
                            </span>
                        </div>
                        
                        <div class="toolbar-right">
                            <div class="sort-select">
                                <label><i class="bi bi-sort-down"></i> Сортировка:</label>
                                <select id="sortSelect" onchange="sortFavorites(this.value)">
                                    <option value="date_desc">Недавно добавленные</option>
                                    <option value="date_asc">Давно добавленные</option>
                                    <option value="price_asc">Цена: по возрастанию</option>
                                    <option value="price_desc">Цена: по убыванию</option>
                                    <option value="name_asc">По названию (А-Я)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Сетка товаров (точно как в каталоге) -->
                    <div class="products grid-5" id="products">
                        <?= $this->render('_products', ['products' => array_map(function($fav) { return $fav->product; }, array_filter($favorites, function($fav) { return $fav->product !== null; }))]) ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<script>
// Сортировка избранного
function sortFavorites(sortType) {
    const container = document.getElementById('products');
    const products = Array.from(container.children);
    
    products.sort((a, b) => {
        const priceA = parseFloat(a.querySelector('.product-card-price-current')?.textContent.replace(/[^\d.]/g, '') || 0);
        const priceB = parseFloat(b.querySelector('.product-card-price-current')?.textContent.replace(/[^\d.]/g, '') || 0);
        const nameA = a.querySelector('.product-card-name')?.textContent.trim() || '';
        const nameB = b.querySelector('.product-card-name')?.textContent.trim() || '';
        
        switch(sortType) {
            case 'price_asc':
                return priceA - priceB;
            case 'price_desc':
                return priceB - priceA;
            case 'name_asc':
                return nameA.localeCompare(nameB, 'ru');
            case 'date_asc':
                return 1; // Reverse current order
            case 'date_desc':
            default:
                return -1; // Keep current order
        }
    });
    
    // Clear and re-append
    container.innerHTML = '';
    products.forEach(product => container.appendChild(product));
}
</script>
