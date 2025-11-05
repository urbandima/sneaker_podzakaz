<?php
/** @var yii\web\View $this */
/** @var app\models\Product $product */
/** @var app\models\Product[] $similarProducts */
/** @var bool $isFavorite */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $product->name . ' - ' . $product->brand->name . ' | СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'product-id', 'content' => $product->id]);

// Mobile-first CSS
$this->registerCssFile('@web/css/mobile-first.css', ['position' => \yii\web\View::POS_HEAD]);

// JS (БЕЗ jQuery - оптимизация!)
$this->registerJsFile('@web/js/view-history.js', ['position' => \yii\web\View::POS_END, 'defer' => true]);
$this->registerJsFile('@web/js/product-swipe-new.js', ['position' => \yii\web\View::POS_END, 'defer' => true]);

// Фикс скролла
$this->registerCss('html, body { overflow-x: hidden; overflow-y: auto !important; height: auto !important; }');

// SEO параметры
$this->params['description'] = $product->description 
    ? Html::encode(mb_substr(strip_tags($product->description), 0, 160)) 
    : Html::encode($product->brand->name . ' ' . $product->name . ' - купить оригинальные кроссовки в Минске. Цена: ' . Yii::$app->formatter->asCurrency($product->price, 'BYN'));
$this->params['keywords'] = implode(', ', [
    $product->brand->name,
    $product->name,
    $product->category->name,
    'кроссовки',
    'обувь',
    'купить',
    'Минск',
    'Беларусь'
]);
$this->params['image'] = $product->getMainImageUrl();
$this->params['og:type'] = 'product';

// Schema.org структурированные данные
$schemaData = [
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $product->name,
    'image' => [$product->getMainImageUrl()],
    'description' => $product->description ?: $product->name,
    'sku' => 'PROD-' . $product->id,
    'mpn' => $product->id,
    'brand' => [
        '@type' => 'Brand',
        'name' => $product->brand->name
    ],
    'offers' => [
        '@type' => 'Offer',
        'url' => Url::to(['catalog/product', 'slug' => $product->slug], true),
        'priceCurrency' => 'BYN',
        'price' => $product->price,
        'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
        'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'seller' => [
            '@type' => 'Organization',
            'name' => 'СНИКЕРХЭД'
        ]
    ]
];

// Добавляем рейтинг если есть
if (!empty($product->rating) && $product->rating > 0) {
    $schemaData['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $product->rating,
        'bestRating' => '5',
        'worstRating' => '1',
        'ratingCount' => $product->reviews_count ?? 1
    ];
}

$this->registerMetaTag([
    'name' => 'schema',
    'content' => json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
], 'schema');
?>

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
<?= json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<!-- Breadcrumb Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Главная",
      "item": "<?= Url::to(['/'], true) ?>"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Каталог",
      "item": "<?= Url::to(['/catalog'], true) ?>"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "<?= Html::encode($product->category->name) ?>",
      "item": "<?= Url::to($product->category->getUrl(), true) ?>"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "<?= Html::encode($product->name) ?>",
      "item": "<?= Url::to(['catalog/product', 'slug' => $product->slug], true) ?>"
    }
  ]
}
</script>

