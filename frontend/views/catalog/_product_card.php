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
$searchQuery = $searchQuery ?? '';

$selectedSizesParam = $selectedSizesParam ?? Yii::$app->request->get('sizes');
$selectedSizesArray = $selectedSizesArray ?? ProductCardHelper::normalizeSelectedSizes($selectedSizesParam);
$currentSizeSystem = $currentSizeSystem ?? Yii::$app->request->get('size_system', ProductCardHelper::DEFAULT_SIZE_SYSTEM);
$sizeField = $sizeField ?? ProductCardHelper::resolveSizeField($currentSizeSystem);

$galleryImages = ProductCardHelper::buildGalleryImages($product, $lazyPlaceholder);
$sizeBadges = ProductCardHelper::prepareSizeBadges($product, $sizeField, $selectedSizesArray);
$priceView = ProductCardHelper::calculatePriceView($product, $selectedSizesParam, $selectedSizesArray, $sizeField);
$effectivePrice = $priceView['currentPrice'] ?? $product->price ?? 0;
$hasPrice = ($priceView['showRange'] && $priceView['minPrice'] && $priceView['maxPrice'])
    ? ($priceView['minPrice'] > 0)
    : ($effectivePrice > 0);

// Заглушка визуально активируется через .is-placeholder, когда у товара нет
// настоящего фото. И buildGalleryImages, и Product::getMainImageUrl возвращают
// data:URI SVG-плейсхолдер при отсутствии фото — оба ловятся проверкой `data:`.
// При сбое загрузки JS-onerror переключает класс — карточка получает иконку
// вместо broken-image иконки браузера.
$mainImage = $galleryImages[0] ?? null;
$isPlaceholder = empty($mainImage) || strncmp($mainImage, 'data:', 5) === 0;
?>

<article class="product-card" data-product-id="<?= $product->id ?>">
    <!-- Изображение -->
    <div class="product-image-wrapper<?= $isPlaceholder ? ' is-placeholder' : '' ?>">
        <a href="<?= $product->getUrl() ?>" class="product-link">
            <?php if (!$isPlaceholder): ?>
            <img src="<?= Html::encode($mainImage) ?>"
                 class="product-image primary"
                 alt="<?= Html::encode($product->name) ?>"
                 width="600" height="600"
                 loading="<?= $isCriticalCard ? 'eager' : 'lazy' ?>"
                 decoding="<?= $isCriticalCard ? 'sync' : 'async' ?>"
                 <?= $isCriticalCard ? 'fetchpriority="high"' : '' ?>
                 onerror="this.closest('.product-image-wrapper').classList.add('is-placeholder');this.onerror=null;">
            <?php if (isset($galleryImages[1])): ?>
            <img src="<?= Html::encode($galleryImages[1]) ?>"
                 class="product-image secondary"
                 alt="<?= Html::encode($product->name) ?> - вид 2"
                 width="600" height="600"
                 loading="lazy"
                 decoding="async"
                 onerror="this.closest('.product-image-wrapper').classList.add('is-placeholder');this.onerror=null;">
            <?php endif; ?>
            <?php endif; ?>
        </a>

        <!-- Стилизованная заглушка: видна через .is-placeholder, иначе скрыта.
             Иконка без подписи — на тайле размер карточки и так подсказывает,
             что это превью отсутствующего фото (см. CMP-34 acceptance). -->
        <div class="product-card-empty-placeholder" aria-hidden="true">
            <svg class="product-card-empty-placeholder__icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="6" y="14" width="52" height="36" rx="4" stroke="currentColor" stroke-width="2.5"/>
                <circle cx="22" cy="26" r="4" fill="currentColor"/>
                <path d="M10 46l14-16 12 12 8-8 10 12" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" fill="none"/>
            </svg>
        </div>
        
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

        <!-- Hover overlay: быстрый просмотр + добавление в корзину -->
        <div class="product-card-overlay">
            <a href="<?= $product->getUrl() ?>" class="overlay-quick-view">
                <i class="bi bi-eye"></i>
                Быстрый просмотр
            </a>
            <button class="overlay-cart-btn" onclick="quickAddToCart(event, <?= $product->id ?>)"
                    aria-label="Добавить в корзину"
                    <?= !$hasPrice ? 'disabled data-disabled="no-price" title="Цена уточняется"' : '' ?>>
                <i class="bi bi-bag-plus"></i>
            </button>
        </div>
    </div>
    
    <!-- Информация -->
    <div class="product-info">
        <?php if ($product->brand_name && $product->brand_name !== '-'): ?>
        <div class="product-card-brand"><?= Html::encode($product->brand_name) ?></div>
        <?php endif; ?>
        
        <h3 class="product-card-name">
            <a href="<?= $product->getUrl() ?>"><?php
                $title = Html::encode($product->getDisplayTitle());
                if ($searchQuery !== '' && mb_strlen($searchQuery) >= 2) {
                    $title = preg_replace('/(' . preg_quote(Html::encode($searchQuery), '/') . ')/iu', '<mark>$1</mark>', $title);
                }
                echo $title;
            ?></a>
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
        <?php
        $hasPriceRange = $priceView['showRange'] && $priceView['minPrice'] && $priceView['maxPrice'];
        ?>
        <div class="product-price">
            <?php if ($hasPriceRange): ?>
                <span class="product-card-price-current">
                    от <?= Yii::$app->formatter->asCurrency($priceView['minPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                </span>
                <span class="product-card-price-range">
                    до <?= Yii::$app->formatter->asCurrency($priceView['maxPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                </span>
            <?php elseif ($hasPrice): ?>
                <span class="product-card-price-current">
                    <?= Yii::$app->formatter->asCurrency($effectivePrice, ProductCardHelper::PRICE_CURRENCY) ?>
                </span>
            <?php else: ?>
                <span class="product-card-price-pending">Цена уточняется</span>
            <?php endif; ?>
            <?php if ($hasPrice && $priceView['showOldPrice'] && $priceView['oldPrice'] !== null): ?>
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

        <!-- Кнопка В корзину -->
        <?php if ($hasPrice): ?>
        <button class="product-card-add-to-cart" onclick="quickAddToCart(event, <?= $product->id ?>)" aria-label="Добавить в корзину">
            <i class="bi bi-bag-plus"></i> В корзину
        </button>
        <?php else: ?>
        <button class="product-card-add-to-cart" disabled data-disabled="no-price" aria-label="Цена уточняется" title="Цена уточняется — добавление недоступно">
            <i class="bi bi-question-circle"></i> Цена уточняется
        </button>
        <?php endif; ?>

    </div>
</article>
