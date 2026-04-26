<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product[] $products */

use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\shared\helpers\ProductCardHelper;

if (empty($products)) return;

$defaultSizeField = 'eu_size';
?>

<div class="similar-products-section recently-viewed-section">
    <div class="section-header">
        <h2><i class="bi bi-clock-history" style="opacity:.55;font-size:1rem;margin-right:.35em"></i> Недавно просмотренные</h2>
        <a href="/catalog/history" class="similar-all-link">Вся история <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="similar-products-grid">
        <?php foreach ($products as $product): ?>
            <?php $priceView = ProductCardHelper::calculatePriceView($product, null, [], $defaultSizeField); ?>
            <a class="similar-product-card" href="<?= Url::to(['/catalog/product', 'slug' => $product->slug ?? $product->id]) ?>">
                <div class="product-image">
                    <img src="<?= Html::encode($product->getMainImageUrl()) ?>"
                         alt="<?= Html::encode($product->name) ?>"
                         loading="lazy">
                    <?php if ($product->hasDiscount()): ?>
                        <span class="rv-badge">−<?= (int)$product->getDiscountPercent() ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <?php if ($product->brand?->name): ?>
                        <div class="product-brand"><?= Html::encode($product->brand->name) ?></div>
                    <?php endif; ?>
                    <h3 class="product-name"><?= Html::encode($product->name) ?></h3>
                    <div class="product-price">
                        <?php if ($priceView['showRange']): ?>
                            <span class="current-price">от <?= number_format($priceView['minPrice'], 0, '.', ' ') ?> BYN</span>
                        <?php else: ?>
                            <span class="current-price"><?= number_format($priceView['currentPrice'] ?? $product->price, 0, '.', ' ') ?> BYN</span>
                            <?php if (!empty($priceView['oldPrice'])): ?>
                                <span class="old-price"><?= number_format($priceView['oldPrice'], 0, '.', ' ') ?> BYN</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* badge overlay on product image inside recently viewed */
.recently-viewed-section .product-image {
    position: relative;
}
.rv-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: var(--c-black, #111);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    letter-spacing: 0.03em;
    z-index: 1;
}
/* Slightly dimmer border-top to separate from tabs above */
.recently-viewed-section {
    padding-top: var(--space-10);
    border-top: 1px solid var(--color-border);
    margin-top: var(--space-10);
}
</style>