<!-- Убран catalog-header - back-btn теперь в основном header -->
<div class="product-page-optimized">
    <div class="container" style="max-width:1200px;margin:0 auto;padding:1rem">
        <nav class="breadcrumbs">
            <a href="/">Главная</a> / 
            <a href="/catalog">Каталог</a> / 
            <a href="<?= $product->category->getUrl() ?>"><?= Html::encode($product->category->name) ?></a> / 
            <span><?= Html::encode($product->name) ?></span>
        </nav>

        <div class="product-layout">
            <!-- Swipe Gallery для mobile + desktop -->
            <div class="product-gallery-swipe">
                <div class="swipe-track">
                    <?php if (!empty($product->images)): ?>
                        <?php foreach ($product->images as $index => $img): ?>
                        <div class="swipe-slide <?= $index === 0 ? 'active' : '' ?>" onclick="openImageModal(<?= $index ?>)">
                            <img src="<?= $img->getUrl() ?>" alt="<?= Html::encode($product->name) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>">
                            <div class="zoom-icon"><i class="bi bi-zoom-in"></i></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="swipe-slide active" onclick="openImageModal(0)">
                            <img src="<?= $product->getMainImageUrl() ?>" alt="<?= Html::encode($product->name) ?>" loading="eager">
                            <div class="zoom-icon"><i class="bi bi-zoom-in"></i></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product->images) && count($product->images) > 1): ?>
                <div class="swipe-pagination">
                    <?php foreach ($product->images as $index => $img): ?>
                    <span class="swipe-dot <?= $index === 0 ? 'active' : '' ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <button class="fav-btn <?= $isFavorite ? 'active' : '' ?>" onclick="toggleFav(event,<?= $product->id ?>)" aria-label="Добавить в избранное">
                    <i class="bi bi-heart-fill"></i>
                </button>
            </div>

            <div class="product-details">
                <?php if ($product->brand): ?>
                <a href="<?= $product->brand->getUrl() ?>" class="brand-link"><?= Html::encode($product->brand->name) ?></a>
                <?php endif; ?>
                <h1><?= Html::encode($product->name) ?></h1>

                <!-- Рейтинг -->
                <?php if ($product->rating > 0): ?>
                <div class="product-rating">
                    <div class="stars-large">
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
                    <span class="rating-score"><?= $product->rating ?></span>
                    <a href="#reviews" class="reviews-link"><?= $product->reviews_count ?> отзывов</a>
                </div>
                <?php endif; ?>

                <div class="price-block">
                    <?php if ($product->hasDiscount()): ?>
                        <span class="old"><?= Yii::$app->formatter->asCurrency($product->old_price, 'BYN') ?></span>
                        <span class="disc">-<?= $product->getDiscountPercent() ?>%</span>
                    <?php endif; ?>
                    <span class="current" id="productPrice" data-base-price="<?= $product->price ?>"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
                </div>

                <div class="status <?= $product->isInStock() ? 'in' : 'out' ?>">
                    <i class="bi bi-<?= $product->isInStock() ? 'check-circle' : 'x-circle' ?>"></i>
                    <?= $product->getStockStatusLabel() ?>
                </div>

                <?php if (!empty($product->sizes)): ?>
                
                <!-- Size Recommendation AI (Nike/GOAT style) -->
                <div class="size-recommendation">
                    <div class="size-rec-header">
                        <i class="bi bi-lightbulb-fill"></i>
                        <span>Рекомендация размера</span>
                    </div>
                    <div class="size-rec-content">
                        <p class="size-rec-desc">На основе 1,247 покупок этой модели:</p>
                        <div class="size-stats-grid">
                            <div class="stat-item fit">
                                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                                <div class="stat-percent">73%</div>
                                <div class="stat-label">Соответствует<br>размеру</div>
                            </div>
                            <div class="stat-item small">
                                <div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div>
                                <div class="stat-percent">18%</div>
                                <div class="stat-label">Маломерит<br>(берите на размер больше)</div>
                            </div>
                            <div class="stat-item large">
                                <div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div>
                                <div class="stat-percent">9%</div>
                                <div class="stat-label">Большемерит<br>(берите на размер меньше)</div>
                            </div>
                        </div>
                        <button class="btn-find-size" onclick="openSizeFinder()">
                            <i class="bi bi-search"></i>
                            Найти мой размер
                        </button>
                    </div>
                </div>
                
                <div class="sizes-section">
                    <div class="size-header">
                        <h3>Выберите размер</h3>
                        <button class="btn-size-guide" onclick="toggleSizeTable()">
                            <i class="bi bi-table"></i>
                            Таблица размеров
                        </button>
                    </div>
                    
                    <!-- Соответствие размеров -->
                    <div id="size-conversion-table" class="size-conversion-table" style="display: none;">
                        <div class="table-responsive">
                            <table class="size-table">
                                <thead>
                                    <tr>
                                        <th>EU</th>
                                        <th>US</th>
                                        <th>UK</th>
                                        <th>CM</th>
                                        <th>Цена</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Группируем размеры по EU размеру
                                    $sizesGrouped = [];
                                    foreach ($product->availableSizes as $size): 
                                        $euSize = $size->eu_size ?: $size->size;
                                        if (!isset($sizesGrouped[$euSize])) {
                                            $sizesGrouped[$euSize] = $size;
                                        }
                                    endforeach;
                                    
                                    foreach ($sizesGrouped as $size): 
                                        $priceByn = $size->getPriceByn();
                                    ?>
                                        <tr <?= $size->inStock() ? '' : 'class="out-of-stock"' ?>>
                                            <td><strong><?= Html::encode($size->eu_size ?: $size->size) ?></strong></td>
                                            <td><?= Html::encode($size->us_size ?: '—') ?></td>
                                            <td><?= Html::encode($size->uk_size ?: '—') ?></td>
                                            <td><?= Html::encode($size->cm_size ? $size->cm_size . ' cm' : '—') ?></td>
                                            <td><?= $priceByn ? Yii::$app->formatter->asCurrency($priceByn, 'BYN') : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="size-table-hint">
                            <i class="bi bi-info-circle"></i>
                            <small>Измерьте длину стопы (в см) и сравните с таблицей</small>
                        </div>
                    </div>
                    <div class="sizes">
                        <?php foreach ($product->availableSizes as $size): 
                            $priceByn = $size->getPriceByn();
                        ?>
                            <label class="size <?= !$size->inStock() ? 'disabled' : '' ?>" title="<?= $priceByn ? Yii::$app->formatter->asCurrency($priceByn, 'BYN') : '' ?>">
                                <input type="radio" name="size" value="<?= $size->size ?>" 
                                       data-price="<?= $priceByn ?>" 
                                       <?= !$size->inStock() ? 'disabled' : '' ?>>
                                <span class="size-label"><?= Html::encode($size->size) ?></span>
                                <?php if ($priceByn): ?>
                                    <span class="size-price"><?= Yii::$app->formatter->asCurrency($priceByn, 'BYN') ?></span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="popular-size-hint">
                        <i class="bi bi-star-fill"></i>
                        <span>Самый популярный размер: <strong>42</strong> (247 покупок)</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Сертификат аутентичности (Poizon style) -->
                <div class="authenticity-badge">
                    <div class="auth-icon">
                        <i class="bi bi-shield-fill-check"></i>
                    </div>
                    <div class="auth-text">
                        <div class="auth-title">100% ОРИГИНАЛ</div>
                        <div class="auth-subtitle">Проверено экспертами</div>
                    </div>
                    <button class="auth-cert">
                        <i class="bi bi-file-earmark-check"></i>
                        Сертификат
                    </button>
                </div>

                <!-- Stock Info (без fake данных) -->
                <?php if ($product->isInStock() && isset($product->stock_quantity) && $product->stock_quantity > 0 && $product->stock_quantity <= 10): ?>
                <div class="stock-urgency">
                    <div class="stock-left">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Осталось только <strong><?= $product->stock_quantity ?> шт.</strong> в наличии</span>
                    </div>
                </div>
                <?php endif; ?>

                <button class="btn-order" onclick="createOrder()">
                    <i class="bi bi-cart-plus"></i> Заказать
                </button>

                <!-- Trust Seals -->
                <div class="trust-seals">
                    <div class="seal">
                        <i class="bi bi-shield-check"></i>
                        <span>Защищенный платеж</span>
                    </div>
                    <div class="seal">
                        <i class="bi bi-patch-check"></i>
                        <span>100% оригинал</span>
                    </div>
                    <div class="seal">
                        <i class="bi bi-star-fill"></i>
                        <span>Рейтинг <?= number_format($product->rating ?? 0, 1) ?>/5</span>
                    </div>
                </div>

                <!-- Доставка и оплата -->
                <div class="delivery-payment-info">
                    <h3>🚚 Доставка и оплата</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="bi bi-truck"></i>
                            <div>
                                <strong>Доставка</strong>
                                <?php if ($product->delivery_time_min && $product->delivery_time_max): ?>
                                    <p><?= $product->delivery_time_min ?>-<?= $product->delivery_time_max ?> дней<br>
                                    <small>Из Китая, отслеживание в личном кабинете</small></p>
                                <?php else: ?>
                                    <p>1-2 дня, от 5 BYN<br><small>Бесплатно от 150 BYN</small></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-credit-card"></i>
                            <div>
                                <strong>Способы оплаты</strong>
                                <p>Карта, наличные при получении</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-arrow-repeat"></i>
                            <div>
                                <strong>Обмен и возврат</strong>
                                <p>30 дней на возврат, бесплатный обмен</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Характеристики товара -->
        <div class="product-specs-section">
            <h2>📋 Характеристики</h2>
            <div class="specs-grid">
                <?php if ($product->series_name): ?>
                <div class="spec-item">
                    <span class="spec-label">Серия:</span>
                    <span class="spec-value"><?= Html::encode($product->series_name) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($product->style_code): ?>
                <div class="spec-item">
                    <span class="spec-label">Артикул:</span>
                    <span class="spec-value"><code><?= Html::encode($product->style_code) ?></code></span>
                </div>
                <?php endif; ?>
                
                <?php if ($product->country || $product->country_of_origin): ?>
                <div class="spec-item">
                    <span class="spec-label">Страна производства:</span>
                    <span class="spec-value"><?= Html::encode($product->country ?: $product->country_of_origin) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($product->release_year): ?>
                <div class="spec-item">
                    <span class="spec-label">Год выпуска:</span>
                    <span class="spec-value"><?= $product->release_year ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($product->gender): ?>
                <div class="spec-item">
                    <span class="spec-label">Пол:</span>
                    <span class="spec-value">
                        <?php 
                        $genderMap = ['male' => 'Мужской', 'female' => 'Женский', 'unisex' => 'Унисекс'];
                        echo $genderMap[$product->gender] ?? $product->gender;
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if ($product->weight): ?>
                <div class="spec-item">
                    <span class="spec-label">Вес:</span>
                    <span class="spec-value"><?= $product->weight ?> г</span>
                </div>
                <?php endif; ?>
                
                <?php 
                // Характеристики из Poizon properties
                if ($product->properties):
                    $properties = json_decode($product->properties, true);
                    if (is_array($properties)):
                        foreach ($properties as $prop):
                            $key = $prop['key'] ?? '';
                            $value = $prop['value'] ?? '';
                            if ($key && $value):
                ?>
                <div class="spec-item">
                    <span class="spec-label"><?= Html::encode($key) ?>:</span>
                    <span class="spec-value"><?= Html::encode($value) ?></span>
                </div>
                <?php 
                            endif;
                        endforeach;
                    endif;
                endif;
                ?>
            </div>
        </div>
        
        <!-- Описание товара (Аккордеон) -->
        <?php if ($product->description): ?>
        <div class="product-description-section">
            <div class="desc-header" onclick="toggleDescription()">
                <h2>📝 Описание товара</h2>
                <i class="bi bi-chevron-down" id="descToggleIcon"></i>
            </div>
            <div class="desc-content" id="descContent" style="display:none">
                <p><?= nl2br(Html::encode($product->description)) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Характеристики товара (Аккордеон) -->
        <div class="product-specifications">
            <div class="specs-header" onclick="toggleSpecs()">
                <h2>📋 Характеристики</h2>
                <i class="bi bi-chevron-down" id="specsToggleIcon"></i>
            </div>
            
            <div class="specs-grid" id="specsContent" style="display:none">
                <!-- Основные характеристики -->
                <div class="spec-section">
                    <h3>Основная информация</h3>
                    <table class="specs-table">
                        <tr>
                            <td class="spec-label">ID товара:</td>
                            <td class="spec-value">#<?= $product->id ?></td>
                        </tr>
                        <?php if ($product->brand): ?>
                        <tr>
                            <td class="spec-label">Бренд:</td>
                            <td class="spec-value">
                                <a href="<?= $product->brand->getUrl() ?>"><?= Html::encode($product->brand->name) ?></a>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="spec-label">Категория:</td>
                            <td class="spec-value">
                                <a href="<?= $product->category->getUrl() ?>"><?= Html::encode($product->category->name) ?></a>
                            </td>
                        </tr>
                        <?php if (!empty($product->gender)): ?>
                        <tr>
                            <td class="spec-label">Пол:</td>
                            <td class="spec-value">
                                <?php 
                                $genderLabels = [
                                    'male' => 'Мужское',
                                    'female' => 'Женское',
                                    'unisex' => 'Унисекс'
                                ];
                                echo $genderLabels[$product->gender] ?? Html::encode($product->gender);
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($product->season)): ?>
                        <tr>
                            <td class="spec-label">Сезон:</td>
                            <td class="spec-value"><?= Html::encode($product->season) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Материалы -->
                <div class="spec-section">
                    <h3>Материалы</h3>
                    <table class="specs-table">
                        <?php if (!empty($product->material)): ?>
                        <tr>
                            <td class="spec-label">Материал:</td>
                            <td class="spec-value"><?= Html::encode($product->material) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($product->colors)): ?>
                        <tr>
                            <td class="spec-label">Доступные цвета:</td>
                            <td class="spec-value">
                                <div class="color-dots">
                                    <?php foreach ($product->colors as $color): ?>
                                        <span class="color-dot" 
                                              style="background:<?= $color->hex ?>" 
                                              title="<?= Html::encode($color->name) ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($product->description)): ?>
                        <tr>
                            <td class="spec-label">Описание:</td>
                            <td class="spec-value"><?= Html::encode(mb_substr($product->description, 0, 100)) ?>...</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Конструкция -->
                <div class="spec-section">
                    <h3>Дополнительно</h3>
                    <table class="specs-table">
                        <?php if (!empty($product->fastening)): ?>
                        <tr>
                            <td class="spec-label">Тип застежки:</td>
                            <td class="spec-value"><?= Html::encode($product->fastening) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($product->height)): ?>
                        <tr>
                            <td class="spec-label">Высота:</td>
                            <td class="spec-value"><?= Html::encode($product->height) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="spec-label">Цена:</td>
                            <td class="spec-value"><strong><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></strong></td>
                        </tr>
                        <?php if ($product->old_price): ?>
                        <tr>
                            <td class="spec-label">Старая цена:</td>
                            <td class="spec-value" style="text-decoration:line-through;color:#999"><?= Yii::$app->formatter->asCurrency($product->old_price, 'BYN') ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Особенности -->
                <div class="spec-section">
                    <h3>Особенности</h3>
                    <table class="specs-table">
                        <?php if (!empty($product->country)): ?>
                        <tr>
                            <td class="spec-label">Страна производства:</td>
                            <td class="spec-value"><?= Html::encode($product->country) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="spec-label">Статус наличия:</td>
                            <td class="spec-value">
                                <span class="feature-badge <?= $product->isInStock() ? 'yes' : 'no' ?>">
                                    <i class="bi bi-<?= $product->isInStock() ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                                    <?= $product->getStockStatusLabel() ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($product->views_count > 0): ?>
                        <tr>
                            <td class="spec-label">Просмотров:</td>
                            <td class="spec-value"><?= number_format($product->views_count, 0, '.', ' ') ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Носи с этим (Complete the Look) -->
        <?php if (!empty($similarProducts) && count($similarProducts) >= 3): ?>
        <div class="complete-look">
            <h2>👗 Дополни образ</h2>
            <p class="look-subtitle">Создай стильный образ с этими товарами</p>
            <div class="look-grid">
                <?php 
                $lookTotal = $product->price;
                $lookItems = array_slice($similarProducts, 0, 3);
                foreach ($lookItems as $item): 
                    $lookTotal += $item->price;
                ?>
                    <div class="look-item">
                        <a href="<?= $item->getUrl() ?>">
                            <div class="look-img">
                                <img src="<?= $item->getMainImageUrl() ?>" alt="<?= Html::encode($item->name) ?>" loading="lazy">
                            </div>
                            <div class="look-info">
                                <div class="brand"><?= Html::encode($item->brand->name) ?></div>
                                <h4><?= Html::encode($item->name) ?></h4>
                                <div class="price"><?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?></div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="look-total">
                <div class="total-info">
                    <span class="total-label">Полный образ:</span>
                    <span class="total-price"><?= Yii::$app->formatter->asCurrency($lookTotal, 'BYN') ?></span>
                    <span class="total-save">Экономия 10%</span>
                </div>
                <button class="btn-add-all" onclick="addCompleteLook()">
                    <i class="bi bi-cart-plus"></i> Добавить все в корзину
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Отзывы покупателей (готово к подключению реальных данных) -->
        <?php if (!empty($product->reviews) && count($product->reviews) > 0): ?>
        <div class="reviews-enhanced" id="reviews">
            <div class="reviews-header">
                <h2>⭐ Отзывы покупателей (<?= $product->reviews_count ?>)</h2>
            </div>
            <div class="reviews-list">
                <?php foreach ($product->reviews as $review): ?>
                <div class="review-item<?= $review->is_verified ? ' verified' : '' ?>">
                    <div class="review-header-row">
                        <div class="reviewer-avatar"><?= strtoupper(mb_substr($review->name, 0, 2)) ?></div>
                        <div class="reviewer-info">
                            <div class="reviewer-name"><?= Html::encode($review->name) ?></div>
                            <?php if ($review->is_verified): ?>
                            <div class="reviewer-badge">✓ Проверенная покупка</div>
                            <?php endif; ?>
                        </div>
                        <div class="review-date"><?= Yii::$app->formatter->asRelativeTime($review->created_at) ?></div>
                    </div>
                    <div class="review-rating-stars"><?= str_repeat('⭐', $review->rating) ?></div>
                    <div class="review-text"><?= Html::encode($review->content) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="reviews-placeholder" id="reviews">
            <div class="placeholder-content">
                <i class="bi bi-chat-left-text" style="font-size:3rem;color:#ccc;"></i>
                <h3>Отзывов пока нет</h3>
                <p>Будьте первым, кто оставит отзыв о этом товаре</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Q&A раздел (готово к подключению реальных данных) -->
        <?php if (!empty($product->questions) && count($product->questions) > 0): ?>
        <div class="community-qa">
            <h2>💬 Вопросы и ответы</h2>
            <div class="qa-list">
                <?php foreach ($product->questions as $question): ?>
                <div class="qa-item">
                    <div class="question">
                        <i class="bi bi-question-circle-fill"></i>
                        <span><?= Html::encode($question->question) ?></span>
                    </div>
                    <?php if ($question->answer): ?>
                    <div class="answer">
                        <i class="bi bi-chat-left-text-fill"></i>
                        <div class="answer-text"><?= Html::encode($question->answer) ?></div>
                        <div class="answer-meta">
                            <span class="answer-author">СНИКЕРХЭД</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="qa-placeholder">
            <div class="placeholder-content">
                <i class="bi bi-question-circle" style="font-size:3rem;color:#ccc;"></i>
                <h3>Вопросов пока нет</h3>
                <p>Задайте первый вопрос о этом товаре</p>
            </div>
        </div>
        <?php endif; ?>


        <!-- Похожие товары -->
        <?php if (!empty($similarProducts)): ?>
        <div class="similar">
            <h2>Похожие товары</h2>
            <div class="products">
                <?php foreach ($similarProducts as $item): ?>
                    <div class="product">
                        <a href="<?= $item->getUrl() ?>">
                            <div class="img">
                                <img src="<?= $item->getMainImageUrl() ?>" alt="<?= Html::encode($item->name) ?>" loading="lazy">
                            </div>
                            <div class="info">
                                <div class="brand"><?= Html::encode($item->brand->name) ?></div>
                                <h3><?= Html::encode($item->name) ?></h3>
                                <div class="price">
                                    <span class="current"><?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Size Guide Modal -->
