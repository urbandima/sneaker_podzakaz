<?php
use yii\helpers\Html;

/** @var app\models\Product $product */
?>

<div class="quick-view-product">
    <div class="qv-layout">
        <!-- Галерея -->
        <div class="qv-gallery">
            <div class="qv-main-image">
                <img src="<?= $product->getMainImageUrl() ?>" alt="<?= Html::encode($product->name) ?>" id="qvMainImg">
                <?php if ($product->hasDiscount()): ?>
                    <span class="qv-discount-badge">-<?= $product->getDiscountPercent() ?>%</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($product->images) && count($product->images) > 1): ?>
                <div class="qv-thumbs">
                    <img src="<?= $product->getMainImageUrl() ?>" alt="" class="qv-thumb active" onclick="changeQvImg('<?= $product->getMainImageUrl() ?>', this)">
                    <?php foreach (array_slice($product->images, 0, 4) as $img): ?>
                        <img src="<?= $img->getUrl() ?>" alt="" class="qv-thumb" onclick="changeQvImg('<?= $img->getUrl() ?>', this)">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Информация -->
        <div class="qv-info">
            <div class="qv-brand"><?= Html::encode($product->brand->name) ?></div>
            <h2 class="qv-title"><?= Html::encode($product->name) ?></h2>
            
            <!-- Рейтинг -->
            <?php if ($product->rating > 0): ?>
                <div class="qv-rating">
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
                    <span class="rating-value"><?= $product->rating ?></span>
                    <span class="reviews-count">(<?= $product->reviews_count ?>)</span>
                </div>
            <?php endif; ?>
            
            <div class="qv-price">
                <?php if ($product->hasDiscount()): ?>
                    <span class="old-price"><?= Yii::$app->formatter->asCurrency($product->old_price, 'BYN') ?></span>
                <?php endif; ?>
                <span class="current-price"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
            </div>
            
            <div class="qv-stock <?= $product->isInStock() ? 'in-stock' : 'out-stock' ?>">
                <i class="bi bi-<?= $product->isInStock() ? 'check-circle' : 'x-circle' ?>"></i>
                <span><?= $product->getStockStatusLabel() ?></span>
            </div>
            
            <?php if ($product->description): ?>
                <div class="qv-description">
                    <?= nl2br(Html::encode(mb_substr($product->description, 0, 200))) ?>
                    <?php if (mb_strlen($product->description) > 200): ?>
                        <span>...</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Размеры -->
            <?php if (!empty($product->availableSizes)): ?>
                <div class="qv-sizes">
                    <h4>Выберите размер</h4>
                    <div class="size-grid">
                        <?php foreach ($product->availableSizes as $size): ?>
                            <label class="size-option">
                                <input type="radio" name="qv_size" value="<?= $size->size ?>">
                                <span><?= Html::encode($size->size) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Цвета -->
            <?php if (!empty($product->colors)): ?>
                <div class="qv-colors">
                    <h4>Выберите цвет</h4>
                    <div class="color-grid">
                        <?php foreach ($product->colors as $color): ?>
                            <label class="color-option" title="<?= Html::encode($color->name) ?>">
                                <input type="radio" name="qv_color" value="<?= Html::encode($color->name) ?>">
                                <span class="color-circle" style="background-color: <?= Html::encode($color->hex) ?>"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Детали товара -->
            <div class="qv-details">
                <div class="detail-row">
                    <span class="detail-label">Артикул:</span>
                    <span class="detail-value"><?= $product->id ?></span>
                </div>
                <?php if ($product->material): ?>
                    <div class="detail-row">
                        <span class="detail-label">Материал:</span>
                        <span class="detail-value"><?= Html::encode($product->material) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($product->season): ?>
                    <div class="detail-row">
                        <span class="detail-label">Сезон:</span>
                        <span class="detail-value"><?= Html::encode($product->season) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Кнопки -->
            <div class="qv-actions">
                <button class="btn-add-cart" onclick="addFromQuickView(<?= $product->id ?>)">
                    <i class="bi bi-cart-plus"></i>
                    Добавить в корзину
                </button>
                <button class="btn-favorite" onclick="toggleFavorite(<?= $product->id ?>, this)">
                    <i class="bi bi-heart"></i>
                </button>
            </div>
            
            <a href="<?= $product->getUrl() ?>" class="qv-full-link">
                Подробнее о товаре →
            </a>
        </div>
    </div>
