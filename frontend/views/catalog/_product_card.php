<?php

use app\helpers\ProductCardHelper;
use yii\helpers\Html;

/**
 * Карточка товара каталога.
 * Контракт:
 * - $product приходит с жадной загрузкой brand/images/sizes (см. CatalogController::buildProductQuery()).
 * - $isCriticalCard — первые 3-4 карточки, для которых нужно eager-loading изображений.
 * - $selectedSizesParam/$selectedSizesArray/$currentSizeSystem/$sizeField вычисляются в _products.php ради единичного чтения запроса.
 * - $lazyPlaceholder — data URI для ленивой подгрузки (опционален).
 */
/** @var app\models\Product $product */
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

// Нужен ручной пересмотр: card fallback всё ещё читает глобальный запрос при прямой загрузке страницы без _products.php.
$selectedSizesParam = $selectedSizesParam ?? Yii::$app->request->get('sizes');
$selectedSizesArray = $selectedSizesArray ?? ProductCardHelper::normalizeSelectedSizes($selectedSizesParam);
$currentSizeSystem = $currentSizeSystem ?? Yii::$app->request->get('size_system', ProductCardHelper::DEFAULT_SIZE_SYSTEM);
$sizeField = $sizeField ?? ProductCardHelper::resolveSizeField($currentSizeSystem);

$galleryImages = ProductCardHelper::buildGalleryImages($product, $lazyPlaceholder);
$hasGalleryNavigation = count($galleryImages) > 1;

$sizeBadges = ProductCardHelper::prepareSizeBadges($product, $sizeField, $selectedSizesArray);
$priceView = ProductCardHelper::calculatePriceView($product, $selectedSizesParam, $selectedSizesArray, $sizeField);

$renderPriceRange = function (float $min, float $max) {
    return Yii::$app->formatter->asCurrency($min, ProductCardHelper::PRICE_CURRENCY)
        . '<span class="price-separator"> - </span>'
        . Yii::$app->formatter->asCurrency($max, ProductCardHelper::PRICE_CURRENCY);
};
?>

