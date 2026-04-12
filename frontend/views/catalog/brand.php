<?php
/** @var yii\web\View $this */
/** @var stdClass $brand */
/** @var array $products */
/** @var yii\data\Pagination $pagination */
/** @var bool $demoMode */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = ($brand->name ?? 'Бренд') . ' - Каталог товаров';
$this->registerMetaTag(['name' => 'description', 'content' => 'Товары бренда ' . ($brand->name ?? '')]);
?>

<div class="catalog-page">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="<?= Url::to(['/site/index']) ?>">Главная</a> /
            <a href="<?= Url::to(['/catalog']) ?>">Каталог</a> /
            <span><?= Html::encode($brand->name ?? 'Бренд') ?></span>
        </nav>

        <!-- Brand Header -->
        <div class="brand-header">
            <h1><?= Html::encode($brand->name ?? 'Бренд') ?></h1>
            <?php if (!empty($brand->description)): ?>
            <p class="brand-description"><?= Html::encode($brand->description) ?></p>
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
            <?= $this->render('_product_card_simple', ['product' => $product]) ?>
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
            <a href="<?= Url::to(['/catalog']) ?>" class="btn btn-primary">Перейти в каталог</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* =====================================================
   Страница бренда — design tokens
   ===================================================== */
.brand-header {
    margin-bottom: var(--spacing-8);
}

.brand-header h1 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: var(--spacing-2);
}

.brand-description {
    color: var(--text-secondary);
    font-size: var(--font-size-base);
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: var(--spacing-8);
}
</style>
