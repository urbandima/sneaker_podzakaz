<?php
/**
 * Simple product card for category/brand listing pages.
 * Receives a $product array with keys: slug, id, image, name, brand_name, price
 *
 * @var yii\web\View $this
 * @var array $product
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>
<div class="product-card">
    <a href="<?= Url::to(['/catalog/catalog/product', 'slug' => $product['slug'] ?? ($product['id'] ?? '')]) ?>">
        <div class="product-image">
            <img src="<?= Html::encode($product['image'] ?? '/images/placeholder.png') ?>"
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
