<?php

/** @var yii\web\View $this */
/** @var app\models\Category $category */
/** @var app\models\Product[] $products */
/** @var yii\data\Pagination $pagination */
/** @var array $filters */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = $category->getMetaTitle();
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="<?= Url::to(['/site/index']) ?>">Главная</a>
            <span>/</span>
            <a href="<?= Url::to(['/catalog/index']) ?>">Каталог</a>
            <?php if ($category->parent): ?>
                <span>/</span>
                <a href="<?= $category->parent->getUrl() ?>"><?= Html::encode($category->parent->name) ?></a>
            <?php endif; ?>
            <span>/</span>
            <span><?= Html::encode($category->name) ?></span>
        </nav>

        <!-- Category Header -->
        <div class="category-header">
            <h1 class="category-title"><?= Html::encode($category->name) ?></h1>
            <?php if ($category->description): ?>
                <p class="category-description"><?= Html::encode($category->description) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($category->children)): ?>
                <div class="subcategories">
                    <?php foreach ($category->children as $child): ?>
                        <a href="<?= $child->getUrl() ?>" class="subcategory-link">
                            <?= Html::encode($child->name) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="catalog-layout">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <div class="filters-header">
                    <h3>Фильтры</h3>
                    <button class="btn-reset" onclick="resetFilters()">Сбросить</button>
                </div>

                <!-- Бренды -->
                <div class="filter-group">
                    <h4 class="filter-title">Бренд</h4>
                    <?php foreach ($filters['brands'] as $brand): ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="brands[]" value="<?= $brand['id'] ?>">
                            <span><?= Html::encode($brand['name']) ?></span>
                            <span class="count">(<?= $brand['products_count'] ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Цена -->
                <div class="filter-group">
                    <h4 class="filter-title">Цена</h4>
                    <div class="price-inputs">
                        <input type="number" name="price_from" placeholder="От" min="0">
                        <span>—</span>
                        <input type="number" name="price_to" placeholder="До">
                    </div>
                </div>

                <button class="btn-apply-filters" onclick="applyFilters()">Применить фильтры</button>
            </aside>

            <!-- Main Content -->
            <main class="catalog-main">
                <!-- Controls Bar -->
                <div class="controls-bar">
                    <div class="results-info">
                        Найдено товаров: <strong><?= $pagination->totalCount ?></strong>
                    </div>
                    <div class="controls-actions">
                        <select class="sort-select" onchange="applySort(this.value)">
                            <option value="popular">По популярности</option>
                            <option value="price_asc">Сначала дешевле</option>
                            <option value="price_desc">Сначала дороже</option>
                            <option value="new">Новинки</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="products-grid" id="products-container">
                    <?php if (empty($products)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Товары не найдены</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?= $this->render('_product_card', ['product' => $product]) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if (!empty($products)): ?>
                    <div class="pagination-wrapper">
                        <?= LinkPager::widget([
                            'pagination' => $pagination,
                            'options' => ['class' => 'pagination'],
                        ]) ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="catalog-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> СНИКЕРХЭД. Все права защищены.</p>
        </div>
    </footer>
</div>

<style>
.breadcrumbs {
    padding: 1rem 0;
    font-size: 0.875rem;
    color: #666666;
}

.breadcrumbs a {
    color: #666666;
    text-decoration: none;
}

.breadcrumbs a:hover {
    color: #000000;
}

.breadcrumbs span {
    margin: 0 0.5rem;
}

.category-header {
    padding: 2rem 0;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.category-title {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
    letter-spacing: -1px;
}

.category-description {
    font-size: 1.125rem;
    color: #666666;
    margin-bottom: 1.5rem;
}

.subcategories {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.subcategory-link {
    padding: 0.5rem 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    color: #000000;
    text-decoration: none;
    font-size: 0.9375rem;
    transition: all 0.2s;
}

.subcategory-link:hover {
    background: #000000;
    color: #ffffff;
    border-color: #000000;
}
</style>

<?php
$this->registerJsFile('/js/catalog.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