<div class="product product-card modern-card">
    <!-- УЛУЧШЕНО: Hover-эффект смены изображения -->
    <div class="product-image-wrapper" data-gallery-size="<?= count($galleryImages) ?>">
        <a href="<?= $product->getUrl() ?>" class="product-link">
            <div class="product-image-slider" data-active-index="0">
                <?php foreach ($galleryImages as $index => $imageUrl): ?>
                    <?php
                        $isFirst = $index === 0;
                        $isSecond = $index === 1;
                        $imageClasses = ['product-image'];
                        if ($isFirst) {
                            $imageClasses[] = 'is-active';
                            $imageClasses[] = 'primary';
                        } elseif ($isSecond) {
                            $imageClasses[] = 'secondary';
                        }

                        $shouldEagerLoadImage = $isCriticalCard && $isFirst && $imageUrl !== $lazyPlaceholder;
                        $imgSrc = $shouldEagerLoadImage ? Html::encode($imageUrl) : $lazyPlaceholder;
                        $dataSrc = (!$shouldEagerLoadImage && $imageUrl !== $lazyPlaceholder) ? Html::encode($imageUrl) : null;
                    ?>
                    <img
                        class="<?= implode(' ', $imageClasses) ?>"
                        src="<?= $imgSrc ?>"
                        <?php if ($dataSrc): ?>data-src="<?= $dataSrc ?>"<?php endif; ?>
                        alt="<?= Html::encode($product->name) ?>"
                        data-image-index="<?= $index ?>"
                        <?php if ($shouldEagerLoadImage): ?>
                            loading="eager"
                            fetchpriority="high"
                        <?php else: ?>
                            loading="lazy"
                        <?php endif; ?>
                    >
                <?php endforeach; ?>
            </div>
        </a>
        
        <?php if ($hasGalleryNavigation): ?>
            <button class="product-image-nav prev" type="button" aria-label="Предыдущее фото" data-direction="prev">
                <span class="nav-icon"></span>
            </button>
            <button class="product-image-nav next" type="button" aria-label="Следующее фото" data-direction="next">
                <span class="nav-icon"></span>
            </button>
        <?php endif; ?>
        
        <!-- Бейджи (скидка, NEW) -->
        <div class="product-badges-compact">
            <?php if ($product->hasDiscount()): ?>
                <span class="badge-discount">-<?= $product->getDiscountPercent() ?>%</span>
            <?php endif; ?>
            <?php if (ProductCardHelper::isNewProduct($product->created_at ?? null)): ?>
                <span class="badge-new">NEW</span>
            <?php endif; ?>
        </div>
        
        <!-- ИСПРАВЛЕНО: Quick Actions вынесены за пределы ссылки для правильной работы -->
        <div class="quick-actions">
            <button class="action-btn favorite" 
                    type="button"
                    onclick="toggleFav(event,<?= $product->id ?>)"
                    aria-pressed="<?= $product->is_favorite ?? false ? 'true' : 'false' ?>"
                    title="В избранное">
                <i class="bi bi-heart"></i>
            </button>
        </div>
    </div>
    <a href="<?= $product->getUrl() ?>" class="product-link">
        <div class="info product-card-body">
            <!-- Бренд и Рейтинг в одну строку -->
            <div class="product-card-header">
                <?php if ($product->brand_name): ?>
                    <span class="product-card-brand"><?= Html::encode($product->brand_name) ?></span>
                <?php endif; ?>
                
                <?php if (isset($product->rating) && $product->rating > 0): ?>
                <div class="rating rating-compact">
                    <div class="stars">
                        <?php 
                        $fullStars = floor($product->rating);
                        $hasHalf = ($product->rating - $fullStars) >= 0.5;
                        for ($i = 0; $i < $fullStars; $i++): ?>
                            <i class="bi bi-star-fill"></i>
                        <?php endfor; ?>
                        <?php if ($hasHalf): ?>
                            <i class="bi bi-star-half"></i>
                        <?php endif; ?>
                        <?php for ($i = $fullStars + ($hasHalf ? 1 : 0); $i < 5; $i++): ?>
                            <i class="bi bi-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-text"><?= $product->rating ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <h3 class="product-card-name"><?= Html::encode($product->getDisplayTitle()) ?></h3>
            
            <!-- Размеры (с учетом системы измерения) -->
            <?php if (!empty($sizeBadges['badges'])): ?>
            <div class="sizes-quick">
                <?php foreach ($sizeBadges['badges'] as $badge): ?>
                    <span
                        class="size-badge <?= $badge['selected'] ? 'selected' : '' ?>"
                        onclick="selectQuickSize(event, <?= $product->id ?>, '<?= Html::encode($badge['value']) ?>')"
                    ><?= Html::encode($badge['value']) ?></span>
                <?php endforeach; ?>
                <?php if ($sizeBadges['remaining'] > 0): ?>
                    <span class="size-more">+<?= $sizeBadges['remaining'] ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Цена (актуальная с учетом выбранных размеров или диапазон) -->
            <div class="price product-card-price">
                <?php if ($priceView['showRange'] && $priceView['minPrice'] !== null && $priceView['maxPrice'] !== null): ?>
                    <span class="current product-card-price-current">
                        <?= $renderPriceRange($priceView['minPrice'], $priceView['maxPrice']); ?>
                    </span>
                <?php else: ?>
                    <?php if ($priceView['showOldPrice'] && $priceView['oldPrice'] !== null): ?>
                        <span class="old product-card-price-old">
                            <?= Yii::$app->formatter->asCurrency($priceView['oldPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                        </span>
                        <?php if ($priceView['discountPercent'] !== null): ?>
                            <span class="product-card-discount">-<?= $priceView['discountPercent'] ?>%</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span class="current product-card-price-current">
                        <?= Yii::$app->formatter->asCurrency($priceView['currentPrice'] ?? $product->price, ProductCardHelper::PRICE_CURRENCY) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    
    <!-- Кнопка В корзину -->
    <div class="product-footer">
        <button class="btn-add-to-cart" 
                type="button"
                data-product-id="<?= $product->id ?>"
                onclick="quickAddToCart(event, <?= $product->id ?>)">
            <i class="bi bi-cart-plus"></i>
            <span>В корзину</span>
        </button>
    </div>
</div>