</div>

<style>
.quick-view-product {
    padding: 0;
}

.qv-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

/* Галерея */
.qv-gallery {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.qv-main-image {
    position: relative;
    background: #f9fafb;
    border-radius: 12px;
    overflow: hidden;
    padding-top: 100%;
}

.qv-main-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.qv-discount-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #ef4444;
    color: #fff;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 700;
}

.qv-thumbs {
    display: flex;
    gap: 0.5rem;
}

.qv-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: border 0.2s;
}

.qv-thumb:hover,
.qv-thumb.active {
    border-color: #000;
}

/* Информация */
.qv-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.qv-brand {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #666;
    letter-spacing: 0.5px;
}

.qv-title {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.2;
    margin: 0;
}

.qv-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qv-rating .stars {
    display: flex;
    gap: 2px;
    color: #fbbf24;
}

.qv-rating .rating-value {
    font-weight: 700;
    font-size: 0.9375rem;
}

.qv-rating .reviews-count {
    color: #666;
    font-size: 0.875rem;
}

.qv-price {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.old-price {
    font-size: 1rem;
    color: #9ca3af;
    text-decoration: line-through;
}

.current-price {
    font-size: 1.75rem;
    font-weight: 900;
    color: #000;
}

.qv-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
}

.qv-stock.in-stock {
    background: #ecfdf5;
    color: #10b981;
}

.qv-stock.out-stock {
    background: #fef2f2;
    color: #ef4444;
}

.qv-description {
    line-height: 1.6;
    color: #666;
    font-size: 0.9375rem;
}

.qv-sizes h4,
.qv-colors h4 {
    font-size: 0.875rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.size-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.size-option input {
    display: none;
}

.size-option span {
    display: block;
    min-width: 48px;
    padding: 0.625rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.size-option input:checked + span {
    border-color: #000;
    background: #000;
    color: #fff;
}

.color-grid {
    display: flex;
    gap: 0.5rem;
}

.color-option input {
    display: none;
}

.color-circle {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid transparent;
    cursor: pointer;
    transition: border 0.2s;
}

.color-option input:checked + .color-circle {
    border-color: #000;
}

.qv-details {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.375rem 0;
}

.detail-label {
    color: #666;
}

.detail-value {
    font-weight: 600;
    color: #000;
}

.qv-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-add-cart {
    flex: 1;
    background: #000;
    color: #fff;
    border: none;
    padding: 1rem;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-add-cart:hover {
    background: #333;
    transform: translateY(-2px);
}

.btn-favorite {
    width: 52px;
    height: 52px;
    background: #f3f4f6;
    border: none;
    border-radius: 10px;
    font-size: 1.25rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-favorite:hover {
    background: #e5e7eb;
}

.btn-favorite.active {
    color: #ef4444;
}

.qv-full-link {
    text-align: center;
    color: #666;
    text-decoration: none;
    font-size: 0.9375rem;
    font-weight: 600;
    padding: 0.75rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.qv-full-link:hover {
    background: #f9fafb;
    color: #000;
}

@media (max-width: 768px) {
    .qv-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function changeQvImg(src, thumb) {
    document.getElementById('qvMainImg').src = src;
    document.querySelectorAll('.qv-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

function addFromQuickView(productId) {
    const sizeInput = document.querySelector('input[name="qv_size"]:checked');
    const colorInput = document.querySelector('input[name="qv_color"]:checked');
    
    const size = sizeInput ? sizeInput.value : null;
    const color = colorInput ? colorInput.value : null;
    
    addToCart(productId, 1, size, color);
    
    // Закрываем Quick View через 1 секунду после добавления
    setTimeout(() => {
        closeQuickView();
    }, 1000);
}
</script>
