<?php
/** @var yii\web\View $this */
/** @var stdClass $category */
/** @var array $products */
/** @var yii\data\Pagination $pagination */
/** @var bool $demoMode */

use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = ($category->name ?? 'Категория') . ' - Каталог товаров';
$this->registerMetaTag(['name' => 'description', 'content' => 'Товары категории ' . ($category->name ?? '')]);
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="/">Главная</a> / 
            <a href="/catalog">Каталог</a> / 
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
                <a href="/catalog/product/<?= Html::encode($product['slug'] ?? $product['id'] ?? '') ?>">
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
            <a href="/catalog" class="btn btn-primary">Перейти в каталог</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.catalog-page {
    padding: 20px 0;
}

.breadcrumbs {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.breadcrumbs a {
    color: #6366f1;
    text-decoration: none;
}

.breadcrumbs a:hover {
    text-decoration: underline;
}

.category-header {
    margin-bottom: 30px;
}

.category-header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.category-description {
    color: #666;
    font-size: 16px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.product-card {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-card a {
    text-decoration: none;
    color: inherit;
}

.product-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-info {
    padding: 15px;
}

.brand-name {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.product-name {
    font-size: 14px;
    margin: 5px 0;
    font-weight: 500;
}

.product-price {
    font-size: 16px;
    font-weight: 600;
    color: #6366f1;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 48px;
    color: #ccc;
}

.empty-state p {
    margin: 20px 0;
    color: #666;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 30px;
}
</style>