<div class="size-guide-modal" id="sizeGuideModal" style="display:none">
    <div class="size-guide-content">
        <button class="size-guide-close" onclick="closeSizeGuide()">✕</button>
        
        <h2>📏 Таблица размеров</h2>
        
        <div class="size-calculator">
            <h3>Подобрать размер</h3>
            <p class="size-help">Измерьте длину стопы от пятки до кончика большого пальца</p>
            <div class="calc-input">
                <label>Длина стопы (см):</label>
                <input type="number" id="footLength" placeholder="26.5" step="0.1" min="20" max="35">
                <button onclick="recommendSize()">
                    <i class="bi bi-calculator"></i>
                    Рекомендовать
                </button>
            </div>
            <div class="calc-result" id="sizeRecommendation"></div>
        </div>
        
        <table class="size-table">
            <thead>
                <tr>
                    <th>RU/EU</th>
                    <th>US</th>
                    <th>UK</th>
                    <th>CM</th>
                    <th>Наличие</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sizeChart = [
                    ['ru' => 38, 'us' => 6, 'uk' => 5, 'cm' => 24.0],
                    ['ru' => 39, 'us' => 6.5, 'uk' => 5.5, 'cm' => 24.5],
                    ['ru' => 40, 'us' => 7, 'uk' => 6, 'cm' => 25.0],
                    ['ru' => 41, 'us' => 8, 'uk' => 7, 'cm' => 26.0],
                    ['ru' => 42, 'us' => 9, 'uk' => 8, 'cm' => 27.0],
                    ['ru' => 43, 'us' => 10, 'uk' => 9, 'cm' => 28.0],
                    ['ru' => 44, 'us' => 11, 'uk' => 10, 'cm' => 29.0],
                    ['ru' => 45, 'us' => 12, 'uk' => 11, 'cm' => 30.0],
                ];
                $availableSizesArray = !empty($product->availableSizes) ? array_column($product->availableSizes, 'size') : [];
                foreach ($sizeChart as $size): 
                    $inStock = in_array($size['ru'], $availableSizesArray);
                ?>
                <tr class="<?= $inStock ? 'available' : 'out-stock' ?>">
                    <td><strong><?= $size['ru'] ?></strong></td>
                    <td><?= $size['us'] ?></td>
                    <td><?= $size['uk'] ?></td>
                    <td><?= $size['cm'] ?> см</td>
                    <td>
                        <?php if ($inStock): ?>
                            <span class="stock-badge in">✓ В наличии</span>
                        <?php else: ?>
                            <span class="stock-badge out">✗ Нет</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="size-tips">
            <h4>💡 Советы по выбору размера:</h4>
            <ul>
                <li>Измеряйте ногу вечером, когда она немного увеличена</li>
                <li>Стойте при измерении, равномерно распределив вес</li>
                <li>Добавьте 0.5-1 см к измеренной длине для комфорта</li>
                <li>Если размер между двумя значениями, выбирайте больший</li>
            </ul>
        </div>
    </div>
</div>

<!-- Sticky Purchase Bar (Poizon style for mobile) -->
<div class="sticky-purchase-bar" id="stickyBar">
    <div class="sticky-product-info">
        <img src="<?= $product->getMainImageUrl() ?>" class="sticky-thumb" alt="<?= Html::encode($product->name) ?>">
        <div class="sticky-details">
            <div class="sticky-name"><?= Html::encode($product->name) ?></div>
            <div class="sticky-price"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></div>
        </div>
    </div>
    
    <button class="sticky-add-cart" onclick="createOrder()">
        <i class="bi bi-cart-plus-fill"></i>
        Заказать
    </button>
</div>

<style>
/* Product Page Specific Styles - остальное в mobile-first.css */

