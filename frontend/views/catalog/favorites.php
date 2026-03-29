<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\ProductFavorite[] $favorites */

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\shared\components\AssetOptimizer;
use app\frontend\assets\CatalogAsset;

$this->title = 'Избранное - СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'description', 'content' => 'Ваши избранные товары']);

// Подключаем AssetBundle для каталога (все стили автоматически с версионированием)
CatalogAsset::register($this);

// JS файлы для функционала
$this->registerJsFile('@web/js/favorites.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('@web/js/lazy-load.js', ['position' => \yii\web\View::POS_HEAD, 'defer' => true]);
$this->registerJsFile('@web/js/catalog.js', ['position' => \yii\web\View::POS_HEAD, 'defer' => true]);
$this->registerJsFile('@web/js/global-helpers.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs (как в каталоге) -->
        <nav class="breadcrumbs">
            <a href="<?= \yii\helpers\Url::to(['/site/index']) ?>">Главная</a> /
            <a href="<?= \yii\helpers\Url::to(['/catalog/catalog/index']) ?>">Каталог</a> /
            <span>Избранное</span>
        </nav>

        <!-- Content (без sidebar - на всю ширину) -->
        <div class="history-full-width">
            <main class="content history-content">
                <div class="content-header">
                    <h1>Избранное <span class="products-count">(<span id="productsCount"><?= count($favorites) ?></span>)</span></h1>
                </div>

                <?php if (empty($favorites)): ?>
                    <!-- Empty State -->
                    <div class="history-empty-box">
                        <div class="history-empty-icon">
                            <i class="bi bi-heart"></i>
                        </div>
                        <h3 class="history-empty-title">Избранное пустое</h3>
                        <p class="history-empty-text">Вы ещё не добавили ни одного товара в избранное</p>
                        <a href="<?= \yii\helpers\Url::to(['/catalog/catalog/index']) ?>" class="btn btn-primary history-empty-cta">
                            <i class="bi bi-grid-3x3-gap"></i>
                            Перейти в каталог
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Toolbar (упрощенный, только сортировка) -->
                    <div class="catalog-toolbar">
                        <div class="toolbar-left">
                            <span class="history-toolbar-info">
                                <i class="bi bi-heart-fill favorites-heart-icon"></i>
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
