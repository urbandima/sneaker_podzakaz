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
            <?php else: ?>
            <img src="/images/placeholder.png" class="product-image primary" alt="<?= Html::encode($product->name) ?>">
            <?php endif; ?>
        </a>
        
        <!-- Бейджи -->
        <div class="product-badges">
            <?php if (ProductCardHelper::isNewProduct($product->created_at ?? null)): ?>
            <span class="badge badge-new">NEW</span>
            <?php endif; ?>
            <?php if ($product->hasDiscount()): ?>
            <span class="badge badge-discount">-<?= (int) $product->getDiscountPercent() ?>%</span>
            <?php endif; ?>
        </div>
        
        <!-- Избранное -->
        <button class="action-btn favorite btn-favorite" 
                data-product-id="<?= $product->id ?>"
                onclick="toggleFav(event,<?= $product->id ?>)" 
                aria-label="Добавить в избранное">
            <i class="bi bi-heart"></i>
        </button>
    </div>
    
    <!-- Информация -->
    <div class="product-info">
        <?php if ($product->brand_name): ?>
        <div class="product-card-brand"><?= Html::encode($product->brand_name) ?></div>
        <?php endif; ?>
        
        <h3 class="product-card-name">
            <a href="<?= $product->getUrl() ?>"><?= Html::encode($product->getDisplayTitle()) ?></a>
        </h3>
        
        <!-- Размеры -->
        <?php if (!empty($sizeBadges['badges'])): ?>
        <div class="sizes-quick">
            <?php foreach (array_slice($sizeBadges['badges'], 0, 4) as $badge): ?>
            <span class="size-badge <?= $badge['selected'] ? 'selected' : '' ?>"><?= Html::encode($badge['value']) ?></span>
            <?php endforeach; ?>
            <?php if ($sizeBadges['remaining'] > 0): ?>
            <span class="size-more">+<?= (int) $sizeBadges['remaining'] ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Цена -->
        <div class="product-price">
            <?php if ($priceView['showRange'] && $priceView['minPrice'] !== null && $priceView['maxPrice'] !== null): ?>
                <span class="product-card-price-current">
                    от <?= Yii::$app->formatter->asCurrency($priceView['minPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                </span>
                <span class="product-card-price-range">
                    до <?= Yii::$app->formatter->asCurrency($priceView['maxPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                </span>
            <?php else: ?>
                <span class="product-card-price-current">
                    <?= Yii::$app->formatter->asCurrency($priceView['currentPrice'] ?? $product->price, ProductCardHelper::PRICE_CURRENCY) ?>
                </span>
            <?php endif; ?>
            <?php if ($priceView['showOldPrice'] && $priceView['oldPrice'] !== null): ?>
            <span class="product-card-price-old">
                <?= Yii::$app->formatter->asCurrency($priceView['oldPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
            </span>
            <?php endif; ?>
        </div>
        
        <!-- Теги -->
        <?= \app\frontend\widgets\ProductTagsWidget::widget([
            'product' => $product,
            'style' => 'badges',
            'limit' => 3,
            'showLinks' => true,
            'containerClass' => 'product-tags--compact',
        ]) ?>
        
        <!-- Кнопка -->
        <button class="btn-add-to-cart-card" onclick="quickAddToCart(event, <?= $product->id ?>)">
            В корзину
        </button>
    </div>
</article>
