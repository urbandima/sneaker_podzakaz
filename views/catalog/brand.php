<?php

/** @var yii\web\View $this */
/** @var app\models\Brand $brand */
/** @var app\models\Product[] $products */
/** @var yii\data\Pagination $pagination */
/** @var array $filters */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = $brand->getMetaTitle();
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="<?= Url::to(['/site/index']) ?>">Главная</a>
            <span>/</span>
            <a href="<?= Url::to(['/catalog/index']) ?>">Каталог</a>
            <span>/</span>
            <span><?= Html::encode($brand->name) ?></span>
        </nav>

        <!-- Brand Header -->
        <div class="brand-header">
            <?php if ($brand->logo): ?>
                <img src="<?= $brand->logo ?>" alt="<?= Html::encode($brand->name) ?>" class="brand-logo">
            <?php endif; ?>
            <h1 class="brand-title"><?= Html::encode($brand->name) ?></h1>
            <?php if ($brand->description): ?>
                <p class="brand-description"><?= Html::encode($brand->description) ?></p>
            <?php endif; ?>
        </div>

        <div class="catalog-layout">
            <!-- Sidebar Filters -->
            <aside class="filters-sidebar">
                <div class="filters-header">
                    <h3>Фильтры</h3>
                    <button class="btn-reset" onclick="resetFilters()">Сбросить</button>
                </div>

                <!-- Категории -->
                <div class="filter-group">
                    <h4 class="filter-title">Категория</h4>
                    <?php foreach ($filters['categories'] as $category): ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="categories[]" value="<?= $category['id'] ?>">
                            <span><?= Html::encode($category['name']) ?></span>
                            <span class="count">(<?= $category['count'] ?? 0 ?>)</span>
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

.brand-header {
    text-align: center;
    padding: 2rem 0;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.brand-logo {
    max-width: 150px;
    height: auto;
    margin-bottom: 1rem;
}

.brand-title {
    font-size: 2.5rem;
    font-weight: 900;
    margin-bottom: 1rem;
    letter-spacing: -1px;
}

.brand-description {
    font-size: 1.125rem;
    color: #666666;
    max-width: 600px;
    margin: 0 auto;
}
</style>

<?php
$this->registerJsFile('/js/catalog.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
