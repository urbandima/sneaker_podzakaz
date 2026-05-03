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
<?php
$image = $product['image'] ?? '';
// Карточка получает .is-placeholder когда фото нет либо это data:URI SVG-заглушка
// от Product::getMainImageUrl. При сбое загрузки JS-onerror переключает класс ниже.
$isPlaceholder = empty($image)
    || $image === '/images/placeholder.png'
    || str_starts_with($image, 'data:');
?>
<div class="product-card">
    <a href="<?= Url::to(['/catalog/catalog/product', 'slug' => $product['slug'] ?? ($product['id'] ?? '')]) ?>">
        <div class="product-image-wrapper<?= $isPlaceholder ? ' is-placeholder' : '' ?>">
            <?php if (!$isPlaceholder): ?>
            <img src="<?= Html::encode($image) ?>"
                 class="product-image primary"
                 alt="<?= Html::encode($product['name'] ?? 'Товар') ?>"
                 loading="lazy"
                 onerror="this.closest('.product-image-wrapper').classList.add('is-placeholder');this.onerror=null;">
            <?php endif; ?>
            <div class="product-card-empty-placeholder" aria-hidden="true">
                <svg class="product-card-empty-placeholder__icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="6" y="14" width="52" height="36" rx="4" stroke="currentColor" stroke-width="2.5"/>
                    <circle cx="22" cy="26" r="4" fill="currentColor"/>
                    <path d="M10 46l14-16 12 12 8-8 10 12" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" fill="none"/>
                </svg>
            </div>
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
