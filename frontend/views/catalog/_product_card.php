<?php

use app\helpers\ProductCardHelper;
use yii\helpers\Html;

/**
 * Карточка товара - унифицированный стиль
 */
/** @var app\backend\modules\catalog\models\Product $product */
/** @var bool|null $isCriticalCard */
/** @var string|null $selectedSizesParam */
/** @var array|null $selectedSizesArray */
/** @var string|null $sizeField */
/** @var string|null $currentSizeSystem */
/** @var string|null $lazyPlaceholder */
?>

<?php
$lazyPlaceholder = $lazyPlaceholder ?? ProductCardHelper::LAZY_PLACEHOLDER;
$isCriticalCard = $isCriticalCard ?? false;

$selectedSizesParam = $selectedSizesParam ?? Yii::$app->request->get('sizes');
$selectedSizesArray = $selectedSizesArray ?? ProductCardHelper::normalizeSelectedSizes($selectedSizesParam);
$currentSizeSystem = $currentSizeSystem ?? Yii::$app->request->get('size_system', ProductCardHelper::DEFAULT_SIZE_SYSTEM);
$sizeField = $sizeField ?? ProductCardHelper::resolveSizeField($currentSizeSystem);

$galleryImages = ProductCardHelper::buildGalleryImages($product, $lazyPlaceholder);
$sizeBadges = ProductCardHelper::prepareSizeBadges($product, $sizeField, $selectedSizesArray);
$priceView = ProductCardHelper::calculatePriceView($product, $selectedSizesParam, $selectedSizesArray, $sizeField);
?>

<article class="product-card" data-product-id="<?= $product->id ?>">
    <!-- Изображение -->
    <div class="product-image-wrapper">
        <a href="<?= $product->getUrl() ?>" class="product-link">
            <?php if (!empty($galleryImages[0])): ?>
            <img src="<?= Html::encode($galleryImages[0]) ?>" 
                 class="product-image primary" 
                 alt="<?= Html::encode($product->name) ?>"
                 loading="<?= $isCriticalCard ? 'eager' : 'lazy' ?>">
            <?php if (isset($galleryImages[1])): ?>
            <img src="<?= Html::encode($galleryImages[1]) ?>" 
                 class="product-image secondary" 
                 alt="<?= Html::encode($product->name) ?> - вид 2"
                 loading="lazy">
            <?php endif; ?>
            <?php endif; ?>
        </a>
        
        <!-- Бейджи -->
        <div class="product-badges">
            <?php if ($product->hasDiscount()): ?>
            <span class="badge badge-discount">-<?= $product->getDiscountPercent() ?>%</span>
            <?php endif; ?>
            <?php if (ProductCardHelper::isNewProduct($product->created_at ?? null)): ?>
            <span class="badge badge-new">NEW</span>
            <?php endif; ?>
        </div>
        
        <!-- Избранное -->
        <button class="btn-favorite" onclick="toggleFav(event,<?= $product->id ?>)" 
                aria-label="Добавить в избранное">
            <i class="bi bi-heart"></i>
        </button>
        
        <!-- Навигация по галерее -->
        <?php if (count($galleryImages) > 1): ?>
        <button class="product-image-nav prev" data-direction="prev" aria-label="Предыдущее">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="product-image-nav next" data-direction="next" aria-label="Следующее">
            <i class="bi bi-chevron-right"></i>
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Информация -->
    <div class="product-info">
        <div class="product-meta">
            <?php if ($product->brand_name): ?>
            <span class="product-brand"><?= Html::encode($product->brand_name) ?></span>
            <?php endif; ?>
            
            <?php if (isset($product->rating) && $product->rating > 0): ?>
            <div class="product-rating">
                <i class="bi bi-star-fill"></i>
                <span><?= $product->rating ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <h3 class="product-name">
            <a href="<?= $product->getUrl() ?>"><?= Html::encode($product->getDisplayTitle()) ?></a>
        </h3>
        
        <!-- Размеры -->
        <?php if (!empty($sizeBadges['badges'])): ?>
        <div class="sizes-quick">
            <?php foreach (array_slice($sizeBadges['badges'], 0, 4) as $badge): ?>
            <span class="size-badge"><?= Html::encode($badge['value']) ?></span>
            <?php endforeach; ?>
            <?php if ($sizeBadges['remaining'] > 0): ?>
            <span class="size-more">+<?= $sizeBadges['remaining'] ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Цена -->
        <div class="product-price">
            <?php if ($priceView['showOldPrice'] && $priceView['oldPrice'] !== null): ?>
            <span class="price-old">
                <?= Yii::$app->formatter->asCurrency($priceView['oldPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
            </span>
            <?php endif; ?>
            <span class="price-current">
                <?= Yii::$app->formatter->asCurrency($priceView['currentPrice'] ?? $product->price, ProductCardHelper::PRICE_CURRENCY) ?>
            </span>
            <?php if ($priceView['discountPercent'] !== null): ?>
            <span class="product-card-discount">-<?= $priceView['discountPercent'] ?>%</span>
            <?php endif; ?>
        </div>
        
        <!-- Кнопка -->
        <button class="btn-cart" onclick="quickAddToCart(event, <?= $product->id ?>)">
            <i class="bi bi-cart-plus"></i> В корзину
        </button>
    </div>
</article>
