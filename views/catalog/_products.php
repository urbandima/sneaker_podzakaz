<?php
use yii\helpers\Html;

/** @var $products app\models\Product[] */
?>

<?php if (empty($products)): ?>
    <div class="empty">
        <i class="bi bi-inbox"></i>
        <h3>Товары не найдены</h3>
        <button onclick="resetFilters()">Сбросить фильтры</button>
    </div>
<?php else: ?>
    <?php foreach ($products as $product): ?>
        <div class="product product-card modern-card">
            <a href="<?= $product->getUrl() ?>" class="product-link">
                <!-- УЛУЧШЕНО: Hover-эффект смены изображения -->
                <div class="product-image-wrapper">
                    <img src="<?= $product->getMainImageUrl() ?>" 
                         alt="<?= Html::encode($product->name) ?>" 
                         loading="lazy" 
                         class="product-image primary">
                    
                    <?php if (!empty($product->images[1])): ?>
                    <img src="<?= $product->images[1]->getUrl() ?>" 
                         alt="<?= Html::encode($product->name) ?>" 
                         loading="lazy" 
                         class="product-image secondary">
                    <?php endif; ?>
                    
                    <!-- УЛУЧШЕНО: Компактные бейджи (верхний правый угол) -->
                    <div class="product-badges-compact">
                        <?php if ($product->hasDiscount()): ?>
                            <span class="badge-discount">-<?= round((($product->old_price - $product->price) / $product->old_price) * 100) ?>%</span>
                        <?php endif; ?>
                        <?php if (isset($product->created_at) && $product->created_at > time() - 7*24*3600): ?>
                            <span class="badge-new">NEW</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- НОВОЕ: Quick Actions (показываются при hover) -->
                    <div class="quick-actions">
                        <button class="action-btn favorite" 
                                onclick="toggleFav(event,<?= $product->id ?>)"
                                title="В избранное">
                            <i class="bi bi-heart"></i>
                        </button>
                        <button class="action-btn compare" 
                                onclick="toggleCompare(event,<?= $product->id ?>)"
                                title="Сравнить">
                            <i class="bi bi-arrow-left-right"></i>
                        </button>
                        <button class="action-btn quick-view" 
                                onclick="openQuickView(event,<?= $product->id ?>)"
                                title="Быстрый просмотр">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="info product-card-body">
                    <?php if ($product->brand): ?>
                        <span class="product-card-brand"><?= Html::encode($product->brand->name) ?></span>
                    <?php endif; ?>
                    <h3 class="product-card-name"><?= Html::encode($product->name) ?></h3>
                    
                    <!-- Рейтинг -->
                    <?php if (isset($product->rating) && $product->rating > 0): ?>
                    <div class="rating">
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
                        <span class="rating-text"><?= $product->rating ?> (<?= $product->reviews_count ?? 0 ?>)</span>
                    </div>
                    <?php endif; ?>
                    
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
                    
                    <!-- Размеры (улучшенные - кликабельные) -->
                    <?php if (!empty($product->sizes) && is_array($product->sizes)): ?>
                    <div class="sizes-quick">
                        <?php 
                        $availableSizes = array_filter($product->sizes, function($s) { return $s && isset($s->is_available) && $s->is_available; });
                        $shown = 0;
                        foreach ($availableSizes as $size): 
                            if ($shown >= 6) break;
                            if (!$size || !isset($size->size)) continue;
                        ?>
                            <span class="size-badge" 
                                  onclick="selectQuickSize(event, <?= $product->id ?>, '<?= Html::encode($size->size) ?>')"><?= Html::encode($size->size) ?></span>
                        <?php 
                            $shown++;
                        endforeach; 
                        if (count($availableSizes) > 6): ?>
                            <span class="size-more">+<?= count($availableSizes) - 6 ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="price product-card-price">
                        <?php if ($product->hasDiscount()): ?>
                            <span class="old product-card-price-old"><?= Yii::$app->formatter->asCurrency($product->old_price, 'BYN') ?></span>
                            <span class="product-card-discount">-<?= $product->getDiscountPercent() ?>%</span>
                        <?php endif; ?>
                        <span class="current product-card-price-current"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
                    </div>
                    <div class="stock <?= $product->isInStock() ? 'in' : 'out' ?>">
                        <i class="bi bi-<?= $product->isInStock() ? 'check-circle' : 'x-circle' ?>"></i>
                        <?= $product->isInStock() ? 'В наличии' : 'Нет в наличии' ?>
                    </div>
                </div>
            </a>
            
            <!-- Кнопка В корзину -->
            <div class="product-footer">
                <button class="btn-add-to-cart" onclick="quickAddToCart(event, <?= $product->id ?>)" type="button">
                    <i class="bi bi-cart-plus"></i>
                    <span>В корзину</span>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