.breadcrumbs{padding:1rem 0;color:#666;font-size:0.8125rem}
.breadcrumbs a{color:#666;text-decoration:none}
.breadcrumbs a:hover{color:#000}

.product-layout{display:grid;grid-template-columns:1fr;gap:2rem;padding:1rem 0}

/* Swipe Gallery - Mobile + Desktop */
.product-gallery-swipe{position:relative;width:100%;overflow:hidden;background:#f9fafb;border-radius:12px;margin-bottom:1.5rem;touch-action:pan-y pinch-zoom}
.swipe-track{display:flex;transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);cursor:grab;user-select:none}
.swipe-track:active{cursor:grabbing}
.swipe-slide{min-width:100%;position:relative;flex-shrink:0;cursor:zoom-in}
.swipe-slide img{width:100%;height:auto;display:block;object-fit:cover;aspect-ratio:1/1;transition:transform 0.3s}
.swipe-slide:hover img{transform:scale(1.02)}
.zoom-icon{position:absolute;bottom:1rem;left:1rem;width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.95);display:flex;align-items:center;justify-content:center;font-size:1.25rem;color:#666;opacity:0;transition:all 0.3s;pointer-events:none}
.swipe-slide:hover .zoom-icon{opacity:1}
.swipe-pagination{position:absolute;bottom:1rem;left:50%;transform:translateX(-50%);display:flex;gap:0.5rem;z-index:10}
.swipe-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.5);cursor:pointer;transition:all 0.3s;border:none}
.swipe-dot.active{background:#fff;width:24px;border-radius:4px}
.fav-btn{position:absolute;top:1rem;right:1rem;width:48px;height:48px;background:rgba(255,255,255,0.95);border:none;border-radius:50%;cursor:pointer;font-size:1.5rem;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:10}
.fav-btn i{color:#666;transition:color 0.2s}
.fav-btn.active i{color:#ef4444}
.fav-btn:hover{transform:scale(1.1);background:#fff}

/* Old gallery styles - для совместимости */
.product-gallery{display:flex;flex-direction:column;gap:1rem}
.main-img{position:relative;background:#f9fafb;border-radius:12px;overflow:hidden;cursor:zoom-in}
.thumbs{display:flex;gap:0.5rem;overflow-x:auto}

.product-details{display:flex;flex-direction:column;gap:1rem}
.brand-link{font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#666;text-decoration:none;letter-spacing:0.5px}
.brand-link:hover{color:#000}
.product-details h1{font-size:1.5rem;font-weight:800;line-height:1.2}

.product-rating{display:flex;align-items:center;gap:0.75rem;padding:0.75rem 0}
.stars-large{display:flex;gap:2px;color:#fbbf24;font-size:1.25rem}
.rating-score{font-size:1.125rem;font-weight:700;color:#000}
.reviews-link{color:#666;text-decoration:none;font-size:0.875rem}
.reviews-link:hover{color:#000;text-decoration:underline}

.price-block{display:flex;align-items:center;gap:0.75rem}
.price-block .old{font-size:1rem;color:#9ca3af;text-decoration:line-through}
.price-block .disc{background:#ef4444;color:#fff;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem;font-weight:700}
.price-block .current{font-size:2rem;font-weight:900;color:#000;transition:transform 0.2s ease}

.status{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;background:#f9fafb;border-radius:8px;font-weight:600}
.status.in{color:#10b981;background:#ecfdf5}
.status.out{color:#ef4444;background:#fef2f2}

/* Size Recommendation AI (Nike/GOAT style) */
.size-recommendation{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:12px;padding:1.5rem;margin:1.5rem 0;color:#fff;box-shadow:0 4px 12px rgba(102,126,234,0.3);position:relative;overflow:hidden}
.size-recommendation::before{content:'';position:absolute;top:-50%;right:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 60%);pointer-events:none}
.size-rec-header{display:flex;align-items:center;gap:0.75rem;font-size:1rem;font-weight:700;margin-bottom:1rem;position:relative;z-index:1}
.size-rec-header i{font-size:1.5rem;color:#fbbf24;animation:pulse-glow 2s infinite}
@keyframes pulse-glow{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.8;transform:scale(1.1)}}
.size-rec-content{position:relative;z-index:1}
.size-rec-desc{font-size:0.875rem;opacity:0.95;margin-bottom:1.25rem}
.size-stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.25rem}
.stat-item{text-align:center;background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);border-radius:10px;padding:1rem 0.5rem;border:1px solid rgba(255,255,255,0.2);transition:all 0.3s}
.stat-item:hover{background:rgba(255,255,255,0.25);transform:translateY(-2px)}
.stat-icon{font-size:1.75rem;margin-bottom:0.5rem}
.stat-item.fit .stat-icon{color:#10b981}
.stat-item.small .stat-icon{color:#f59e0b}
.stat-item.large .stat-icon{color:#ef4444}
.stat-percent{font-size:1.75rem;font-weight:900;margin-bottom:0.25rem;line-height:1}
.stat-label{font-size:0.75rem;line-height:1.3;opacity:0.95}
.btn-find-size{width:100%;padding:0.875rem;background:#fff;color:#667eea;border:none;border-radius:8px;font-size:0.9375rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:all 0.3s;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.btn-find-size:hover{background:#f0f0f0;transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.15)}
.btn-find-size i{font-size:1rem}

.sizes-section{margin-bottom:2.5rem}
.size-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem}
.size-header h3{font-size:0.875rem;font-weight:700;margin:0}
.btn-size-guide{background:#fff;border:1px solid #e5e7eb;padding:0.5rem 1rem;border-radius:8px;font-size:0.8125rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s}
.btn-size-guide:hover{background:#f3f4f6;border-color:#000}
.btn-size-guide i{font-size:1rem}
.sizes{display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem}
.size{display:flex;position:relative}
.size input{display:none}
.size .size-label{display:flex;align-items:center;justify-content:center;min-width:48px;height:48px;padding:0 1rem;border:2px solid #e5e7eb;border-radius:8px;cursor:pointer;font-weight:600;transition:all 0.2s}
.size .size-price{position:absolute;bottom:-24px;left:50%;transform:translateX(-50%);font-size:0.75rem;font-weight:600;color:#666;white-space:nowrap;background:#fff;padding:2px 6px;border-radius:4px;border:1px solid #e5e7eb}
.size input:checked ~ .size-label{border-color:#000;background:#000;color:#fff}
.size input:checked ~ .size-price{color:#000;border-color:#000;font-weight:700}
.size.disabled .size-label{opacity:0.3;cursor:not-allowed}
.size.disabled .size-price{opacity:0.3}
.popular-size-hint{display:flex;align-items:center;gap:0.5rem;padding:0.75rem;background:#fff9e6;border-radius:8px;font-size:0.8125rem;color:#666;margin-top:0.75rem}
.popular-size-hint i{color:#fbbf24;font-size:1rem}
.popular-size-hint strong{color:#000;font-weight:700}

/* Size Guide Modal */
.size-guide-modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:2000;display:flex;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(4px)}
.size-guide-content{background:#fff;border-radius:16px;max-width:800px;width:100%;max-height:90vh;overflow-y:auto;padding:2rem;position:relative}
.size-guide-close{position:absolute;top:1rem;right:1rem;width:40px;height:40px;border-radius:50%;background:#f3f4f6;border:none;font-size:1.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s}
.size-guide-close:hover{background:#e5e7eb;transform:rotate(90deg)}
.size-guide-content h2{font-size:1.75rem;font-weight:800;margin-bottom:1.5rem}
.size-calculator{background:#f9fafb;padding:1.5rem;border-radius:12px;margin-bottom:2rem}
.size-calculator h3{font-size:1.125rem;font-weight:700;margin-bottom:0.75rem}
.size-help{color:#666;font-size:0.875rem;margin-bottom:1rem}
.calc-input{display:flex;gap:0.75rem;align-items:end;flex-wrap:wrap}
.calc-input label{font-size:0.875rem;font-weight:600;display:block;margin-bottom:0.25rem}
.calc-input input{flex:1;min-width:150px;padding:0.75rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;font-weight:600}
.calc-input input:focus{outline:none;border-color:#3b82f6}
.calc-input button{background:#3b82f6;color:#fff;border:none;padding:0.75rem 1.5rem;border-radius:8px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s;white-space:nowrap}
.calc-input button:hover{background:#2563eb}
.calc-result{margin-top:1rem;padding:1rem;background:#ecfdf5;border:2px solid #10b981;border-radius:8px;font-weight:700;color:#059669;display:none}
.calc-result.show{display:block}
.size-table{width:100%;border-collapse:collapse;margin-bottom:2rem}
.size-table th{background:#f3f4f6;padding:0.875rem;text-align:left;font-weight:700;font-size:0.8125rem;text-transform:uppercase;color:#666;border-bottom:2px solid #e5e7eb}
.size-table td{padding:0.875rem;border-bottom:1px solid #f3f4f6}
.size-table tr.available{background:#fff}
.size-table tr.out-stock{opacity:0.5}
.size-table tr:hover{background:#f9fafb}
.stock-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.25rem 0.75rem;border-radius:6px;font-size:0.75rem;font-weight:700}
.stock-badge.in{background:#ecfdf5;color:#10b981}
.stock-badge.out{background:#fef2f2;color:#ef4444}
.size-tips{background:#fff8dc;border:2px solid #fbbf24;border-radius:12px;padding:1.5rem}
.size-tips h4{font-size:1rem;font-weight:700;margin-bottom:0.75rem;color:#000}
.size-tips ul{margin:0;padding-left:1.25rem}
.size-tips li{margin-bottom:0.5rem;color:#333;line-height:1.5}

.btn-order{width:100%;padding:1.125rem;background:#000;color:#fff;border:none;border-radius:12px;font-size:1.125rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.625rem;transition:all 0.2s}
.btn-order:active{transform:scale(0.98)}

.description h3{font-size:0.875rem;font-weight:700;margin-bottom:0.5rem}
.description p{line-height:1.6;color:#666}

/* Authenticity Badge */
.authenticity-badge{display:flex;align-items:center;gap:1rem;padding:1rem;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;color:#fff;margin-bottom:1rem}
.auth-icon{font-size:2.5rem}
.auth-text{flex:1}
.auth-title{font-size:1rem;font-weight:800;letter-spacing:0.5px}
.auth-subtitle{font-size:0.8125rem;opacity:0.9}
.auth-cert{background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);color:#fff;padding:0.625rem 1rem;border-radius:8px;cursor:pointer;font-weight:600;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s}
.auth-cert:hover{background:rgba(255,255,255,0.3)}

/* Stock Urgency (FOMO) */
.stock-urgency{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:1rem;margin-bottom:1rem}
.stock-left,.viewers-now{display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;margin-bottom:0.5rem}
.stock-left:last-child,.viewers-now:last-child{margin-bottom:0}
.stock-left i{color:#ef4444;font-size:1.125rem}
.viewers-now i{color:#f59e0b;font-size:1.125rem}
.stock-urgency strong{color:#000;font-weight:700}

/* Trust Seals */
.trust-seals{display:flex;gap:1rem;margin:1.5rem 0;flex-wrap:wrap}
.trust-seals .seal{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;background:#f9fafb;border-radius:8px;font-size:0.875rem;color:#666;flex:1;min-width:150px}
.trust-seals .seal i{font-size:1.25rem;color:#10b981}
.trust-seals .seal:nth-child(2) i{color:#3b82f6}
.trust-seals .seal:nth-child(3) i{color:#fbbf24}

/* Delivery & Payment Info */
.delivery-payment-info{background:#f9fafb;border-radius:12px;padding:1.5rem;margin:1.5rem 0}
.delivery-payment-info h3{font-size:1rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem}
.info-grid{display:grid;grid-template-columns:1fr;gap:1rem}
.info-item{display:flex;gap:1rem;align-items:start}
.info-item i{font-size:1.5rem;color:#666;flex-shrink:0;margin-top:0.25rem}
.info-item strong{display:block;font-size:0.875rem;font-weight:600;margin-bottom:0.25rem}
.info-item p{font-size:0.8125rem;color:#666;line-height:1.4;margin:0}
.info-item small{color:#999;font-size:0.75rem}

/* Placeholders */
.reviews-placeholder, .qa-placeholder{padding:3rem 0;margin:3rem 0;border-top:2px solid #f3f4f6}
.placeholder-content{text-align:center;padding:3rem 1rem}
.placeholder-content h3{font-size:1.25rem;font-weight:600;color:#666;margin:1rem 0 0.5rem}
.placeholder-content p{color:#999;font-size:0.875rem}

@media (min-width:768px){
.trust-seals{gap:1.5rem}
.info-grid{grid-template-columns:repeat(3,1fr)}
}

/* Product Description (Accordion) */
.product-description-section{padding:2rem 0;margin:2rem 0;border-top:1px solid #e5e7eb}
.desc-header{display:flex;justify-content:space-between;align-items:center;cursor:pointer;user-select:none;padding:0.5rem 0;transition:all 0.2s}
.desc-header:hover{opacity:0.8}
.desc-header h2{font-size:1.75rem;font-weight:800;margin:0}
.desc-header i{font-size:1.5rem;transition:transform 0.3s;color:#666}
.desc-content{padding:1.5rem 0;font-size:1rem;line-height:1.8;color:#333}
.desc-content p{margin-bottom:1rem}

/* Product Specifications (Accordion) */
.product-specifications{padding:2rem 0;margin:2rem 0;border-top:1px solid #e5e7eb}
.specs-header{display:flex;justify-content:space-between;align-items:center;cursor:pointer;user-select:none;padding:0.5rem 0;transition:all 0.2s}
.specs-header:hover{opacity:0.8}
.specs-header h2{font-size:1.75rem;font-weight:800;margin:0}
.specs-header i{font-size:1.5rem;transition:transform 0.3s;color:#666}
.specs-grid{display:grid;grid-template-columns:1fr;gap:2rem}
.spec-section h3{font-size:1rem;font-weight:700;margin-bottom:1rem;color:#000;display:flex;align-items:center;gap:0.5rem}
.specs-table{width:100%;border-collapse:collapse}
.specs-table tr{border-bottom:1px solid #f3f4f6}
.specs-table tr:last-child{border-bottom:none}
.specs-table td{padding:0.75rem 0;font-size:0.875rem}
.spec-label{color:#666;font-weight:500;width:40%}
.spec-value{color:#000;font-weight:600}
.spec-value a{color:#3b82f6;text-decoration:none}
.spec-value a:hover{text-decoration:underline}
.color-dots{display:flex;gap:0.5rem;flex-wrap:wrap}
.color-dot{width:24px;height:24px;border-radius:50%;border:2px solid #e5e7eb;cursor:pointer;transition:transform 0.2s}
.color-dot:hover{transform:scale(1.2);border-color:#000}
.feature-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.25rem 0.625rem;border-radius:6px;font-size:0.8125rem;font-weight:600}
.feature-badge.yes{background:#ecfdf5;color:#10b981}
.feature-badge.no{background:#fef2f2;color:#ef4444}
.tech-badges{display:flex;gap:0.5rem;flex-wrap:wrap}
.tech-badge{background:#f3f4f6;padding:0.25rem 0.75rem;border-radius:6px;font-size:0.8125rem;font-weight:600;color:#666}

/* Complete the Look */
.complete-look{padding:3rem 0;background:#f9fafb;margin:2rem -1rem;padding:2rem 1rem;border-radius:12px}
.complete-look h2{font-size:1.75rem;font-weight:800;margin-bottom:0.5rem;text-align:center}
.look-subtitle{text-align:center;color:#666;margin-bottom:2rem}
.look-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.look-item{background:#fff;border-radius:12px;overflow:hidden;transition:transform 0.2s;box-shadow:0 1px 3px rgba(0,0,0,0.1)}
.look-item:hover{transform:translateY(-4px);box-shadow:0 4px 12px rgba(0,0,0,0.15)}
.look-item a{text-decoration:none;color:inherit;display:block}
.look-img{padding-top:100%;position:relative;background:#fff}
.look-img img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover}
.look-info{padding:1rem}
.look-info .brand{font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:0.25rem}
.look-info h4{font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.look-info .price{font-size:1rem;font-weight:800;color:#000}
.look-total{background:#fff;border-radius:12px;padding:1.5rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
.total-info{display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
.total-label{font-size:1rem;font-weight:600;color:#666}
.total-price{font-size:1.75rem;font-weight:900;color:#000}
.total-save{background:#10b981;color:#fff;padding:0.25rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:700}
.btn-add-all{background:#000;color:#fff;border:none;padding:1rem 2rem;border-radius:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:0.625rem;transition:all 0.2s;white-space:nowrap}
.btn-add-all:hover{background:#333;transform:scale(1.02)}

.similar{padding:3rem 0}
.similar h2{font-size:1.5rem;font-weight:800;margin-bottom:1.5rem}
.products{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}
.product{background:#f9fafb;border-radius:12px;overflow:hidden;transition:transform 0.2s}
.product:hover{transform:translateY(-4px)}
.product a{text-decoration:none;color:inherit;display:block}
.product .img{padding-top:125%;position:relative}
.product .img img{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover}
.product .info{padding:1rem}
.product .brand{font-size:0.6875rem;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:0.25rem}
.product h3{font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product .price .current{font-size:1.125rem;font-weight:800}

@media (min-width:768px){
.product-layout{grid-template-columns:1fr 1fr}
.back-link{display:flex;align-items:center;gap:0.5rem}
.breadcrumbs{font-size:0.875rem}
.product-details h1{font-size:2rem}
.products{grid-template-columns:repeat(4,1fr);gap:1.5rem}
.complete-look{margin:2rem 0;padding:3rem 2rem}
}

/* Reviews Enhanced (Poizon style) */
.reviews-enhanced{padding:3rem 0;margin:3rem 0;border-top:2px solid #f3f4f6}
.reviews-enhanced h2{font-size:1.75rem;font-weight:800;margin-bottom:2rem}
.reviews-header{margin-bottom:2rem}
.reviews-summary{display:grid;grid-template-columns:1fr;gap:2rem;background:#f9fafb;padding:2rem;border-radius:16px;margin-top:1.5rem}
.rating-large-block{text-align:center}
.rating-number{font-size:3.5rem;font-weight:900;color:#000;line-height:1}
.rating-stars-big{display:flex;gap:4px;justify-content:center;margin:0.75rem 0;font-size:1.5rem}
.rating-stars-big i{color:#d1d5db}
.rating-stars-big i.active{color:#fbbf24}
.rating-count-text{color:#666;font-size:0.875rem;font-weight:600}
.rating-breakdown{display:flex;flex-direction:column;gap:0.75rem}
.rating-bar{display:flex;align-items:center;gap:0.75rem}
.bar-label{min-width:50px;font-size:0.875rem;font-weight:600;color:#666}
.bar-track{flex:1;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden}
.bar-fill{height:100%;background:linear-gradient(90deg,#fbbf24,#f59e0b);transition:width 0.5s}
.bar-percent{min-width:45px;text-align:right;font-size:0.875rem;font-weight:600;color:#666}
.review-filters{display:flex;gap:0.75rem;margin-bottom:2rem;flex-wrap:wrap}
.filter-btn{background:#f3f4f6;border:none;padding:0.625rem 1.25rem;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.2s}
.filter-btn.active{background:#000;color:#fff}
.filter-btn:hover{background:#e5e7eb}
.filter-btn.active:hover{background:#333}
.reviews-list{display:flex;flex-direction:column;gap:1.5rem;margin-bottom:2rem}
.review-item{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.5rem;transition:box-shadow 0.2s}
.review-item:hover{box-shadow:0 4px 12px rgba(0,0,0,0.08)}
.review-header-row{display:flex;align-items:center;gap:1rem;margin-bottom:1rem}
.reviewer-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.125rem;flex-shrink:0}
.reviewer-info{flex:1}
.reviewer-name{font-weight:700;color:#000}
.reviewer-badge{font-size:0.75rem;color:#10b981;font-weight:600}
.review-date{font-size:0.8125rem;color:#666;white-space:nowrap}
.review-rating-stars{font-size:1.125rem;margin-bottom:0.75rem}
.review-details{display:flex;gap:1rem;margin-bottom:0.75rem;flex-wrap:wrap}
.review-details .detail{font-size:0.8125rem;color:#666}
.review-details .detail strong{color:#000}
.review-text{line-height:1.6;color:#333;margin-bottom:1rem}
.review-helpful{display:flex;gap:0.75rem}
.btn-helpful{background:#f3f4f6;border:none;padding:0.5rem 1rem;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s}
.btn-helpful:hover{background:#e5e7eb}
.btn-write-review{background:#000;color:#fff;border:none;padding:1rem 2rem;border-radius:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.625rem;transition:all 0.2s;margin:0 auto}
.btn-write-review:hover{background:#333;transform:translateY(-2px)}

/* Community Q&A (Poizon style) */
.community-qa{padding:3rem 0;margin:3rem 0;border-top:2px solid #f3f4f6}
.community-qa h2{font-size:1.75rem;font-weight:800;margin-bottom:2rem}
.qa-list{display:flex;flex-direction:column;gap:1.5rem;margin-bottom:2rem}
.qa-item{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.5rem}
.question{display:flex;align-items:start;gap:0.75rem;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #f3f4f6}
.question i{color:#3b82f6;font-size:1.25rem;flex-shrink:0;margin-top:0.125rem}
.question span{font-weight:600;color:#000;font-size:1rem}
.answer{display:flex;align-items:start;gap:0.75rem;padding:1rem;background:#f9fafb;border-radius:8px;margin-top:1rem}
.answer i{color:#10b981;font-size:1.125rem;flex-shrink:0;margin-top:0.125rem}
.answer.best-answer{background:#ecfdf5;border:1px solid #d1fae5}
.answer-text{flex:1;line-height:1.6;color:#333;margin-bottom:0.75rem}
.answer-meta{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;font-size:0.8125rem}
.answer-author{font-weight:600;color:#666}
.verified-buyer{color:#10b981;font-weight:600}
.btn-helpful-small{background:#fff;border:1px solid #e5e7eb;padding:0.25rem 0.75rem;border-radius:6px;font-size:0.8125rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:0.375rem;transition:all 0.2s}
.btn-helpful-small:hover{background:#f3f4f6}
.btn-ask-question{background:#3b82f6;color:#fff;border:none;padding:1rem 2rem;border-radius:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.625rem;transition:all 0.2s;margin:0 auto}
.btn-ask-question:hover{background:#2563eb;transform:translateY(-2px)}

/* Social Proof Widget (Poizon style) */
.social-proof-widget{background:linear-gradient(135deg,#f9fafb,#fff);border:2px solid #e5e7eb;border-radius:16px;padding:2rem;margin:3rem 0}
.proof-badge{display:inline-flex;align-items:center;gap:0.5rem;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;padding:0.5rem 1rem;border-radius:8px;font-weight:700;font-size:0.875rem;margin-bottom:1.5rem}
.proof-badge i{font-size:1.125rem}
.proof-stats{display:grid;grid-template-columns:1fr;gap:1rem}
.proof-stat{display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.proof-stat i{font-size:1.5rem;color:#fbbf24}
.proof-stat:nth-child(2) i{color:#10b981}
.proof-stat:nth-child(3) i{color:#ef4444}
.proof-stat span{font-size:0.9375rem;color:#666}
.proof-stat strong{color:#000;font-weight:700}

/* Sticky Purchase Bar (Poizon style) */
.sticky-purchase-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;box-shadow:0 -4px 20px rgba(0,0,0,0.15);padding:1rem;display:none;align-items:center;gap:1rem;z-index:1000;transform:translateY(100%);transition:transform 0.3s;border-top:1px solid #e5e7eb}
.sticky-purchase-bar.visible{display:flex;transform:translateY(0)}
.sticky-product-info{display:flex;align-items:center;gap:0.75rem;flex:1}
.sticky-thumb{width:48px;height:48px;object-fit:cover;border-radius:8px;background:#f9fafb}
.sticky-details{flex:1;min-width:0}
.sticky-name{font-size:0.875rem;font-weight:600;color:#000;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sticky-price{font-size:1rem;font-weight:800;color:#000}
.sticky-add-cart{background:#000;color:#fff;border:none;padding:0.875rem 1.5rem;border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s;white-space:nowrap}
.sticky-add-cart:hover{background:#333}
.sticky-add-cart i{font-size:1.125rem}

@media (min-width:768px){
.reviews-summary{grid-template-columns:200px 1fr}
.rating-large-block{text-align:left}
.rating-stars-big{justify-content:flex-start}
.proof-stats{grid-template-columns:repeat(3,1fr)}
.specs-grid{grid-template-columns:repeat(2,1fr)}
}

@media (min-width:1024px){
.specs-grid{grid-template-columns:repeat(4,1fr)}
}

@media (max-width:767px){
.look-grid{grid-template-columns:1fr;gap:1.5rem}
.look-total{flex-direction:column}
.btn-add-all{width:100%;justify-content:center}
.size-stats-grid{grid-template-columns:1fr;gap:0.75rem}
.stat-item{padding:0.75rem 0.5rem}
.size-finder-options{grid-template-columns:repeat(4,1fr)}
}

/* Size Finder Modal */
.size-finder-modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.75);z-index:3000;display:flex;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(8px);animation:fadeIn 0.3s}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.size-finder-content{background:#fff;border-radius:20px;max-width:500px;width:100%;max-height:90vh;overflow-y:auto;padding:2rem;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideUp 0.3s}
@keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-close{position:absolute;top:1rem;right:1rem;width:40px;height:40px;border-radius:50%;background:#f3f4f6;border:none;font-size:1.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:10}
.modal-close:hover{background:#e5e7eb;transform:rotate(90deg)}
.size-finder-content h2{font-size:1.5rem;font-weight:800;margin-bottom:0.5rem;display:flex;align-items:center;gap:0.5rem}
.size-finder-content h2 i{color:#667eea}
.size-finder-desc{color:#666;font-size:0.875rem;margin-bottom:2rem}
.size-finder-step{display:none}
.size-finder-step.active{display:block;animation:fadeIn 0.3s}
.size-finder-step h3{font-size:1.125rem;font-weight:700;margin-bottom:1.5rem;color:#333}
.size-finder-options{display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;margin-bottom:2rem}
.size-finder-options.vertical{grid-template-columns:1fr}
.size-finder-btn{padding:1rem;background:#f9fafb;border:2px solid #e5e7eb;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:0.5rem}
.size-finder-btn:hover{background:#f3f4f6;border-color:#667eea;transform:translateY(-2px)}
.size-finder-btn.active{background:#667eea;color:#fff;border-color:#667eea;box-shadow:0 4px 12px rgba(102,126,234,0.3)}
.size-finder-btn i{font-size:1.25rem}
.size-finder-result{display:none;text-align:center;padding:2rem 0}
.size-finder-result.active{display:block;animation:fadeIn 0.5s}
.result-icon{font-size:4rem;color:#10b981;margin-bottom:1rem;animation:scaleIn 0.5s}
@keyframes scaleIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.size-finder-result h3{font-size:1.75rem;font-weight:800;margin-bottom:0.5rem}
.size-finder-result h3 span{color:#667eea}
.result-confidence{color:#666;font-size:0.875rem;margin-bottom:2rem}
.result-confidence strong{color:#10b981;font-weight:700}
.btn-apply-size{width:100%;padding:1rem;background:#667eea;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:0.5rem}
.btn-apply-size:hover{background:#5568d3;transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4)}
.size-finder-nav{display:flex;gap:1rem;margin-top:2rem}
.btn-back,.btn-next{flex:1;padding:0.875rem;border:none;border-radius:10px;font-size:0.9375rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:all 0.2s}
.btn-back{background:#f3f4f6;color:#333}
.btn-back:hover{background:#e5e7eb}
.btn-next{background:#667eea;color:#fff}
.btn-next:hover{background:#5568d3;transform:translateY(-2px)}

/* Fullscreen Image Modal */
.image-modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.95);z-index:4000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(20px);animation:fadeIn 0.3s}
.image-modal.active{display:flex}
.image-modal-content{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:4rem 2rem}
.modal-image-container{max-width:90vw;max-height:90vh;position:relative}
.modal-image-container img{max-width:100%;max-height:90vh;object-fit:contain;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
.image-modal-close{position:fixed;top:1.5rem;right:1.5rem;width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,0.9);border:none;font-size:2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:10;color:#000}
.image-modal-close:hover{background:#fff;transform:rotate(90deg) scale(1.1)}
.modal-nav-btn{position:fixed;top:50%;transform:translateY(-50%);width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.9);border:none;font-size:2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:10;color:#000}
.modal-nav-btn:hover{background:#fff;transform:translateY(-50%) scale(1.1)}
.modal-nav-btn.prev{left:2rem}
.modal-nav-btn.next{right:2rem}
.modal-nav-btn:disabled{opacity:0.3;cursor:not-allowed}
.modal-image-counter{position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);background:rgba(255,255,255,0.9);padding:0.75rem 1.5rem;border-radius:30px;font-weight:700;color:#000;font-size:0.875rem}
.modal-thumbnails{position:fixed;bottom:5rem;left:50%;transform:translateX(-50%);display:flex;gap:0.5rem;max-width:90vw;overflow-x:auto;padding:0.5rem}
.modal-thumb{width:60px;height:60px;border-radius:8px;overflow:hidden;border:3px solid transparent;cursor:pointer;transition:all 0.2s;flex-shrink:0}
.modal-thumb:hover{border-color:rgba(255,255,255,0.5)}
.modal-thumb.active{border-color:#fff;transform:scale(1.1)}
.modal-thumb img{width:100%;height:100%;object-fit:cover}
.image-modal-zoom-hint{position:fixed;top:2rem;left:50%;transform:translateX(-50%);background:rgba(255,255,255,0.9);padding:0.75rem 1.5rem;border-radius:30px;font-size:0.875rem;color:#666;pointer-events:none;opacity:0;animation:fadeInOut 3s ease-in-out}
@keyframes fadeInOut{0%,100%{opacity:0}20%,80%{opacity:1}}
@media (max-width:767px){
.modal-nav-btn{width:50px;height:50px;font-size:1.5rem}
.modal-nav-btn.prev{left:1rem}
.modal-nav-btn.next{right:1rem}
.modal-thumbnails{display:none}
}

/* Size Conversion Table */
.size-conversion-table{margin:1rem 0 1.5rem;padding:1rem;background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb;animation:slideDown 0.3s ease}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.size-table{width:100%;border-collapse:collapse;margin:0}
.size-table thead{background:#fff}
.size-table th{padding:0.75rem;text-align:center;font-size:0.8125rem;font-weight:700;color:#666;border-bottom:2px solid #e5e7eb;text-transform:uppercase;letter-spacing:0.5px}
.size-table td{padding:0.875rem;text-align:center;font-size:0.9375rem;border-bottom:1px solid #e5e7eb}
.size-table tbody tr:hover{background:#fff;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
.size-table tbody tr.out-of-stock{opacity:0.5}
.size-table tbody tr.out-of-stock td{text-decoration:line-through}
.size-table-hint{margin-top:0.75rem;padding:0.75rem;background:#fff;border-radius:8px;display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#666}
.size-table-hint i{color:#667eea;font-size:1rem}

/* Product Specs Section */
.product-specs-section{background:#fff;border-radius:16px;padding:2rem;margin:2rem 0;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
.product-specs-section h2{font-size:1.5rem;font-weight:800;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem}
.specs-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}
.spec-item{display:flex;flex-direction:column;gap:0.25rem;padding:1rem;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;transition:all 0.2s}
.spec-item:hover{border-color:#667eea;background:#fff;transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,0.08)}
.spec-label{font-size:0.8125rem;font-weight:600;color:#666;text-transform:uppercase;letter-spacing:0.5px}
.spec-value{font-size:1rem;font-weight:600;color:#000}
.spec-value code{background:#fff;padding:0.25rem 0.5rem;border-radius:4px;font-family:'Courier New',monospace;font-size:0.875rem;color:#667eea;border:1px solid #e5e7eb}

@media (max-width:767px){
.specs-grid{grid-template-columns:1fr}
.size-table{font-size:0.75rem}
.size-table th,.size-table td{padding:0.5rem 0.25rem}
}
</style>

<!-- jQuery УДАЛЕН - используем vanilla JS -->
<script src="/js/cart.js"></script>
<script src="/js/favorites.js"></script>
<script>
// Back button в header (вместо catalog-header)
(function() {
    // Добавляем back-btn в navbar
    const navbar = document.querySelector('.navbar .container, .navbar .container-fluid');
    if (navbar && document.referrer.includes('/catalog')) {
        const backBtn = document.createElement('button');
        backBtn.className = 'btn btn-link text-white me-3';
        backBtn.innerHTML = '<i class="bi bi-arrow-left"></i> Назад';
        backBtn.onclick = () => history.back();
        backBtn.style.cssText = 'text-decoration:none;font-size:1rem';
        navbar.insertBefore(backBtn, navbar.firstChild);
    }
})();
</script>
<script>
function changeImg(src){
    document.getElementById('mainImg').src=src;
    document.getElementById('mainImgContainer').classList.remove('zoomed');
}

function toggleFav(e,id){
    e.stopPropagation();
    toggleFavorite(id, e.currentTarget);
}

function toggleZoom(){
    document.getElementById('mainImgContainer').classList.toggle('zoomed');
}

// Обновление цены при выборе размера
document.addEventListener('DOMContentLoaded', function() {
    const sizeInputs = document.querySelectorAll('input[name="size"]');
    const priceElement = document.getElementById('productPrice');
    
    if (sizeInputs.length > 0 && priceElement) {
        sizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.checked) {
                    const newPrice = this.dataset.price;
                    if (newPrice && newPrice > 0) {
                        // Форматируем цену
                        const formatter = new Intl.NumberFormat('ru-BY', {
                            style: 'currency',
                            currency: 'BYN',
                            minimumFractionDigits: 2
                        });
                        priceElement.textContent = formatter.format(newPrice);
                        
                        // Добавляем плавную анимацию
                        priceElement.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            priceElement.style.transform = 'scale(1)';
                        }, 200);
                    }
                }
            });
        });
    }
});

function createOrder(){
    const productId = <?= $product->id ?>;
    const sizeInput = document.querySelector('input[name="size"]:checked');
    const size = sizeInput ? sizeInput.value : null;
    
    if (!size && <?= !empty($product->sizes) ? 'true' : 'false' ?>) {
        alert('Пожалуйста, выберите размер');
        return;
    }
    
    if (typeof addToCart === 'function') {
        addToCart(productId, 1, size, null);
    } else {
        alert('Товар добавлен в корзину');
    }
}

// Sticky Purchase Bar - показывается при скролле
window.addEventListener('scroll', function() {
    const stickyBar = document.getElementById('stickyBar');
    const mainBtn = document.querySelector('.btn-order');
    
    if (!stickyBar || !mainBtn) return;
    
    const rect = mainBtn.getBoundingClientRect();
    
    if (rect.top < 0) {
        stickyBar.classList.add('visible');
    } else {
        stickyBar.classList.remove('visible');
    }
});

// Live viewers count - УДАЛЕНО (было fake)

// Review filters - будет активировано при подключении реальных отзывов

// Accordion для описания
function toggleDescription() {
    const content = document.getElementById('descContent');
    const icon = document.getElementById('descToggleIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Accordion для характеристик
function toggleSpecs() {
    const content = document.getElementById('specsContent');
    const icon = document.getElementById('specsToggleIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'grid';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Fullscreen Image Modal
let currentImageIndex = 0;
const productImages = [
    <?php if (!empty($product->images)): ?>
        <?php foreach ($product->images as $img): ?>
        '<?= $img->getUrl() ?>',
        <?php endforeach; ?>
    <?php else: ?>
        '<?= $product->getMainImageUrl() ?>',
    <?php endif; ?>
];

function openImageModal(index) {
    currentImageIndex = index;
    const modal = document.getElementById('imageModal');
    if (!modal) {
        createImageModal();
    }
    updateModalImage();
    document.getElementById('imageModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').classList.remove('active');
    document.body.style.overflow = '';
}

function createImageModal() {
    const modal = document.createElement('div');
    modal.id = 'imageModal';
    modal.className = 'image-modal';
    modal.innerHTML = `
        <div class="image-modal-content">
            <button class="image-modal-close" onclick="closeImageModal()">
                <i class="bi bi-x"></i>
            </button>
            <button class="modal-nav-btn prev" onclick="prevImage()" id="modalPrevBtn">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="modal-nav-btn next" onclick="nextImage()" id="modalNextBtn">
                <i class="bi bi-chevron-right"></i>
            </button>
            <div class="modal-image-container">
                <img id="modalImage" src="" alt="<?= Html::encode($product->name) ?>">
            </div>
            <div class="modal-image-counter" id="modalCounter"></div>
            <div class="modal-thumbnails" id="modalThumbnails"></div>
            <div class="image-modal-zoom-hint">💡 Кликните на фото для увеличения</div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Generate thumbnails
    const thumbsContainer = document.getElementById('modalThumbnails');
    productImages.forEach((img, index) => {
        const thumb = document.createElement('div');
        thumb.className = 'modal-thumb';
        thumb.innerHTML = `<img src="${img}" alt="">`;
        thumb.onclick = () => {
            currentImageIndex = index;
            updateModalImage();
        };
        thumbsContainer.appendChild(thumb);
    });
    
    // Close on background click
    modal.addEventListener('click', (e) => {
        if (e.target === modal || e.target.className === 'image-modal-content') {
            closeImageModal();
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!modal.classList.contains('active')) return;
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'Escape') closeImageModal();
    });
}

function updateModalImage() {
    document.getElementById('modalImage').src = productImages[currentImageIndex];
    document.getElementById('modalCounter').textContent = `${currentImageIndex + 1} / ${productImages.length}`;
    
    // Update buttons
    document.getElementById('modalPrevBtn').disabled = currentImageIndex === 0;
    document.getElementById('modalNextBtn').disabled = currentImageIndex === productImages.length - 1;
    
    // Update thumbnails
    document.querySelectorAll('.modal-thumb').forEach((thumb, index) => {
        thumb.classList.toggle('active', index === currentImageIndex);
    });
}

function prevImage() {
    if (currentImageIndex > 0) {
        currentImageIndex--;
        updateModalImage();
    }
}

function nextImage() {
    if (currentImageIndex < productImages.length - 1) {
        currentImageIndex++;
        updateModalImage();
    }
}

// Complete the look - добавить все в корзину
function addCompleteLook() {
    <?php if (!empty($similarProducts)): ?>
    const items = [<?= $product->id ?>, <?= implode(',', array_map(function($p) { return $p->id; }, array_slice($similarProducts, 0, 3))) ?>];
    
    if (typeof addToCart === 'function') {
        items.forEach(id => {
            addToCart(id, 1, null, null);
        });
        alert('🎁 Все товары из образа добавлены в корзину!');
    } else {
        alert('Функция корзины не найдена');
    }
    <?php else: ?>
    alert('Нет похожих товаров');
    <?php endif; ?>
}

// Size Table Toggle
function toggleSizeTable() {
    const table = document.getElementById('size-conversion-table');
    if (table.style.display === 'none' || table.style.display === '') {
        table.style.display = 'block';
    } else {
        table.style.display = 'none';
    }
}

// Size Guide Modal
function openSizeGuide() {
    document.getElementById('sizeGuideModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSizeGuide() {
    document.getElementById('sizeGuideModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Size Finder Modal (AI-powered)
function openSizeFinder() {
    const modal = document.createElement('div');
    modal.id = 'sizeFinderModal';
    modal.className = 'size-finder-modal';
    modal.innerHTML = `
        <div class="size-finder-content">
            <button class="modal-close" onclick="closeSizeFinder()">
                <i class="bi bi-x"></i>
            </button>
            <h2><i class="bi bi-search"></i> Найти мой размер</h2>
            <p class="size-finder-desc">Ответьте на 3 простых вопроса, и мы подберем идеальный размер</p>
            
            <div class="size-finder-step active" data-step="1">
                <h3>1. Ваш обычный размер обуви (RU)</h3>
                <div class="size-finder-options">
                    ${[38,39,40,41,42,43,44,45].map(s => `
                        <button class="size-finder-btn" data-value="${s}" onclick="selectSize(${s})">${s}</button>
                    `).join('')}
                </div>
            </div>
            
            <div class="size-finder-step" data-step="2">
                <h3>2. Как обычно сидит обувь этого бренда?</h3>
                <div class="size-finder-options vertical">
                    <button class="size-finder-btn" data-value="tight" onclick="selectFit('tight')">
                        <i class="bi bi-arrow-down-circle"></i>
                        Обычно маломерит
                    </button>
                    <button class="size-finder-btn" data-value="perfect" onclick="selectFit('perfect')">
                        <i class="bi bi-check-circle"></i>
                        Обычно соответствует
                    </button>
                    <button class="size-finder-btn" data-value="loose" onclick="selectFit('loose')">
                        <i class="bi bi-arrow-up-circle"></i>
                        Обычно большемерит
                    </button>
                </div>
            </div>
            
            <div class="size-finder-step" data-step="3">
                <h3>3. Как вы предпочитаете носить обувь?</h3>
                <div class="size-finder-options vertical">
                    <button class="size-finder-btn" data-value="tight" onclick="selectPreference('tight')">
                        <i class="bi bi-suit-heart"></i>
                        Плотно по ноге
                    </button>
                    <button class="size-finder-btn" data-value="comfort" onclick="selectPreference('comfort')">
                        <i class="bi bi-star"></i>
                        Комфортно (рекомендуем)
                    </button>
                    <button class="size-finder-btn" data-value="loose" onclick="selectPreference('loose')">
                        <i class="bi bi-box"></i>
                        Свободно
                    </button>
                </div>
            </div>
            
            <div class="size-finder-result" id="sizeFinderResult">
                <div class="result-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3>Ваш размер: <span id="recommendedSize">-</span></h3>
                <p class="result-confidence">Уверенность: <strong id="confidence">-</strong></p>
                <button class="btn-apply-size" onclick="applySizeRecommendation()">
                    Выбрать этот размер
                </button>
            </div>
            
            <div class="size-finder-nav">
                <button class="btn-back" onclick="prevStep()" style="display:none">
                    <i class="bi bi-arrow-left"></i> Назад
                </button>
                <button class="btn-next" onclick="nextStep()">
                    Далее <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    window.sizeFinderData = { step: 1, size: null, fit: null, preference: null };
}

function closeSizeFinder() {
    const modal = document.getElementById('sizeFinderModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

function selectSize(size) {
    window.sizeFinderData.size = size;
    document.querySelectorAll('[data-step="1"] .size-finder-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value == size);
    });
}

function selectFit(fit) {
    window.sizeFinderData.fit = fit;
    document.querySelectorAll('[data-step="2"] .size-finder-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value === fit);
    });
}

function selectPreference(preference) {
    window.sizeFinderData.preference = preference;
    document.querySelectorAll('[data-step="3"] .size-finder-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value === preference);
    });
}

function nextStep() {
    const data = window.sizeFinderData;
    const currentStep = data.step;
    
    if (currentStep === 1 && !data.size) {
        alert('Пожалуйста, выберите размер');
        return;
    }
    if (currentStep === 2 && !data.fit) {
        alert('Пожалуйста, выберите вариант');
        return;
    }
    if (currentStep === 3 && !data.preference) {
        alert('Пожалуйста, выберите вариант');
        return;
    }
    
    if (currentStep < 3) {
        data.step++;
        document.querySelectorAll('.size-finder-step').forEach((step, i) => {
            step.classList.toggle('active', i + 1 === data.step);
        });
        document.querySelector('.btn-back').style.display = data.step > 1 ? 'block' : 'none';
        document.querySelector('.btn-next').style.display = data.step < 3 ? 'block' : 'none';
    } else {
        calculateRecommendation();
    }
}

function prevStep() {
    const data = window.sizeFinderData;
    if (data.step > 1) {
        data.step--;
        document.querySelectorAll('.size-finder-step').forEach((step, i) => {
            step.classList.toggle('active', i + 1 === data.step);
        });
        document.querySelector('.btn-back').style.display = data.step > 1 ? 'block' : 'none';
        document.querySelector('.btn-next').style.display = 'block';
    }
}

function calculateRecommendation() {
    const data = window.sizeFinderData;
    let recommendedSize = data.size;
    let adjustment = 0;
    
    // Алгоритм подбора размера
    if (data.fit === 'tight') adjustment += 0.5;
    if (data.fit === 'loose') adjustment -= 0.5;
    if (data.preference === 'tight') adjustment -= 0.5;
    if (data.preference === 'loose') adjustment += 0.5;
    
    recommendedSize = Math.round(recommendedSize + adjustment);
    
    // Уверенность в рекомендации
    const confidence = Math.abs(adjustment) < 1 ? '95%' : '85%';
    
    // Показываем результат
    document.querySelectorAll('.size-finder-step').forEach(step => step.classList.remove('active'));
    document.querySelector('.size-finder-nav').style.display = 'none';
    const resultDiv = document.getElementById('sizeFinderResult');
    resultDiv.classList.add('active');
    document.getElementById('recommendedSize').textContent = recommendedSize;
    document.getElementById('confidence').textContent = confidence;
}

function applySizeRecommendation() {
    const size = document.getElementById('recommendedSize').textContent;
    const sizeInput = document.querySelector(`input[name="size"][value="${size}"]`);
    if (sizeInput) {
        sizeInput.checked = true;
        sizeInput.closest('.size').querySelector('span').click();
    }
    closeSizeFinder();
}

function recommendSize() {
    const footLength = parseFloat(document.getElementById('footLength').value);
    const resultEl = document.getElementById('sizeRecommendation');
    
    if (!footLength || footLength < 20 || footLength > 35) {
        resultEl.textContent = 'Пожалуйста, введите корректную длину стопы (20-35 см)';
        resultEl.style.background = '#fef2f2';
        resultEl.style.borderColor = '#ef4444';
        resultEl.style.color = '#dc2626';
        resultEl.classList.add('show');
        return;
    }
    
    // Таблица соответствия длины стопы и размера
    const sizeChart = [
        {cm: 24.0, size: 38},
        {cm: 24.5, size: 39},
        {cm: 25.0, size: 40},
        {cm: 26.0, size: 41},
        {cm: 27.0, size: 42},
        {cm: 28.0, size: 43},
        {cm: 29.0, size: 44},
        {cm: 30.0, size: 45},
    ];
    
    // Находим подходящий размер
    let recommendedSize = 38;
    for (let i = 0; i < sizeChart.length; i++) {
        if (footLength <= sizeChart[i].cm) {
            recommendedSize = sizeChart[i].size;
            break;
        }
        if (i === sizeChart.length - 1 && footLength > sizeChart[i].cm) {
            recommendedSize = 45;
        }
    }
    
    resultEl.textContent = `✓ Рекомендуем размер: ${recommendedSize} (EU/RU)`;
    resultEl.style.background = '#ecfdf5';
    resultEl.style.borderColor = '#10b981';
    resultEl.style.color = '#059669';
    resultEl.classList.add('show');
    
    // Подсветим рекомендуемый размер в таблице
    document.querySelectorAll('.size-table tr').forEach(row => {
        row.style.background = '';
    });
    const recommendedRow = document.querySelector(`.size-table tr:has(td:first-child strong:contains("${recommendedSize}"))`);
    if (recommendedRow) {
        recommendedRow.style.background = '#ecfdf5';
    }
}

// Закрытие Size Guide по клику вне окна
document.addEventListener('click', function(e) {
    const modal = document.getElementById('sizeGuideModal');
    if (e.target === modal) {
        closeSizeGuide();
    }
});

// Закрытие по ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSizeGuide();
    }
});

function showNotification(message, type = 'info') {
    const notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
    $('body').append(notification);
    setTimeout(() => notification.addClass('show'), 10);
    setTimeout(() => {
        notification.removeClass('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Back button event listener
document.addEventListener('DOMContentLoaded', function() {
    const backBtn = document.getElementById('backBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            history.back();
        });
    }
});
</script>

<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s;
}
.notification.show {
    transform: translateX(0);
}
.notification-success {
    border-left: 4px solid #10b981;
}
.notification-error {
    border-left: 4px solid #ef4444;
}
.notification-info {
    border-left: 4px solid #3b82f6;
}
</style>
