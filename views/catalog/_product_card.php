<?php
use yii\helpers\Html;
/** @var $product app\models\Product */
?>

<div class="product product-card modern-card">
    <a href="<?= $product->getUrl() ?>" class="product-link">
        <!-- УЛУЧШЕНО: Hover-эффект смены изображения -->
        <?php $lazyPlaceholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="10" height="10"%3E%3Crect width="10" height="10" fill="%23f3f4f6"/%3E%3C/svg%3E'; ?>
        <div class="product-image-wrapper">
            <img
                class="product-image primary"
                src="<?= $lazyPlaceholder ?>"
                data-src="<?= Html::encode($product->getMainImageUrl()) ?>"
                alt="<?= Html::encode($product->name) ?>"
            >
        
            <?php if (!empty($product->images[1])): ?>
            <img
                class="product-image secondary"
                src="<?= $lazyPlaceholder ?>"
                data-src="<?= Html::encode($product->images[1]->getUrl()) ?>"
                alt="<?= Html::encode($product->name) ?>"
            >
            <?php endif; ?>
        
        <!-- УЛУЧШЕНО: Компактные бейджи (верхний правый угол) -->
        <div class="product-badges-compact">
            <!-- Премиальный значок "под заказ" -->
            <span class="badge-custom-order">
                <i class="bi bi-clock-history"></i>
                <span>ПОД ЗАКАЗ</span>
            </span>
                <?php if ($product->hasDiscount()): ?>
                    <span class="badge-discount">-<?= round((($product->old_price - $product->price) / $product->old_price) * 100) ?>%</span>
                <?php endif; ?>
                <?php if (isset($product->created_at) && $product->created_at > time() - 7*24*3600): ?>
                    <span class="badge-new">NEW</span>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions (показываются при hover) -->
            <div class="quick-actions">
                <button class="action-btn favorite" 
                        onclick="toggleFav(event,<?= $product->id ?>)"
                        title="В избранное">
                    <i class="bi bi-heart"></i>
                </button>
            </div>
        </div>
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
            
            <!-- Цвета (улучшенные с hover) -->
            <?php if (!empty($product->colors) && is_array($product->colors) && count($product->colors) > 0): ?>
            <div class="colors">
                <?php $shown = 0; foreach ($product->colors as $color): if ($shown >= 5) break; ?>
                    <?php if ($color && isset($color->hex) && isset($color->name)): ?>
                    <span class="dot" 
                          style="background:<?= $color->hex ?>" 
                          title="<?= Html::encode($color->name) ?>"
                          data-product-id="<?= $product->id ?>"
                          data-image="<?= $product->getMainImageUrl() ?>"
                          onmouseenter="changeColorPreview(this, '<?= $product->getMainImageUrl() ?>')"
                          onmouseleave="resetColorPreview(this)"></span>
                    <?php endif; ?>
                <?php $shown++; endforeach; ?>
                <?php if (count($product->colors) > 5): ?>
                    <span class="more">+<?= count($product->colors) - 5 ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Размеры (максимум 6) -->
            <?php if (!empty($product->sizes) && is_array($product->sizes)): ?>
            <div class="sizes-quick">
                <?php 
                $maxSizes = 6; // Лимит для компактности
                $sizesShown = 0;
                foreach ($product->sizes as $size): 
                    if ($sizesShown >= $maxSizes) break;
                    if (!$size || !isset($size->size)) continue;
                    
                    // Формируем tooltip с размерами в разных системах
                    $sizeTooltip = [];
                    if (!empty($size->eu_size)) $sizeTooltip[] = 'EU: ' . $size->eu_size;
                    if (!empty($size->us_size)) $sizeTooltip[] = 'US: ' . $size->us_size;
                    if (!empty($size->uk_size)) $sizeTooltip[] = 'UK: ' . $size->uk_size;
                    if (!empty($size->cm_size)) $sizeTooltip[] = 'CM: ' . $size->cm_size;
                    $tooltipText = !empty($sizeTooltip) ? implode(' | ', $sizeTooltip) : '';
                ?>
                    <span class="size-badge" 
                          <?php if ($tooltipText): ?>
                          title="<?= Html::encode($tooltipText) ?>"
                          <?php endif; ?>><?= Html::encode($size->size) ?></span>
                <?php 
                    $sizesShown++;
                endforeach; 
                
                // Показываем "+N" если размеров больше
                if (count($product->sizes) > $maxSizes): ?>
                    <span class="size-more">+<?= count($product->sizes) - $maxSizes ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Цена (диапазон или фиксированная) -->
            <div class="price product-card-price">
                <?php 
                // Получаем диапазон цен из размеров
                $priceRange = $product->getPriceRange();
                ?>
                <?php if ($priceRange && $product->hasPriceRange()): ?>
                    <!-- Диапазон цен от минимальной до максимальной -->
                    <span class="current product-card-price-current">
                        <?= Yii::$app->formatter->asCurrency($priceRange['min'], 'BYN') ?>
                        <span class="price-separator"> - </span>
                        <?= Yii::$app->formatter->asCurrency($priceRange['max'], 'BYN') ?>
                    </span>
                <?php else: ?>
                    <!-- Одна цена (классический вариант) -->
                    <?php if ($product->hasDiscount()): ?>
                        <span class="old product-card-price-old"><?= Yii::$app->formatter->asCurrency($product->old_price, 'BYN') ?></span>
                        <span class="product-card-discount">-<?= $product->getDiscountPercent() ?>%</span>
                    <?php endif; ?>
                    <span class="current product-card-price-current"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    
    <!-- Кнопка В корзину: ведёт на страницу товара для выбора размера -->
    <div class="product-footer">
        <a href="<?= $product->getUrl() ?>" class="btn-add-to-cart">
            <i class="bi bi-eye"></i>
            <span>Подробнее</span>
        </a>
    </div>
</div>
