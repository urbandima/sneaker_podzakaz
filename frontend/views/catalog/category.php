<?php
/** @var yii\web\View $this */
/** @var stdClass $category */
/** @var array $products */
/** @var yii\data\Pagination $pagination */
/** @var bool $demoMode */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = ($category->name ?? 'Категория') . ' - Каталог товаров';
$this->registerMetaTag(['name' => 'description', 'content' => 'Товары категории ' . ($category->name ?? '')]);
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="<?= Url::to(['/site/index']) ?>">Главная</a> /
            <a href="<?= Url::to(['/catalog/catalog/index']) ?>">Каталог</a> /
            <span><?= Html::encode($category->name ?? 'Категория') ?></span>
        </nav>

        <!-- Category Header -->
        <div class="category-header">
            <h1><?= Html::encode($category->name ?? 'Категория') ?></h1>
            <?php if (!empty($category->description)): ?>
            <p class="category-description"><?= Html::encode($category->description) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($demoMode ?? false): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Демо-режим: отображаются примеры товаров
        </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <?php if (!empty($products)): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <a href="<?= Url::to(['/catalog/catalog/product', 'slug' => $product['slug'] ?? ($product['id'] ?? '')]) ?>">
                    <div class="product-image">
                        <img src="<?= Html::encode($product['image'] ?? '/images/placeholder.jpg') ?>" 
                             alt="<?= Html::encode($product['name'] ?? 'Товар') ?>"
                             loading="lazy">
                    </div>
                    <div class="product-info">
                        <div class="brand-name"><?= Html::encode($product['brand_name'] ?? '') ?></div>
                        <div class="product-name"><?= Html::encode($product['name'] ?? 'Товар') ?></div>
                        <div class="product-price">
                            <?= Yii::$app->formatter->asCurrency($product['price'] ?? 0, 'BYN') ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (isset($pagination)): ?>
        <div class="pagination-wrapper">
            <?= LinkPager::widget(['pagination' => $pagination]) ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-box"></i>
            <p>Товары не найдены</p>
            <a href="<?= Url::to(['/catalog/catalog/index']) ?>" class="btn btn-primary">Перейти в каталог</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* =====================================================
   Страница категории — design tokens
   ===================================================== */
.category-header {
    margin-bottom: var(--spacing-8);
}

.category-header h1 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: var(--spacing-2);
}

.category-description {
    color: var(--text-secondary);
    font-size: var(--font-size-base);
}

/* Category-specific product grid (fallback if main .products grid not used) */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: var(--spacing-6);
    margin-bottom: var(--spacing-8);
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: var(--spacing-8);
}
</style>
