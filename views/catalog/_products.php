<?php
use yii\helpers\Html;

/** @var $products app\models\Product[] */

$lazyPlaceholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="10" height="10"%3E%3Crect width="10" height="10" fill="%23f3f4f6"/%3E%3C/svg%3E';
?>

<?php if (empty($products)): ?>
    <div class="empty">
        <i class="bi bi-inbox"></i>
        <h3>Товары не найдены</h3>
        <button onclick="resetFilters()">Сбросить фильтры</button>
    </div>
<?php else: ?>
    <?php foreach ($products as $index => $product): ?>
        <?php $isCriticalCard = $index < 4; ?>
        <div class="product product-card modern-card">
            <!-- УЛУЧШЕНО: Hover-эффект смены изображения -->
            <div class="product-image-wrapper">
                <a href="<?= $product->getUrl() ?>" class="product-link">
                    <img
                        class="product-image primary"
                        src="<?= $isCriticalCard ? Html::encode($product->getMainImageUrl()) : $lazyPlaceholder ?>"
                        <?php if (!$isCriticalCard): ?>data-src="<?= Html::encode($product->getMainImageUrl()) ?>"<?php endif; ?>
                        alt="<?= Html::encode($product->name) ?>"
                        <?php if ($isCriticalCard): ?>fetchpriority="high"<?php endif; ?>
                    >
                    
                    <?php if (!empty($product->images[1])): ?>
                    <img
                        class="product-image secondary"
                        src="<?= $lazyPlaceholder ?>"
                        data-src="<?= Html::encode($product->images[1]->getUrl()) ?>"
                        alt="<?= Html::encode($product->name) ?>"
                    >
                    <?php endif; ?>
                </a>
                
                <!-- УЛУЧШЕНО: Компактные бейджи (верхний правый угол) -->
                <div class="product-badges-compact">
                    <?php if ($product->hasDiscount()): ?>
                        <span class="badge-discount">-<?= round((($product->old_price - $product->price) / $product->old_price) * 100) ?>%</span>
                    <?php endif; ?>
                    <?php if (isset($product->created_at) && $product->created_at > time() - 7*24*3600): ?>
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
                    <?php if ($product->brand_name): ?>
                        <span class="product-card-brand"><?= Html::encode($product->brand_name) ?></span>
                    <?php endif; ?>
                    <h3 class="product-card-name"><?= Html::encode($product->getDisplayTitle()) ?></h3>
                    
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
                    
                    <!-- Размеры (показываем в соответствии с выбранной системой измерения) -->
                    <?php if (!empty($product->sizes) && is_array($product->sizes)): ?>
                    <div class="sizes-quick">
                        <?php 
                        // Получаем выбранные размеры из фильтра
                        $selectedSizes = Yii::$app->request->get('sizes');
                        $selectedSizesArray = $selectedSizes ? (is_array($selectedSizes) ? $selectedSizes : explode(',', $selectedSizes)) : [];
                        
                        // Получаем текущую систему размеров из GET параметра или localStorage (через JS)
                        $currentSizeSystem = Yii::$app->request->get('size_system', 'eu');
                        $sizeField = match($currentSizeSystem) {
                            'us' => 'us_size',
                            'uk' => 'uk_size',
                            'cm' => 'cm_size',
                            default => 'eu_size'
                        };
                        
                        $availableSizes = array_filter($product->sizes, function($s) use ($sizeField) { 
                            return $s && isset($s->is_available) && $s->is_available && !empty($s->$sizeField); 
                        });
                        
                        // Сортируем размеры: выбранные первыми
                        if (!empty($selectedSizesArray)) {
                            usort($availableSizes, function($a, $b) use ($selectedSizesArray, $sizeField) {
                                $aSelected = in_array($a->$sizeField, $selectedSizesArray);
                                $bSelected = in_array($b->$sizeField, $selectedSizesArray);
                                if ($aSelected && !$bSelected) return -1;
                                if (!$aSelected && $bSelected) return 1;
                                return 0;
                            });
                        }
                        
                        $shown = 0;
                        foreach ($availableSizes as $size): 
                            if ($shown >= 6) break;
                            $displaySize = $size->$sizeField;
                            if (empty($displaySize)) continue;
                            
                            // Проверяем, выбран ли этот размер
                            $isSelected = in_array($displaySize, $selectedSizesArray);
                        ?>
                            <span class="size-badge <?= $isSelected ? 'selected' : '' ?>" 
                                  onclick="selectQuickSize(event, <?= $product->id ?>, '<?= Html::encode($displaySize) ?>')"><?= Html::encode($displaySize) ?></span>
                        <?php 
                            $shown++;
                        endforeach; 
                        if (count($availableSizes) > 6): ?>
                            <span class="size-more">+<?= count($availableSizes) - 6 ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Цена (актуальная цена с учетом выбранных размеров или диапазон) -->
                    <div class="price product-card-price">
                        <?php 
                        // Проверяем, выбраны ли фильтры размеров
                        $selectedSizes = Yii::$app->request->get('sizes');
                        $currentSizeSystem = Yii::$app->request->get('size_system', 'eu');
                        
                        if ($selectedSizes && !empty($product->sizes)) {
                            // Если размеры отфильтрованы - показываем актуальные цены выбранных размеров
                            $sizeArray = is_array($selectedSizes) ? $selectedSizes : explode(',', $selectedSizes);
                            $sizeField = match($currentSizeSystem) {
                                'us' => 'us_size',
                                'uk' => 'uk_size',
                                'cm' => 'cm_size',
                                default => 'eu_size'
                            };
                            
                            // Находим размеры, которые соответствуют выбранным фильтрам
                            $filteredSizes = array_filter($product->sizes, function($s) use ($sizeArray, $sizeField) {
                                return $s && $s->is_available && in_array($s->$sizeField, $sizeArray) && $s->price_byn > 0;
                            });
                            
                            if (!empty($filteredSizes)) {
                                $prices = array_map(function($s) { return $s->price_byn; }, $filteredSizes);
                                $minPrice = min($prices);
                                $maxPrice = max($prices);
                                
                                if ($minPrice == $maxPrice) {
                                    // Одна актуальная цена
                                    ?>
                                    <span class="current product-card-price-current"><?= Yii::$app->formatter->asCurrency($minPrice, 'BYN') ?></span>
                                    <?php
                                } else {
                                    // Диапазон цен для выбранных размеров
                                    ?>
                                    <span class="current product-card-price-current">
                                        <?= Yii::$app->formatter->asCurrency($minPrice, 'BYN') ?>
                                        <span class="price-separator"> - </span>
                                        <?= Yii::$app->formatter->asCurrency($maxPrice, 'BYN') ?>
                                    </span>
                                    <?php
                                }
                            } else {
                                // Fallback на базовую цену
                                ?>
                                <span class="current product-card-price-current"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
                                <?php
                            }
                        } else {
                            // Если размеры не выбраны - показываем диапазон цен из всех размеров
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
                        <?php } ?>
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
    <?php endforeach; ?>
<?php endif; ?>
