<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\ProductCardHelper;

/** @var \app\backend\modules\catalog\models\Product $product */
/** @var bool $isCriticalCard */
/** @var string $selectedSizesParam */
/** @var array $selectedSizesArray */
/** @var string $currentSizeSystem */
/** @var string $sizeField */
/** @var string $lazyPlaceholder */

$brandName = Html::encode($product->brand->name ?? '');
$productName = Html::encode($product->name);
$productUrl = Url::to(['/catalog/product/' . $product->slug]);
$price = Yii::$app->formatter->asDecimal($product->price, 2);
$oldPrice = $product->old_price ? Yii::$app->formatter->asDecimal($product->old_price, 2) : null;
$discount = $product->getDiscountPercentage();

// Изображение
$imageUrl = $product->getMainImageUrl();
$imageAlt = Html::encode($product->name);

// Размеры
$sizes = $product->getAvailableSizes();
$hasSizes = !empty($sizes);

// Избранное
$isFavorite = $product->isFavorite();

// Карточка
?>

<div class="product-card" data-product-id="<?= $product->id ?>">
    <!-- Изображение -->
    <div class="product-card__image">
        <a href="<?= $productUrl ?>" class="product-card__link">
            <?php if ($isCriticalCard): ?>
                <img src="<?= $imageUrl ?>" alt="<?= $imageAlt ?>" loading="eager">
            <?php else: ?>
                <img src="<?= $lazyPlaceholder ?>" data-src="<?= $imageUrl ?>" alt="<?= $imageAlt ?>" loading="lazy" class="lazy">
            <?php endif; ?>
        </a>
        
        <!-- Бейджи -->
        <div class="product-card__badges">
            <?php if ($discount > 0): ?>
                <span class="badge badge--discount">-<?= $discount ?>%</span>
            <?php endif; ?>
            
            <?php if ($product->is_new): ?>
                <span class="badge badge--new">Новинка</span>
            <?php endif; ?>
            
            <?php if ($product->is_hit): ?>
                <span class="badge badge--hit">Хит</span>
            <?php endif; ?>
        </div>
        
        <!-- Кнопка избранного -->
        <button type="button" class="product-card__favorite" onclick="toggleFavorite(<?= $product->id ?>)" aria-label="<?= $isFavorite ? 'Удалить из избранного' : 'Добавить в избранное' ?>">
            <i class="bi <?= $isFavorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
        </button>
        
        <!-- Быстрый просмотр -->
        <button type="button" class="product-card__quick-view" onclick="openQuickView(<?= $product->id ?>)" aria-label="Быстрый просмотр">
            <i class="bi bi-eye"></i>
        </button>
    </div>
    
    <!-- Информация -->
    <div class="product-card__info">
        <!-- Бренд -->
        <div class="product-card__brand">
            <a href="<?= Url::to(['/catalog', 'brands[]' => $product->brand_id]) ?>">
                <?= $brandName ?>
            </a>
        </div>
        
        <!-- Название -->
        <h3 class="product-card__title">
            <a href="<?= $productUrl ?>">
                <?= $productName ?>
            </a>
        </h3>
        
        <!-- Размеры -->
        <?php if ($hasSizes): ?>
        <div class="product-card__sizes">
            <?php foreach (array_slice($sizes, 0, 5) as $size): ?>
            <button type="button" class="size-btn" data-size="<?= Html::encode($size[$sizeField]) ?>">
                <?= Html::encode($size[$sizeField]) ?>
            </button>
            <?php endforeach; ?>
            
            <?php if (count($sizes) > 5): ?>
            <button type="button" class="size-btn size-btn--more" onclick="openSizeSelector(<?= $product->id ?>)">
                +<?= count($sizes) - 5 ?>
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Цена -->
        <div class="product-card__price">
            <?php if ($oldPrice): ?>
            <span class="price price--old"><?= $oldPrice ?> BYN</span>
            <?php endif; ?>
            <span class="price price--current"><?= $price ?> BYN</span>
        </div>
        
        <!-- Кнопка добавления в корзину -->
        <button type="button" class="product-card__add-to-cart" onclick="addToCart(<?= $product->id ?>)" aria-label="Добавить в корзину">
            <i class="bi bi-cart-plus"></i>
            <span>В корзину</span>
        </button>
    </div>
</div>
