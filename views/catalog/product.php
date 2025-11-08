<?php
/** @var yii\web\View $this */
/** @var app\models\Product $product */
/** @var app\models\Product[] $similarProducts */
/** @var bool $isFavorite */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $product->getDisplayTitle() . ' | СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'product-id', 'content' => $product->id]);

// ============================================
// ОПТИМИЗАЦИЯ ЗАГРУЗКИ РЕСУРСОВ
// ============================================
use app\components\AssetOptimizer;

// Используем AssetOptimizer для оптимизации страницы товара
AssetOptimizer::optimizeProductPage($this, [
    'fonts' => [], // Веб-шрифты при наличии
    'mainImage' => $product->getMainImageUrl(), // Preload главного изображения для LCP
]);

// Дополнительные критичные стили для product page (inline)
$this->registerCss(<<<'CSS'
.product-page-optimized {
    background: #ffffff;
}
.product-page-optimized .product-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(16px, 4vw, 32px) clamp(12px, 3vw, 40px);
}
.product-page-optimized .product-layout {
    display: grid;
    gap: clamp(20px, 4vw, 48px);
}
.product-gallery-swipe {
    position: relative;
    background: #f9fafb;
    border-radius: 24px;
    overflow: hidden;
    min-height: clamp(260px, 40vw, 520px);
}
.product-gallery-swipe .swipe-track {
    display: flex;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform;
}
.product-gallery-swipe .swipe-slide {
    flex: 0 0 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(12px, 3vw, 36px);
}
.product-gallery-swipe .swipe-slide img {
    max-width: 100%;
    height: auto;
    object-fit: contain;
}
.product-details h1 {
    font-size: clamp(26px, 5vw, 40px);
    margin-bottom: 12px;
    font-weight: 700;
}
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
}
.action-buttons .btn-order {
    flex: 1 1 220px;
    min-height: 56px;
    border-radius: 16px;
    font-size: 16px;
}
@media (min-width: 992px) {
    .product-page-optimized .product-layout {
        grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
        align-items: start;
    }
}
html, body { overflow-x: hidden; overflow-y: auto !important; height: auto !important; }
CSS
, ['position' => \yii\web\View::POS_HEAD]);

// Измерение производительности (только в dev режиме)
if (YII_ENV_DEV) {
    AssetOptimizer::measurePerformance($this);
}

// SEO параметры
$this->params['description'] = $product->description 
    ? Html::encode(mb_substr(strip_tags($product->description), 0, 160)) 
    : Html::encode($product->getDisplayTitle() . ' - купить оригинальные кроссовки в Минске. Цена: ' . Yii::$app->formatter->asCurrency($product->price, 'BYN'));
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
    'name' => $product->getDisplayTitle(),
    'image' => [$product->getMainImageUrl()],
    'description' => $product->description ?: $product->getDisplayTitle(),
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

<!-- Индикатор "В корзине" -->
<div class="product-in-cart-indicator" id="productInCartIndicator" style="display:none;" title="Этот товар уже в вашей корзине! Нажмите для перехода в корзину">
    <div class="indicator-content">
        <i class="bi bi-cart-check-fill"></i>
        <span class="indicator-text">В корзине</span>
    </div>
    <div class="indicator-hint">Нажмите для перехода</div>
</div>

<!-- Убран catalog-header - back-btn теперь в основном header -->
<div class="product-page-optimized">
    <div class="container product-container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a> / 
            <a href="/catalog">Каталог</a> / 
            <a href="<?= $product->category->getUrl() ?>"><?= Html::encode($product->category->name) ?></a> / 
            <span><?= Html::encode($product->name) ?></span>
        </nav>

        <div class="product-layout">
            <!-- Галерея с миниатюрами -->
            <div class="product-gallery-wrapper">
                <!-- Swipe Gallery для mobile + desktop -->
                <div class="product-gallery-swipe">
                    <div class="swipe-track">
                        <?php if (!empty($product->images)): ?>
                            <?php foreach ($product->images as $index => $img): ?>
                            <div class="swipe-slide <?= $index === 0 ? 'active' : '' ?>" onclick="openImageModal(<?= $index ?>)">
                                <img src="<?= $img->getUrl() ?>" alt="<?= Html::encode($product->name) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" <?= $index === 0 ? 'fetchpriority="high" decoding="async"' : 'decoding="async"' ?>>
                                <div class="zoom-icon"><i class="bi bi-zoom-in"></i></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="swipe-slide active" onclick="openImageModal(0)">
                                <img src="<?= $product->getMainImageUrl() ?>" alt="<?= Html::encode($product->name) ?>" loading="eager" fetchpriority="high" decoding="async">
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
                
                <!-- Миниатюры под галереей -->
                <?php if (!empty($product->images) && count($product->images) > 1): ?>
                <div class="product-thumbnails-carousel">
                    <button class="thumb-nav thumb-prev" onclick="scrollThumbnails('prev')" aria-label="Предыдущие">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="thumbnails-wrapper">
                        <div class="thumbnails-track">
                            <?php foreach ($product->images as $index => $img): ?>
                            <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>" 
                                 data-index="<?= $index ?>" 
                                 onclick="switchToSlide(<?= $index ?>)">
                                <img src="<?= $img->getUrl() ?>" 
                                     alt="<?= Html::encode($product->name) ?> - фото <?= $index + 1 ?>" 
                                     loading="lazy">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="thumb-nav thumb-next" onclick="scrollThumbnails('next')" aria-label="Следующие">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="product-details">
                <?php if ($product->brand): ?>
                <!-- Красивый блок бренда с логотипом -->
                <div class="brand-block-premium">
                    <a href="<?= $product->brand->getUrl() ?>" class="brand-card">
                        <?php if ($product->brand->logo_url || $product->brand->logo): ?>
                        <div class="brand-logo">
                            <img src="<?= $product->brand->getLogoUrl() ?>" alt="<?= Html::encode($product->brand->name) ?>">
                        </div>
                        <?php endif; ?>
                        <div class="brand-info">
                            <span class="brand-name"><?= Html::encode($product->brand->name) ?></span>
                            <span class="brand-count"><?= $product->brand->getProductsCount() ?> товаров</span>
                        </div>
                        <i class="bi bi-chevron-right brand-arrow"></i>
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Бейдж "под заказ" перед названием -->
                <div style="margin-bottom: 0.25rem;">
                    <span class="custom-order-badge">
                        <i class="bi bi-truck"></i>
                        ПОД ЗАКАЗ
                    </span>
                </div>
                
                <h1><?= Html::encode($product->getDisplayTitle()) ?></h1>

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
                    <?php 
                    // Получаем диапазон цен из размеров
                    $priceRange = $product->getPriceRange();
                    $hasPriceRange = $priceRange && $product->hasPriceRange();
                    ?>
                    <?php if ($product->hasDiscount() && !$hasPriceRange): ?>
                        <span class="old"><?= Yii::$app->formatter->asCurrency($product->old_price, 'BYN') ?></span>
                        <span class="disc">-<?= $product->getDiscountPercent() ?>%</span>
                    <?php endif; ?>
                    
                    <?php if ($hasPriceRange): ?>
                        <!-- Диапазон цен изначально -->
                        <span class="current" id="productPrice" 
                              data-base-price="<?= $product->price ?>"
                              data-has-range="true"
                              data-min-price="<?= $priceRange['min'] ?>"
                              data-max-price="<?= $priceRange['max'] ?>">
                            <?= Yii::$app->formatter->asCurrency($priceRange['min'], 'BYN') ?>
                            <span class="price-separator"> - </span>
                            <?= Yii::$app->formatter->asCurrency($priceRange['max'], 'BYN') ?>
                        </span>
                    <?php else: ?>
                        <!-- Одна цена -->
                        <span class="current" id="productPrice" 
                              data-base-price="<?= $product->price ?>"
                              data-has-range="false">
                            <?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($product->colors) && count($product->colors) > 1): ?>
                <!-- Вариации товара (цвета) -->
                <div class="product-variations">
                    <h3>Цвет: <span id="selectedColorName"><?= Html::encode($product->colors[0]->name ?? 'Выберите') ?></span></h3>
                    <div class="color-variations">
                        <?php foreach ($product->colors as $index => $color): ?>
                            <button class="color-variation <?= $index === 0 ? 'active' : '' ?>" 
                                    data-color-id="<?= $color->id ?>"
                                    data-color-name="<?= Html::encode($color->name) ?>"
                                    onclick="selectColor(this)"
                                    title="<?= Html::encode($color->name) ?>">
                                <span class="color-circle" style="background: <?= Html::encode($color->hex) ?>"></span>
                                <span class="color-name"><?= Html::encode($color->name) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($product->sizes)): ?>
                
                <div class="sizes-section">
                    <div class="size-header">
                        <h3>Выберите размер</h3>
                        <button class="btn-size-guide" onclick="openSizeTableModal()">
                            <i class="bi bi-table"></i>
                            Таблица размеров
                        </button>
                    </div>
                    
                    
                    <!-- Быстрый выбор размера -->
                    <div class="sizes-quick-select">
                        <div class="size-system-tabs">
                            <button class="size-tab active" data-system="eu" onclick="switchSizeSystem('eu')">EU</button>
                            <button class="size-tab" data-system="us" onclick="switchSizeSystem('us')">US</button>
                            <button class="size-tab" data-system="uk" onclick="switchSizeSystem('uk')">UK</button>
                            <button class="size-tab" data-system="cm" onclick="switchSizeSystem('cm')">CM</button>
                        </div>
                        <div class="sizes" id="sizesContainer">
                            <?php foreach ($product->availableSizes as $size): 
                                $priceByn = $size->getPriceByn();
                                $inStock = $size->inStock();
                                
                                // Формируем tooltip с размерами в разных системах
                                $sizeTooltip = [];
                                if (!empty($size->eu_size)) $sizeTooltip[] = 'EU: ' . $size->eu_size;
                                if (!empty($size->us_size)) $sizeTooltip[] = 'US: ' . $size->us_size;
                                if (!empty($size->uk_size)) $sizeTooltip[] = 'UK: ' . $size->uk_size;
                                if (!empty($size->cm_size)) $sizeTooltip[] = 'CM: ' . $size->cm_size;
                                $tooltipText = !empty($sizeTooltip) ? implode(' | ', $sizeTooltip) : '';
                            ?>
                                <label class="size-compact <?= !$inStock ? 'disabled' : '' ?>" 
                                       data-eu="<?= Html::encode($size->eu_size ?: $size->size) ?>"
                                       data-us="<?= Html::encode($size->us_size ?: $size->eu_size ?: $size->size) ?>"
                                       data-uk="<?= Html::encode($size->uk_size ?: $size->eu_size ?: $size->size) ?>"
                                       data-cm="<?= Html::encode($size->cm_size ?: $size->eu_size ?: $size->size) ?>"
                                       data-price="<?= $priceByn ?>"
                                       <?php if ($tooltipText): ?>
                                       data-size-tooltip="<?= Html::encode($tooltipText) ?>"
                                       <?php endif; ?>>
                                    <input type="radio" name="size" value="<?= $size->size ?>" 
                                           data-price="<?= $priceByn ?>" 
                                           <?= !$inStock ? 'disabled' : '' ?>>
                                    <span class="size-value">
                                        <?= Html::encode($size->eu_size ?: $size->size) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Ссылка на каталог с выбранным размером -->
                    <div class="selected-size-link" id="selectedSizeLink" style="display:none;">
                        <i class="bi bi-box-seam"></i>
                        <span>Смотреть другие товары размера <strong id="selectedSizeValue"></strong> →</span>
                    </div>
                </div>
                <?php endif; ?>


                <!-- Stock Info (без fake данных) -->
                <?php if ($product->isInStock() && isset($product->stock_quantity) && $product->stock_quantity > 0 && $product->stock_quantity <= 10): ?>
                <div class="stock-urgency">
                    <div class="stock-left">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Осталось только <strong><?= $product->stock_quantity ?> шт.</strong> в наличии</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Кнопки действий -->
                <div class="action-buttons">
                    <button class="btn-order primary" onclick="createOrder()">
                        <i class="bi bi-cart-plus"></i> В корзину
                    </button>
                    <button class="btn-order secondary" onclick="openQuickOrderModal()">
                        <i class="bi bi-lightning-charge-fill"></i> Купить в 1 клик
                    </button>
                </div>

                <!-- Telegram поддержка -->
                <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="telegram-support">
                    <i class="bi bi-telegram"></i>
                    <span>Есть вопросы? Напишите нам в Telegram</span>
                    <i class="bi bi-arrow-right"></i>
                </a>



            </div>
        </div>

        <!-- Объединенный блок аутентичности и доверия (перед аккордеонами) -->
        <div class="product-trust-section">
            <div class="authenticity-main">
                <div class="auth-icon">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
                <div class="auth-info">
                    <div class="auth-title">100% ОРИГИНАЛ</div>
                    <div class="auth-subtitle">Проверено экспертами</div>
                </div>
            </div>
            
            <div class="trust-badges">
                <div class="badge-item">
                    <i class="bi bi-shield-check"></i>
                    <span>Защищенный платеж</span>
                </div>
                <div class="badge-item">
                    <i class="bi bi-patch-check"></i>
                    <span>Гарантия качества</span>
                </div>
                <div class="badge-item">
                    <i class="bi bi-star-fill"></i>
                    <span><?= number_format($product->rating ?? 0, 1) ?>/5 рейтинг</span>
                </div>
            </div>
        </div>

        <!-- Характеристики товара -->
        <div class="product-specs-section">
            <div class="specs-header-toggle" onclick="toggleMainSpecs()">
                <h2><i class="bi bi-list-ul"></i> Характеристики</h2>
                <i class="bi bi-chevron-down" id="mainSpecsToggleIcon"></i>
            </div>
            <div id="mainSpecsContent" style="display:none">
                
                <!-- Основная информация -->
                <div class="spec-section">
                    <h3>Основная информация</h3>
                    <table class="specs-table">
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
                        <?php if ($product->series_name): ?>
                        <tr>
                            <td class="spec-label">Серия:</td>
                            <td class="spec-value"><?= Html::encode($product->series_name) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product->style_code): ?>
                        <tr>
                            <td class="spec-label">Артикул:</td>
                            <td class="spec-value"><code><?= Html::encode($product->style_code) ?></code></td>
                        </tr>
                        <?php endif; ?>
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
                            <td class="spec-value">
                                <?php 
                                $seasonTranslations = [
                                    'summer' => 'Лето',
                                    'winter' => 'Зима',
                                    'spring' => 'Весна',
                                    'autumn' => 'Осень',
                                    'fall' => 'Осень',
                                    'all-season' => 'Всесезонная',
                                    'demi-season' => 'Демисезон',
                                    'demi' => 'Демисезон',
                                ];
                                echo Html::encode($seasonTranslations[strtolower($product->season)] ?? $product->season);
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product->country || $product->country_of_origin): ?>
                        <tr>
                            <td class="spec-label">Страна производства:</td>
                            <td class="spec-value"><?= Html::encode($product->country ?: $product->country_of_origin) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product->release_year): ?>
                        <tr>
                            <td class="spec-label">Дата релиза:</td>
                            <td class="spec-value"><?= $product->release_year ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product->weight): ?>
                        <tr>
                            <td class="spec-label">Вес:</td>
                            <td class="spec-value"><?= $product->weight ?> г</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Материалы и конструкция -->
                <?php if (!empty($product->material) || !empty($product->fastening) || !empty($product->height) || !empty($product->colors)): ?>
                <div class="spec-section">
                    <h3>Материалы и конструкция</h3>
                    <table class="specs-table">
                        <?php if (!empty($product->material)): ?>
                        <tr>
                            <td class="spec-label">Материал:</td>
                            <td class="spec-value"><?= Html::encode($product->material) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($product->fastening)): ?>
                        <tr>
                            <td class="spec-label">Тип застежки:</td>
                            <td class="spec-value">
                                <?php 
                                $fasteningTranslations = [
                                    'lace-up' => 'Шнуровка',
                                    'laces' => 'Шнуровка',
                                    'velcro' => 'Липучка',
                                    'zipper' => 'Молния',
                                    'buckle' => 'Пряжка',
                                    'slip-on' => 'Без застежки',
                                    'elastic' => 'Резинка',
                                    'hook-and-loop' => 'Липучка',
                                ];
                                echo Html::encode($fasteningTranslations[strtolower($product->fastening)] ?? $product->fastening);
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($product->height)): ?>
                        <tr>
                            <td class="spec-label">Высота:</td>
                            <td class="spec-value">
                                <?php 
                                $heightTranslations = [
                                    'low' => 'Низкие',
                                    'mid' => 'Средние',
                                    'high' => 'Высокие',
                                    'ankle' => 'По щиколотку',
                                    'knee' => 'До колена',
                                    'over-knee' => 'Выше колена',
                                ];
                                echo Html::encode($heightTranslations[strtolower($product->height)] ?? $product->height);
                                ?>
                            </td>
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
                    </table>
                </div>
                <?php endif; ?>

                <?php 
                // Дополнительные характеристики из Poizon properties
                if ($product->properties):
                    $properties = json_decode($product->properties, true);
                    if (is_array($properties) && !empty($properties)):
                ?>
                <div class="spec-section">
                    <h3>Дополнительные характеристики</h3>
                    <table class="specs-table">
                        <?php 
                        foreach ($properties as $prop):
                            $key = $prop['key'] ?? '';
                            $value = $prop['value'] ?? '';
                            if ($key && $value):
                        ?>
                        <tr>
                            <td class="spec-label"><?= Html::encode($key) ?>:</td>
                            <td class="spec-value"><?= Html::encode($value) ?></td>
                        </tr>
                        <?php 
                            endif;
                        endforeach;
                        ?>
                    </table>
                </div>
                <?php 
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

        <!-- Похожие товары - объединенная карусель -->
        <?php
        $allRelatedProducts = [];
        
        // Собираем товары из всех источников
        
        // 1. Из related_products_json (импорт Poizon)
        if (!empty($product->related_products_json)) {
            $relatedIds = json_decode($product->related_products_json, true);
            if (!empty($relatedIds) && is_array($relatedIds)) {
                $relatedProducts = \app\models\Product::find()
                    ->where(['id' => $relatedIds, 'is_active' => 1])
                    ->limit(50)
                    ->all();
                $allRelatedProducts = array_merge($allRelatedProducts, $relatedProducts);
            }
        }
        
        // 2. По series_name
        if (!empty($product->series_name)) {
            $seriesProducts = \app\models\Product::find()
                ->where(['series_name' => $product->series_name, 'is_active' => 1])
                ->andWhere(['!=', 'id', $product->id])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $seriesProducts);
        }
        
        // 3. Похожие товары (similarProducts из контроллера)
        if (!empty($similarProducts) && is_array($similarProducts)) {
            $allRelatedProducts = array_merge($allRelatedProducts, $similarProducts);
        }
        
        // 4. Товары того же бренда и категории (если еще мало)
        if (count($allRelatedProducts) < 12 && $product->brand_id) {
            $brandProducts = \app\models\Product::find()
                ->where([
                    'brand_id' => $product->brand_id,
                    'category_id' => $product->category_id,
                    'is_active' => 1
                ])
                ->andWhere(['!=', 'id', $product->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $brandProducts);
        }
        
        // 5. Если всё еще мало товаров - берем просто из той же категории
        if (count($allRelatedProducts) < 12 && $product->category_id) {
            $categoryProducts = \app\models\Product::find()
                ->where(['category_id' => $product->category_id, 'is_active' => 1])
                ->andWhere(['!=', 'id', $product->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $categoryProducts);
        }
        
        // 6. Последний fallback - просто любые активные товары
        if (count($allRelatedProducts) < 12) {
            $anyProducts = \app\models\Product::find()
                ->where(['is_active' => 1])
                ->andWhere(['!=', 'id', $product->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $anyProducts);
        }
        
        // Удаляем дубликаты по ID
        $uniqueProducts = [];
        $seenIds = [];
        foreach ($allRelatedProducts as $prod) {
            if (!in_array($prod->id, $seenIds)) {
                $uniqueProducts[] = $prod;
                $seenIds[] = $prod->id;
            }
        }
        ?>
        
        <!-- Показываем блок всегда, даже если нет товаров -->
        <?php if (!empty($uniqueProducts)): ?>
        <style>
            /* Адаптивный блок похожих товаров */
            .related-products-section {
                margin: 2rem 0;
                background: #fff;
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e5e7eb;
            }
            
            .related-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid #e5e7eb;
                cursor: pointer;
                user-select: none;
                background: linear-gradient(135deg, #f9fafb 0%, #fff 100%);
                transition: background 0.2s;
            }
            
            .related-header:hover {
                background: #f3f4f6;
            }
            
            .related-header h2 {
                margin: 0;
                font-size: 1.125rem;
                font-weight: 700;
                color: #111;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .related-header .toggle-icon {
                font-size: 1.25rem;
                transition: transform 0.3s;
                color: #666;
            }
            
            .related-header.active .toggle-icon {
                transform: rotate(180deg);
            }
            
            .related-content {
                padding: 1.5rem 1rem;
            }
            
            /* Карусель */
            .related-carousel {
                position: relative;
                padding: 0;
            }
            
            .carousel-wrapper {
                overflow-x: auto;
                overflow-y: visible;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                -ms-overflow-style: none;
                scroll-behavior: smooth;
                margin: 0 -1rem;
                padding: 0 1rem;
            }
            
            .carousel-wrapper::-webkit-scrollbar {
                display: none;
            }
            
            .carousel-track {
                display: flex;
                gap: 1rem;
                padding: 0.5rem 0;
            }
            
            /* Карточки товаров */
            .related-product-card {
                flex: 0 0 auto;
                width: 150px;
                background: #fff;
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e5e7eb;
                text-decoration: none;
                transition: all 0.3s;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }
            
            .related-product-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
                border-color: #d1d5db;
            }
            
            .related-product-image {
                position: relative;
                width: 100%;
                padding-top: 100%;
                background: #f9fafb;
                overflow: hidden;
            }
            
            .related-product-image img {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                object-fit: contain;
                padding: 8px;
            }
            
            .related-discount-badge {
                position: absolute;
                top: 8px;
                right: 8px;
                background: linear-gradient(135deg, #ef4444, #dc2626);
                color: #fff;
                padding: 4px 8px;
                border-radius: 8px;
                font-size: 0.7rem;
                font-weight: 700;
                box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
            }
            
            .related-product-info {
                padding: 0.875rem;
            }
            
            .related-brand {
                font-size: 0.7rem;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 0.25rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .related-product-name {
                font-size: 0.8125rem;
                font-weight: 600;
                color: #111;
                line-height: 1.3;
                margin-bottom: 0.5rem;
                height: 2.6em;
                overflow: hidden;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }
            
            .related-price {
                font-size: 0.9375rem;
                font-weight: 700;
                color: #111;
            }
            
            .related-price-range {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            
            .related-price-from {
                font-size: 0.9375rem;
                font-weight: 700;
                color: #111;
            }
            
            .related-price-to {
                font-size: 0.7rem;
                color: #6b7280;
            }
            
            /* Кнопки навигации */
            .carousel-nav-btn {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 44px;
                height: 44px;
                border-radius: 50%;
                border: 2px solid #e5e7eb;
                background: #fff;
                display: none;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 10;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transition: all 0.2s;
            }
            
            .carousel-nav-btn:hover {
                background: #f9fafb;
                border-color: #d1d5db;
                transform: translateY(-50%) scale(1.05);
            }
            
            .carousel-nav-btn:active {
                transform: translateY(-50%) scale(0.95);
            }
            
            .carousel-nav-btn i {
                font-size: 1.25rem;
                color: #374151;
            }
            
            .carousel-nav-btn.prev {
                left: -22px;
            }
            
            .carousel-nav-btn.next {
                right: -22px;
            }
            
            /* Медиазапросы для адаптивности */
            @media (min-width: 480px) {
                .related-product-card {
                    width: 180px;
                }
                
                .carousel-track {
                    gap: 1.25rem;
                }
            }
            
            @media (min-width: 768px) {
                .related-header h2 {
                    font-size: 1.25rem;
                }
                
                .related-content {
                    padding: 2rem 1.5rem;
                }
                
                .related-product-card {
                    width: 200px;
                }
                
                .carousel-nav-btn {
                    display: flex;
                }
                
                .carousel-wrapper {
                    margin: 0;
                    padding: 0 3rem;
                }
            }
            
            @media (min-width: 1024px) {
                .related-product-card {
                    width: 220px;
                }
                
                .related-product-name {
                    font-size: 0.875rem;
                }
                
                .related-price {
                    font-size: 1rem;
                }
            }
            
            /* Плавная анимация */
            @media (prefers-reduced-motion: reduce) {
                .carousel-wrapper {
                    scroll-behavior: auto;
                }
                
                .related-product-card,
                .carousel-nav-btn,
                .related-header {
                    transition: none;
                }
            }
        </style>
        
        <div class="related-products-section">
            <div class="related-header active" onclick="toggleRelatedProducts()">
                <h2>
                    <span>🛍️</span>
                    Похожие товары
                    <span style="color: #6b7280; font-weight: 400; font-size: 0.9em;">(<?= count($uniqueProducts) ?>)</span>
                </h2>
                <i class="bi bi-chevron-down toggle-icon" id="relatedToggleIcon"></i>
            </div>
            <div class="related-content" id="relatedContent">
                <div class="related-carousel">
                    <button class="carousel-nav-btn prev" onclick="scrollRelatedCarousel(-1)" aria-label="Предыдущий товар">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    
                    <div class="carousel-wrapper" id="relatedCarouselWrapper">
                        <div class="carousel-track">
                            <?php foreach ($uniqueProducts as $item): 
                                $priceRange = $item->getPriceRange();
                            ?>
                            <a href="<?= $item->getUrl() ?>" class="related-product-card">
                                <div class="related-product-image">
                                    <img src="<?= $item->getMainImageUrl() ?>" 
                                         alt="<?= Html::encode($item->name) ?>" 
                                         loading="lazy">
                                    <?php if ($item->hasDiscount()): ?>
                                        <span class="related-discount-badge">-<?= $item->getDiscountPercent() ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="related-product-info">
                                    <div class="related-brand"><?= Html::encode($item->brand->name) ?></div>
                                    <div class="related-product-name"><?= Html::encode($item->name) ?></div>
                                    <?php if ($priceRange): ?>
                                        <div class="related-price-range">
                                            <span class="related-price-from">от <?= Yii::$app->formatter->asCurrency($priceRange['min'], 'BYN') ?></span>
                                            <?php if ($priceRange['min'] != $priceRange['max']): ?>
                                                <span class="related-price-to">до <?= Yii::$app->formatter->asCurrency($priceRange['max'], 'BYN') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="related-price"><?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button class="carousel-nav-btn next" onclick="scrollRelatedCarousel(1)" aria-label="Следующий товар">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Отзывы покупателей -->
        <div class="reviews-enhanced" id="reviews">
            <div class="reviews-header" onclick="toggleReviews()">
                <h2><i class="bi bi-chat-left-text"></i> Отзывы покупателей<?php if (!empty($product->reviews_count)): ?> (<?= $product->reviews_count ?>)<?php endif; ?></h2>
                <i class="bi bi-chevron-down" id="reviewsToggleIcon"></i>
            </div>
            <div class="reviews-list" id="reviewsContent" style="display:none">
                <?php if (!empty($product->reviews) && count($product->reviews) > 0): ?>
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
                        <div class="review-rating-stars"><?= str_repeat('<i class="bi bi-star-fill"></i>', $review->rating) ?></div>
                        <div class="review-text"><?= Html::encode($review->content) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="placeholder-content">
                        <i class="bi bi-chat-left-text" style="font-size:3rem;color:#ccc;"></i>
                        <h3>Отзывов пока нет</h3>
                        <p>Будьте первым, кто оставит отзыв о этом товаре</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Q&A раздел -->
        <div class="community-qa">
            <div class="qa-header" onclick="toggleQA()">
                <h2>💬 Вопросы и ответы</h2>
                <i class="bi bi-chevron-down" id="qaToggleIcon"></i>
            </div>
            <div class="qa-list" id="qaContent" style="display:none">
                <?php if (!empty($product->questions) && count($product->questions) > 0): ?>
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
                <?php else: ?>
                    <div class="placeholder-content">
                        <i class="bi bi-question-circle" style="font-size:3rem;color:#ccc;"></i>
                        <h3>Вопросов пока нет</h3>
                        <p>Задайте первый вопрос о этом товаре</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Purchase Bar удалён - используется улучшенная версия ниже -->

<!-- Premium Image Gallery Modal -->
<div class="image-gallery-modal" id="imageGalleryModal" style="display:none">
    <div class="gallery-modal-content">
        <button class="gallery-close" onclick="closeImageGallery()">
            <i class="bi bi-x-lg"></i>
        </button>
        
        <div class="gallery-scroll-container">
            <?php if (!empty($product->images)): ?>
                <?php foreach ($product->images as $index => $img): ?>
                <div class="gallery-image-item" data-index="<?= $index ?>">
                    <img src="<?= $img->getUrl() ?>" alt="<?= Html::encode($product->name) ?> - фото <?= $index + 1 ?>" loading="lazy">
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="gallery-image-item" data-index="0">
                    <img src="<?= $product->getMainImageUrl() ?>" alt="<?= Html::encode($product->name) ?>">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="gallery-counter">
            <span class="gallery-current">1</span> / <span class="gallery-total"><?= !empty($product->images) ? count($product->images) : 1 ?></span>
        </div>
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

<!-- Улучшенная Sticky Purchase Bar с выбором размера -->
<div class="sticky-purchase-bar" id="stickyBar">
    <div class="sticky-product-info">
        <img src="<?= $product->getMainImageUrl() ?>" class="sticky-thumb" alt="<?= Html::encode($product->name) ?>">
        <div class="sticky-details">
            <div class="sticky-name"><?= Html::encode($product->name) ?></div>
            <div class="sticky-price" id="stickyPrice"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></div>
        </div>
    </div>
    
    <?php if (!empty($product->sizes)): ?>
    <div class="sticky-size-selector">
        <button class="sticky-size-btn" id="stickySizeBtn" onclick="toggleStickySizeDropdown()">
            <span id="stickySizeLabel">Размер</span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <div class="sticky-size-dropdown" id="stickySizeDropdown">
            <?php 
            $sizeCount = 0;
            foreach ($product->availableSizes as $size): 
                $priceByn = $size->getPriceByn();
                $inStock = $size->inStock();
                if (!$inStock) continue;
                $sizeCount++;
            ?>
                <div class="sticky-size-option" 
                     data-size="<?= Html::encode($size->size) ?>"
                     data-price="<?= $priceByn ?>">
                    <span class="size"><?= Html::encode($size->eu_size ?: $size->size) ?> EU</span>
                    <span class="price"><?= Yii::$app->formatter->asCurrency($priceByn, 'BYN') ?></span>
                </div>
            <?php endforeach; ?>
            <?php if ($sizeCount === 0): ?>
                <div style="padding:1rem;color:#999;text-align:center;">Нет доступных размеров</div>
            <?php endif; ?>
        </div>
    </div>
    <!-- DEBUG: Всего размеров в наличии: <?= $sizeCount ?> -->
    <?php endif; ?>
    
    <button class="sticky-add-cart" onclick="addToCartFromSticky()">
        <i class="bi bi-cart-plus"></i>
        <span class="d-none d-md-inline">В корзину</span>
    </button>
</div>

<!-- Модальное окно "Купить в 1 клик" -->
<div class="quick-order-modal" id="quickOrderModal" style="display:none">
    <div class="quick-order-content">
        <button class="modal-close" onclick="closeQuickOrderModal()">✕</button>
        
        <div class="modal-header">
            <div class="modal-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <h2>Быстрый заказ</h2>
            <p class="modal-subtitle">Оформите заказ за 30 секунд</p>
        </div>
        
        <div class="modal-body">
            <div class="quick-order-product">
                <img src="<?= $product->getMainImageUrl() ?>" alt="<?= Html::encode($product->name) ?>">
                <div class="product-info">
                    <div class="brand"><?= Html::encode($product->brand->name) ?></div>
                    <div class="name"><?= Html::encode($product->name) ?></div>
                    <div class="price" id="quickOrderPrice"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></div>
                </div>
            </div>
            
            <form id="quickOrderForm" onsubmit="submitQuickOrder(event)">
                <?php if (!empty($product->sizes)): ?>
                <div class="form-group">
                    <label for="quickOrderSize">
                        <i class="bi bi-rulers"></i>
                        Размер *
                    </label>
                    <select id="quickOrderSize" name="size" required class="form-control">
                        <option value="">Выберите размер</option>
                        <?php foreach ($product->availableSizes as $size): 
                            $priceByn = $size->getPriceByn();
                            $inStock = $size->inStock();
                            if (!$inStock) continue;
                        ?>
                            <option value="<?= Html::encode($size->size) ?>" data-price="<?= $priceByn ?>">
                                <?= Html::encode($size->eu_size ?: $size->size) ?> EU - <?= Yii::$app->formatter->asCurrency($priceByn, 'BYN') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="quickOrderName">
                        <i class="bi bi-person"></i>
                        Ваше имя *
                    </label>
                    <input type="text" id="quickOrderName" name="name" required class="form-control" placeholder="Иван">
                </div>
                
                <div class="form-group">
                    <label for="quickOrderPhone">
                        <i class="bi bi-telephone"></i>
                        Телефон *
                    </label>
                    <input type="tel" id="quickOrderPhone" name="phone" required class="form-control" 
                           placeholder="+375 (29) 123-45-67"
                           pattern="[\+]?[0-9\s\(\)\-]+">
                </div>
                
                <div class="form-group">
                    <label for="quickOrderComment">
                        <i class="bi bi-chat-left-text"></i>
                        Комментарий (необязательно)
                    </label>
                    <textarea id="quickOrderComment" name="comment" class="form-control" rows="2" 
                              placeholder="Удобное время для звонка, дополнительные пожелания..."></textarea>
                </div>
                
                <div class="quick-order-benefits">
                    <div class="benefit">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Менеджер свяжется с вами в течение 15 минут</span>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit" id="quickOrderSubmitBtn">
                    <i class="bi bi-lightning-charge-fill"></i>
                    Оформить заказ
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Product Page Specific Styles - остальное в mobile-first.css */

/* Product Page Container */
html, body {
    overflow-x:hidden;
    max-width:100%;
}

.product-page-optimized{
    width:100%;
    max-width:100vw;
    overflow-x:hidden;
}

.product-container{
    width:100%;
    margin:0 auto;
    padding:1rem;
    box-sizing:border-box;
    /* max-width управляется через product.css */
}

.breadcrumbs{
    padding:1rem 0;
    color:#666;
    font-size:0.8125rem;
    overflow-x:auto;
    white-space:nowrap;
}

.breadcrumbs a{color:#666;text-decoration:none}
.breadcrumbs a:hover{color:#000}

.product-layout{
    display:grid;
    grid-template-columns:1fr;
    gap:2rem;
    padding:1rem 0;
    width:100%;
    max-width:100%;
}

/* Swipe Gallery - стили управляются через product.css и mobile-first.css */

.swipe-track{
    display:flex;
    transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);
    cursor:grab;
    user-select:none;
    border-radius:8px;
    /* width и overflow убраны - позволяем flex растянуть track */
}

.swipe-track:active{cursor:grabbing}

/* Стили .swipe-slide и .swipe-slide img управляются через product.css */
.swipe-slide{
    flex-shrink:0;
    width:100%;
}

.zoom-icon{
    position:absolute;
    bottom:1rem;
    left:1rem;
    width:44px;
    height:44px;
    border-radius:50%;
    background:rgba(255,255,255,0.95);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.25rem;
    color:#666;
    opacity:0;
    transition:all 0.3s;
    pointer-events:none;
}

.swipe-slide:hover .zoom-icon{opacity:1}

.swipe-pagination{
    position:absolute;
    bottom:1rem;
    left:50%;
    transform:translateX(-50%);
    display:flex;
    gap:0.5rem;
    z-index:10;
}

.swipe-dot{
    width:8px;
    height:8px;
    border-radius:50%;
    background:rgba(255,255,255,0.5);
    cursor:pointer;
    transition:all 0.3s;
    border:none;
}

.swipe-dot.active{
    background:#fff;
    width:24px;
    border-radius:4px;
}

/* Адаптив для планшетов */
@media (max-width:1024px){
    .product-gallery-swipe{
        padding:1.25rem;
        margin-bottom:0.5rem;
    }
    .swipe-slide{
        min-height:280px;
    }
    .product-thumbnails-carousel{
        margin-bottom:1.5rem;
        padding:0.875rem;
        gap:0.75rem;
    }
    .thumbnail-item{
        width:85px;
        height:85px;
        border-radius:10px;
    }
    .thumb-nav{
        width:38px;
        height:38px;
    }
    .thumb-nav i{
        font-size:1rem;
    }
}

/* Адаптив для мобильных */
@media (max-width:768px){
    .product-container{
        padding:0.5rem;
        max-width:100%;
    }
    
    .breadcrumbs{
        padding:0.5rem 0;
        font-size:0.75rem;
        -webkit-overflow-scrolling:touch;
    }
    
    .product-layout{
        gap:1rem;
        padding:0.5rem 0;
    }
    
    .product-gallery-swipe{
        padding:0.75rem;
        margin-bottom:0.5rem;
        margin-left:0;
        margin-right:0;
        border-radius:8px;
        width:100%;
        max-width:100%;
    }
    
    .swipe-slide{
        min-height:300px;
        aspect-ratio:1/1;
    }
    
    .swipe-slide img{
        object-fit:contain;
        object-position:center;
    }
    
    .zoom-icon{
        width:36px;
        height:36px;
        font-size:1rem;
        bottom:0.75rem;
        left:0.75rem;
    }
    
    .swipe-pagination{
        bottom:0.75rem;
    }
    
    .product-thumbnails-carousel{
        margin-bottom:1rem;
        margin-left:0;
        margin-right:0;
        padding:0.75rem 0.5rem;
        width:100%;
        max-width:100%;
    }
    
    .product-details{
        padding:0;
        width:100%;
        max-width:100%;
    }
}

/* Адаптив для маленьких мобильных */
@media (max-width:480px){
    .product-container{
        padding:0.5rem;
        max-width:100%;
    }
    
    .breadcrumbs{
        padding:0.5rem 0;
        font-size:0.75rem;
    }
    
    .product-layout{
        gap:0.75rem;
        padding:0.5rem 0;
    }
    
    .product-gallery-swipe{
        padding:0.625rem;
        margin-bottom:0.5rem;
        margin-left:0;
        margin-right:0;
        width:100%;
        max-width:100%;
    }
    
    .swipe-slide{
        min-height:280px;
    }
    
    .zoom-icon{
        width:32px;
        height:32px;
        font-size:0.875rem;
        bottom:0.5rem;
        left:0.5rem;
    }
    
    .swipe-pagination{
        bottom:0.5rem;
        gap:0.375rem;
    }
    
    .swipe-dot{
        width:6px;
        height:6px;
    }
    
    .swipe-dot.active{
        width:18px;
    }
    
    .product-thumbnails-carousel{
        margin-bottom:0.75rem;
        margin-left:0;
        margin-right:0;
        padding:0.625rem 0.5rem;
        gap:0.5rem;
        width:100%;
        max-width:100%;
    }
    
    .product-details{
        padding:0;
        width:100%;
        max-width:100%;
    }
}

/* Адаптив для iPhone 12/13/14 Pro (390px) и подобных */
@media (max-width:414px){
    .product-container{
        padding:0.5rem 0.375rem;
    }
    
    .product-gallery-swipe{
        padding:0.5rem;
        border-radius:8px;
    }
    
    .swipe-slide{
        min-height:280px;
    }
    
    .product-thumbnails-carousel{
        padding:0.625rem 0.375rem;
        gap:0.5rem;
    }
    
    .thumbnail-item{
        width:70px;
        height:70px;
    }
    
    .thumb-nav{
        width:32px;
        height:32px;
    }
    
    .brand-card{
        padding:0.75rem;
        gap:0.625rem;
    }
    
    .sizes-quick-select{
        width:100%;
        max-width:100%;
    }
    
    .size-compact{
        min-width:48px;
        padding:0.5rem 0.625rem;
    }
    
    .size-compact .size-value{
        font-size:0.9375rem;
    }
    
    .product-details h1{
        font-size:1.375rem;
    }
    
    .sticky-purchase-bar{
        padding:0.75rem 0.5rem;
        gap:0.5rem;
    }
    
    .sticky-product-info{
        gap:0.5rem;
    }
    
    .sticky-thumb{
        width:44px;
        height:44px;
    }
    
    .sticky-name{
        font-size:0.8125rem;
    }
    
    .sticky-price{
        font-size:1rem;
    }
    
    .sticky-size-btn{
        min-width:100px;
        padding:0.625rem 0.75rem;
        font-size:0.875rem;
    }
    
    .sticky-add-cart{
        padding:0.75rem 1rem;
        font-size:0.875rem;
    }
}

/* Очень узкие экраны (iPhone SE, малые Android) */
@media (max-width:375px){
    .product-container{
        padding:0.375rem;
    }
    
    .product-gallery-swipe{
        padding:0.375rem;
    }
    
    .swipe-slide{
        min-height:260px;
    }
    
    .product-thumbnails-carousel{
        padding:0.5rem 0.25rem;
        gap:0.375rem;
    }
    
    .thumbnail-item{
        width:65px;
        height:65px;
    }
    
    .thumb-nav{
        width:28px;
        height:28px;
    }
    
    .brand-card{
        padding:0.625rem;
        gap:0.5rem;
        font-size:0.875rem;
    }
    
    .brand-logo{
        width:40px;
        height:40px;
    }
    
    .size-compact{
        min-width:45px;
        padding:0.5rem;
        font-size:0.875rem;
    }
    
    .size-compact .size-value{
        font-size:0.875rem;
    }
    
    .product-details h1{
        font-size:1.25rem;
        line-height:1.3;
    }
    
    .sticky-purchase-bar{
        padding:0.625rem 0.375rem;
        gap:0.375rem;
        min-height:64px;
    }
    
    .sticky-thumb{
        width:40px;
        height:40px;
    }
    
    .sticky-name{
        font-size:0.75rem;
    }
    
    .sticky-price{
        font-size:0.9375rem;
    }
    
    .sticky-size-btn{
        min-width:90px;
        padding:0.5rem 0.625rem;
        font-size:0.8125rem;
    }
    
    .sticky-add-cart{
        padding:0.625rem 0.875rem;
        font-size:0.8125rem;
    }
    
    .sticky-add-cart span{
        display:none;
    }
}

/* Миниатюры галереи - вынесены за пределы контейнера */
.product-thumbnails-carousel{
    position:relative;
    display:flex !important;
    align-items:center;
    justify-content:center;
    gap:0.875rem;
    margin-top:0;
    margin-bottom:2rem;
    padding:1rem;
    width:100%;
    box-sizing:border-box;
    background:#fff;
    border-radius:12px;
    border:1px solid #e5e7eb;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
}

.thumbnails-wrapper{
    flex:1;
    overflow-x:auto;
    overflow-y:hidden;
    scroll-behavior:smooth;
    scrollbar-width:none;
    -ms-overflow-style:none;
    max-width:100%;
}

.thumbnails-wrapper::-webkit-scrollbar{display:none}

.thumbnails-track{
    display:flex;
    gap:0.75rem;
    padding:0.25rem 0;
    align-items:center;
    justify-content:flex-start;
}

.thumbnail-item{
    flex-shrink:0;
    width:90px;
    height:90px;
    border-radius:12px;
    overflow:hidden;
    cursor:pointer;
    border:3px solid #e5e7eb;
    transition:all 0.25s ease;
    background:#fafafa;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 2px 6px rgba(0,0,0,0.08);
}

.thumbnail-item img{
    width:100%;
    height:100%;
    object-fit:cover;
    transition:transform 0.25s ease;
    display:block;
}

.thumbnail-item:hover{
    border-color:#3b82f6;
    transform:translateY(-3px) scale(1.05);
    box-shadow:0 6px 16px rgba(59,130,246,0.3);
}

.thumbnail-item.active{
    border-color:#000;
    box-shadow:0 6px 20px rgba(0,0,0,0.25);
    transform:translateY(-3px);
    background:#fff;
}

.thumbnail-item:hover img{
    transform:scale(1.1);
}

.thumb-nav{
    width:40px;
    height:40px;
    border-radius:50%;
    background:#fff;
    border:2px solid #e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:all 0.2s ease;
    flex-shrink:0;
    padding:0;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.thumb-nav:hover{
    background:#f3f4f6;
    border-color:#000;
    transform:scale(1.1);
    box-shadow:0 4px 12px rgba(0,0,0,0.2);
}

.thumb-nav:active{
    transform:scale(0.95);
}

.thumb-nav i{
    font-size:1.125rem;
    color:#666;
    line-height:1;
}

.thumb-nav:hover i{
    color:#000;
}

/* Адаптив для планшетов */
@media (max-width:1024px){
    .product-thumbnails-carousel{
        gap:0.5rem;
        margin-top:1rem;
    }
    .thumbnail-item{
        width:72px;
        height:72px;
    }
    .thumb-nav{
        width:32px;
        height:32px;
    }
    .thumb-nav i{
        font-size:0.875rem;
    }
}

/* Адаптив для мобильных */
@media (max-width:768px){
    .product-thumbnails-carousel{
        display:flex !important;
        gap:0.5rem;
        margin-top:1rem;
        padding:0;
    }
    
    .thumbnail-item{
        width:68px;
        height:68px;
        border-radius:8px;
        border-width:2px;
    }
    
    .thumb-nav{
        width:32px;
        height:32px;
        border-width:1.5px;
    }
    
    .thumb-nav i{
        font-size:0.875rem;
    }
}

/* Адаптив для маленьких мобильных */
@media (max-width:480px){
    .product-thumbnails-carousel{
        gap:0.375rem;
    }
    
    .thumbnails-track{
        gap:0.5rem;
    }
    
    .thumbnail-item{
        width:60px;
        height:60px;
        border-radius:6px;
    }
    
    .thumb-nav{
        width:28px;
        height:28px;
    }
    
    .thumb-nav i{
        font-size:0.75rem;
    }
}

.fav-btn{position:absolute;top:1rem;right:1rem;width:48px;height:48px;background:rgba(255,255,255,0.95);border:none;border-radius:50%;cursor:pointer;font-size:1.5rem;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:10}
.fav-btn i{color:#666;transition:color 0.2s}
.fav-btn.active i{color:#ef4444}
.fav-btn:hover{transform:scale(1.1);background:#fff}

/* Old gallery styles - для совместимости */
.product-gallery{display:flex;flex-direction:column;gap:1rem}
.main-img{position:relative;background:#f9fafb;border-radius:12px;overflow:hidden;cursor:zoom-in}
.thumbs{display:flex;gap:0.5rem;overflow-x:auto}

.product-details{
    display:flex;
    flex-direction:column;
    gap:1rem;
    width:100%;
    max-width:100%;
    box-sizing:border-box;
}

/* Премиум блок бренда */
.brand-block-premium{
    margin-bottom:0.5rem;
}

.brand-card{
    display:flex;
    align-items:center;
    gap:0.875rem;
    padding:0.875rem 1rem;
    background:linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
    border:1.5px solid #e5e7eb;
    border-radius:12px;
    text-decoration:none;
    transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position:relative;
    overflow:hidden;
}

.brand-card::before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
    transition:left 0.5s;
}

.brand-card:hover::before{
    left:100%;
}

.brand-card:hover{
    border-color:#3b82f6;
    box-shadow:0 4px 12px rgba(59, 130, 246, 0.15);
    transform:translateY(-2px);
}

.brand-logo{
    width:42px;
    height:42px;
    border-radius:8px;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    flex-shrink:0;
    box-shadow:0 2px 4px rgba(0,0,0,0.05);
}

.brand-logo img{
    width:100%;
    height:100%;
    object-fit:contain;
}

.brand-info{
    flex:1;
    display:flex;
    flex-direction:column;
    gap:0.125rem;
}

.brand-name{
    font-size:0.9375rem;
    font-weight:700;
    color:#111827;
    letter-spacing:0.3px;
}

.brand-count{
    font-size:0.75rem;
    font-weight:600;
    color:#6b7280;
    letter-spacing:0.2px;
}

.brand-arrow{
    font-size:1.125rem;
    color:#9ca3af;
    transition:all 0.3s;
}

.brand-card:hover .brand-arrow{
    color:#3b82f6;
    transform:translateX(4px);
}

.product-details h1{font-size:1.5rem;font-weight:800;line-height:1.2;margin-top:0}

.product-rating{display:flex;align-items:center;gap:0.75rem;padding:0.75rem 0}
.stars-large{display:flex;gap:2px;color:#fbbf24;font-size:1.25rem}
.rating-score{font-size:1.125rem;font-weight:700;color:#000}
.reviews-link{color:#666;text-decoration:none;font-size:0.875rem}
.reviews-link:hover{color:#000;text-decoration:underline}

.price-block{display:flex;align-items:center;gap:0.75rem}
.price-block .old{font-size:1rem;color:#9ca3af;text-decoration:line-through}
.price-block .disc{background:#ef4444;color:#fff;padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem;font-weight:700}
.price-block .current{font-size:2rem;font-weight:900;color:#000;transition:transform 0.2s ease}
.price-block .price-separator{font-size:1.5rem;color:#6b7280;font-weight:600;margin:0 0.25rem}

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

/* Product Variations (Colors) */
.product-variations{margin-bottom:2rem;padding:1rem;background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb}
.product-variations h3{font-size:0.875rem;font-weight:700;margin:0 0 1rem 0;color:#333}
.product-variations h3 span{color:#000;font-weight:800}
.color-variations{display:flex;gap:0.75rem;flex-wrap:wrap}
.color-variation{display:flex;align-items:center;gap:0.5rem;padding:0.625rem 1rem;background:#fff;border:2px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:all 0.2s;font-size:0.875rem;font-weight:600}
.color-variation:hover{border-color:#667eea;transform:translateY(-2px);box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.color-variation.active{border-color:#667eea;background:#f0f3ff;box-shadow:0 2px 8px rgba(102,126,234,0.2)}
.color-circle{width:24px;height:24px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px #e5e7eb}
.color-name{color:#333}

.sizes-section{
    margin-bottom:2.5rem;
    padding:1.5rem;
    background:linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
    border:1.5px solid #e5e7eb;
    border-radius:16px;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
}

.size-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:1rem;
    padding-bottom:1rem;
    border-bottom:1.5px solid #e5e7eb;
}

.size-header h3{
    font-size:1rem;
    font-weight:800;
    margin:0;
    color:#111827;
    letter-spacing:-0.3px;
}

.btn-size-guide{
    background:#ffffff;
    border:1.5px solid #e5e7eb;
    padding:0.625rem 1.125rem;
    border-radius:10px;
    font-size:0.8125rem;
    font-weight:700;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:0.5rem;
    transition:all 0.3s;
    color:#6b7280;
    box-shadow:0 1px 3px rgba(0,0,0,0.05);
}

.btn-size-guide:hover{
    background:#3b82f6;
    border-color:#3b82f6;
    color:#ffffff;
    transform:scale(1.05);
    box-shadow:0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-size-guide i{
    font-size:1rem;
    transition:transform 0.3s;
}

.btn-size-guide:hover i{
    transform:rotate(15deg) scale(1.15);
}

/* Премиум табы размеров */
.sizes-quick-select{margin-top:1rem}

.size-system-tabs{
    display:flex;
    gap:0.375rem;
    margin-bottom:1.25rem;
    background:linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    padding:0.375rem;
    border-radius:12px;
    border:1.5px solid #e5e7eb;
    box-shadow:inset 0 2px 4px rgba(0,0,0,0.05);
}

.size-tab{
    flex:1;
    padding:0.75rem 1rem;
    background:transparent;
    border:none;
    border-radius:9px;
    font-weight:700;
    font-size:0.875rem;
    cursor:pointer;
    transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color:#6b7280;
    letter-spacing:0.5px;
    position:relative;
}

.size-tab::before{
    content:'';
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%, -50%) scale(0.8);
    width:100%;
    height:100%;
    background:linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    border-radius:9px;
    opacity:0;
    transition:all 0.3s;
    z-index:-1;
}

.size-tab:hover{
    color:#111827;
    background:#ffffff;
    box-shadow:0 2px 4px rgba(0,0,0,0.06);
}

.size-tab.active{
    color:#ffffff;
    background:transparent;
    box-shadow:0 4px 12px rgba(59, 130, 246, 0.3);
    transform:scale(1.02);
}

.size-tab.active::before{
    opacity:1;
    transform:translate(-50%, -50%) scale(1);
}

.sizes{display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.75rem}

/* Премиум размеры */
.size-compact{
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    background:linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border:2px solid #e5e7eb;
    border-radius:10px;
    padding:0.75rem 1.125rem;
    transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor:pointer;
    min-width:65px;
    box-shadow:0 1px 3px rgba(0,0,0,0.05);
}

.size-compact::before{
    content:'';
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
    border-radius:8px;
    opacity:0;
    transition:opacity 0.3s;
}

.size-compact:hover{
    border-color:#3b82f6;
    transform:translateY(-3px) scale(1.02);
    box-shadow:0 4px 12px rgba(59, 130, 246, 0.2);
}

.size-compact:hover::before{
    opacity:1;
}

.size-compact input{display:none}

.size-compact .size-value{
    font-size:1.0625rem;
    font-weight:800;
    color:#111827;
    text-align:center;
    position:relative;
    z-index:1;
    letter-spacing:0.3px;
}

.size-compact:has(input:checked){
    border-color:#3b82f6;
    background:linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    box-shadow:0 6px 20px rgba(59, 130, 246, 0.4);
    transform:translateY(-3px) scale(1.05);
}

.size-compact:has(input:checked)::before{
    opacity:0;
}

.size-compact:has(input:checked) .size-value{
    color:#ffffff;
    text-shadow:0 1px 2px rgba(0,0,0,0.1);
}

.size-compact.disabled{
    opacity:0.35;
    cursor:not-allowed;
    pointer-events:none;
    background:#f3f4f6;
}
/* Ссылка на каталог с выбранным размером */
.selected-size-link{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border:1px solid #bae6fd;border-radius:8px;font-size:0.8125rem;color:#0369a1;margin-top:1rem;cursor:pointer;transition:all 0.2s;font-weight:500}
.selected-size-link:hover{background:linear-gradient(135deg,#e0f2fe 0%,#bae6fd 100%);border-color:#0ea5e9;transform:translateX(4px);box-shadow:0 2px 8px rgba(3,105,161,0.15)}
.selected-size-link i{font-size:1rem;color:#0369a1}
.selected-size-link strong{color:#000;font-weight:700;margin:0 0.25rem}

/* Enhanced Size Table */
.size-table .stock-badge{display:inline-flex;align-items:center;gap:0.25rem;padding:0.25rem 0.625rem;border-radius:6px;font-size:0.75rem;font-weight:700}
.size-table .stock-badge.in-stock{background:#ecfdf5;color:#10b981}
.size-table .stock-badge.out-stock{background:#fef2f2;color:#ef4444}
.size-table .size-row{transition:all 0.2s}
.size-table .size-row.available:hover{background:#f0f3ff;transform:scale(1.01);box-shadow:0 2px 8px rgba(102,126,234,0.15)}
.size-table .size-row.out-of-stock{opacity:0.5}

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
.custom-order-badge{display:inline-flex;align-items:center;gap:0.25rem;padding:0.25rem 0.625rem;border-radius:6px;font-size:0.75rem;font-weight:700;background:#ecfdf5;color:#10b981;text-transform:uppercase}
.custom-order-badge i{font-size:0.875rem}
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

/* Placeholders inside collapsible sections */
.reviews-list .placeholder-content,
.qa-list .placeholder-content{text-align:center;padding:3rem 1rem}
.placeholder-content{text-align:center;padding:3rem 1rem}
.placeholder-content h3{font-size:1.25rem;font-weight:600;color:#666;margin:1rem 0 0.5rem}
.placeholder-content p{color:#999;font-size:0.875rem}

@media (min-width:768px){
.trust-seals{gap:1.5rem}
.info-grid{grid-template-columns:repeat(3,1fr)}
}

/* Объединенный блок аутентичности и доверия (перед аккордеонами) */
.product-trust-section{background:#fff;border-radius:16px;padding:1.5rem;margin:2rem 0;box-shadow:0 2px 12px rgba(0,0,0,0.08);border:1px solid #e5e7eb}

/* Главный блок аутентичности */
.authenticity-main{display:flex;align-items:center;gap:1rem;padding:1.25rem;background:linear-gradient(135deg,#10b981 0%,#059669 100%);border-radius:12px;color:#fff;margin-bottom:1rem}
.authenticity-main .auth-icon{font-size:2.5rem;line-height:1}
.authenticity-main .auth-icon i{color:#fff}
.authenticity-main .auth-info{flex:1}
.authenticity-main .auth-title{font-size:1.125rem;font-weight:800;letter-spacing:0.5px;margin-bottom:0.25rem}
.authenticity-main .auth-subtitle{font-size:0.875rem;opacity:0.95}

/* Бейджи доверия */
.trust-badges{display:grid;grid-template-columns:repeat(3,1fr);gap:0.75rem}
.trust-badges .badge-item{display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.875rem;background:#f9fafb;border-radius:10px;font-size:0.8125rem;color:#666;font-weight:500;transition:all 0.2s;text-align:center}
.trust-badges .badge-item:hover{background:#f0f3ff;transform:translateY(-2px);box-shadow:0 2px 8px rgba(0,0,0,0.08)}
.trust-badges .badge-item i{font-size:1.125rem}
.trust-badges .badge-item:nth-child(1) i{color:#10b981}
.trust-badges .badge-item:nth-child(2) i{color:#3b82f6}
.trust-badges .badge-item:nth-child(3) i{color:#fbbf24}

/* Адаптив для мобильных */
@media (max-width:768px){
.product-trust-section{padding:1rem;margin:1.5rem 0}
.authenticity-main{flex-direction:column;text-align:center;padding:1rem}
.authenticity-main .auth-icon{font-size:2rem}
.authenticity-main .auth-title{font-size:1rem}
.trust-badges{grid-template-columns:1fr;gap:0.5rem}
.trust-badges .badge-item{justify-content:flex-start;padding:0.75rem}
}

/* Премиум аккордеоны (единый стиль для ВСЕХ секций) */
.product-description-section,
.product-specs-section,
.model-variants-section,
.complete-look,
.reviews-enhanced,
.community-qa,
.similar{
    background:linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border:1.5px solid #e5e7eb;
    border-radius:16px;
    padding:0;
    margin:2rem 0;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
    transition:all 0.3s;
}

.product-description-section:hover,
.product-specs-section:hover,
.model-variants-section:hover,
.complete-look:hover,
.reviews-enhanced:hover,
.community-qa:hover,
.similar:hover{
    box-shadow:0 4px 16px rgba(0,0,0,0.08);
}

/* Заголовки всех аккордеонов */
.desc-header,
.specs-header-toggle,
.variants-header,
.look-header,
.reviews-header,
.qa-header,
.similar-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    cursor:pointer;
    user-select:none;
    padding:1.5rem 1.75rem;
    transition:all 0.3s;
    background:linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
    border-bottom:1.5px solid #e5e7eb;
}

.desc-header:hover,
.specs-header-toggle:hover,
.variants-header:hover,
.look-header:hover,
.reviews-header:hover,
.qa-header:hover,
.similar-header:hover{
    background:linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

/* Заголовки H2 */
.desc-header h2,
.specs-header-toggle h2,
.variants-header h2,
.look-header h2,
.reviews-header h2,
.qa-header h2,
.similar-header h2{
    font-size:1.375rem;
    font-weight:800;
    margin:0;
    display:flex;
    align-items:center;
    gap:0.75rem;
    color:#111827;
    letter-spacing:-0.3px;
}

/* Иконки */
.desc-header i,
.specs-header-toggle i,
.variants-header i,
.look-header i,
.reviews-header i,
.qa-header i,
.similar-header i{
    font-size:1.25rem;
    transition:all 0.3s;
    color:#6b7280;
    background:#f3f4f6;
    width:36px;
    height:36px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:8px;
}

.desc-header:hover i,
.specs-header-toggle:hover i,
.variants-header:hover i,
.look-header:hover i,
.reviews-header:hover i,
.qa-header:hover i,
.similar-header:hover i{
    background:#3b82f6;
    color:#ffffff;
    transform:rotate(180deg);
}

/* Контент */
.desc-content{
    padding:1.75rem;
    font-size:1rem;
    line-height:1.8;
    color:#374151;
}

.desc-content p{margin-bottom:1rem}

#mainSpecsContent,
#variantsContent,
#completeLookContent,
#reviewsContent,
#qaContent,
#similarContent{
    padding:1.75rem;
}

/* Product Specifications - стили для секций внутри */
.spec-section{margin-bottom:1rem}
.spec-section h3{font-size:0.95rem;font-weight:700;margin-bottom:0.75rem;color:#000;display:flex;align-items:center;gap:0.5rem}
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
.feature-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:8px;font-size:0.8125rem;font-weight:700;letter-spacing:0.3px;text-transform:uppercase}
.feature-badge.yes{background:#ecfdf5;color:#10b981}
.feature-badge.no{background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;box-shadow:0 2px 8px rgba(139,92,246,0.3);border:1px solid rgba(255,255,255,0.2)}
.feature-badge.no i{animation:status-pulse 2s ease-in-out infinite}
@keyframes status-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08);filter:brightness(1.2)}}
.tech-badges{display:flex;gap:0.5rem;flex-wrap:wrap}
.tech-badge{background:#f3f4f6;padding:0.25rem 0.75rem;border-radius:6px;font-size:0.8125rem;font-weight:600;color:#666}

/* Complete the Look */
.complete-look{padding:2rem 1rem;background:#f9fafb;margin:2rem -1rem;border-radius:12px}
.look-header{display:flex;justify-content:space-between;align-items:center;cursor:pointer;user-select:none;padding:1rem;transition:all 0.2s;border-radius:12px}
.look-header:hover{background:rgba(255,255,255,0.5)}
.look-header h2{font-size:1.75rem;font-weight:800;margin:0;text-align:center}
.look-header i{font-size:1.5rem;transition:transform 0.3s;color:#666;flex-shrink:0}
.look-subtitle{text-align:center;color:#666;margin:0.5rem 0 0 0;font-size:0.9375rem}
#completeLookContent{padding-top:1.5rem}
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

/* Similar products - используют единый премиум стиль аккордеонов */
#similarContent{padding-top:1rem}
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
.brand-logo{width:48px;height:48px}
.brand-name{font-size:1rem}
.brand-count{font-size:0.8125rem}
.product-details h1{font-size:2rem}
.products{grid-template-columns:repeat(4,1fr);gap:1.5rem}
/* complete-look адаптив удалён - используется единый стиль */
}

@media (min-width:1024px){
.product-layout{grid-template-columns:1fr 1fr}
}

@media (min-width:1440px){
.product-layout{grid-template-columns:1fr 1fr}
}

/* Reviews Enhanced - контент внутри */
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

/* Community Q&A - контент внутри */
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
.proof-badge{display:inline-flex;align-items:center;gap:0.5rem;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;padding:0.5rem 1rem;border-radius:8px;font-weight:800;font-size:0.875rem;margin-bottom:1.5rem;box-shadow:0 4px 12px rgba(139,92,246,0.4);border:1px solid rgba(255,255,255,0.2);letter-spacing:0.5px;text-transform:uppercase}
.proof-badge i{font-size:1.125rem;animation:proof-icon-pulse 2s ease-in-out infinite}
@keyframes proof-icon-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1);filter:brightness(1.2)}}
.proof-stats{display:grid;grid-template-columns:1fr;gap:1rem}
.proof-stat{display:flex;align-items:center;gap:0.75rem;padding:1rem;background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.proof-stat i{font-size:1.5rem;color:#fbbf24}
.proof-stat:nth-child(2) i{color:#10b981}
.proof-stat:nth-child(3) i{color:#ef4444}
.proof-stat span{font-size:0.9375rem;color:#666}
.proof-stat strong{color:#000;font-weight:700}

/* Улучшенная Sticky Purchase Bar с dropdown размера */
.sticky-purchase-bar{position:fixed;bottom:0;left:0;right:0;width:100%;background:#fff;box-shadow:0 -4px 20px rgba(0,0,0,0.15);padding:1rem 1.5rem;display:flex;align-items:center;gap:1rem;z-index:9999!important;transform:translateY(100%);opacity:0;transition:transform 0.3s ease-in-out,opacity 0.3s ease-in-out;border-top:1px solid #e5e7eb;min-height:72px}
.sticky-purchase-bar.visible{transform:translateY(0);opacity:1}
.sticky-product-info{display:flex;align-items:center;gap:0.75rem;flex:1;min-width:0}
.sticky-thumb{width:52px;height:52px;object-fit:cover;border-radius:10px;background:#f9fafb;flex-shrink:0}
.sticky-details{flex:1;min-width:0}
.sticky-name{font-size:0.875rem;font-weight:600;color:#000;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sticky-price{font-size:1.125rem;font-weight:800;color:#000;margin-top:2px}

/* Sticky Size Selector */
.sticky-size-selector{position:relative;flex-shrink:0}
.sticky-size-btn{background:#f3f4f6;border:2px solid #e5e7eb;padding:0.75rem 1rem;border-radius:10px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s;min-width:120px;justify-content:space-between}
.sticky-size-btn:hover{background:#e5e7eb;border-color:#000}
.sticky-size-btn i{font-size:0.875rem;transition:transform 0.2s}
.sticky-size-btn.active i{transform:rotate(180deg)}
.sticky-size-dropdown{position:absolute;bottom:calc(100% + 8px);left:0;background:#fff;border:2px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:200px;max-height:300px;overflow-y:auto;display:none;z-index:10}
.sticky-size-dropdown.show{display:block;animation:slideUp 0.2s}
@keyframes slideUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.sticky-size-option{display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1rem;cursor:pointer;transition:all 0.2s;border-bottom:1px solid #f3f4f6}
.sticky-size-option:last-child{border-bottom:none}
.sticky-size-option:hover{background:#f9fafb}
.sticky-size-option.selected{background:#f0f3ff;border-left:3px solid #3b82f6}
.sticky-size-option .size{font-weight:600;font-size:0.9375rem;pointer-events:none}
.sticky-size-option .price{color:#666;font-size:0.875rem;pointer-events:none}

.sticky-add-cart{background:#000;color:#fff;border:none;padding:0.875rem 1.5rem;border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:0.5rem;transition:all 0.2s;white-space:nowrap;flex-shrink:0;z-index:9998}
.sticky-add-cart:hover{background:#333;transform:translateY(-2px)}
.sticky-add-cart:active{transform:translateY(0)}
.sticky-add-cart i{font-size:1.125rem}

/* Кнопки действий (В корзину / Купить в 1 клик) */
.action-buttons{display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.5rem}
.btn-order{width:100%;padding:1rem 1.5rem;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.625rem;transition:all 0.2s;position:relative;overflow:hidden}
.btn-order i{font-size:1.25rem;transition:transform 0.2s}
.btn-order:hover i{transform:scale(1.1)}
.btn-order.primary{background:#000;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.15)}
.btn-order.primary:hover{background:#333;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.2)}
.btn-order.secondary{background:#f3f4f6;color:#000;border:2px solid #e5e7eb}
.btn-order.secondary:hover{background:#fff;border-color:#000;transform:translateY(-2px)}
.btn-order:active{transform:translateY(0)}

/* Telegram поддержка */
.telegram-support{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:linear-gradient(135deg,#f9fafb,#fff);border:2px solid #e5e7eb;border-radius:12px;text-decoration:none;color:#000;transition:all 0.3s;margin-bottom:1.5rem}
.telegram-support:hover{background:linear-gradient(135deg,#e3f2fd,#fff);border-color:#0088cc;transform:scale(1.02);box-shadow:0 6px 20px rgba(0,136,204,0.25)}
.telegram-support i:first-child{font-size:1.5rem;color:#0088cc;flex-shrink:0;transition:transform 0.3s}
.telegram-support:hover i:first-child{transform:rotate(360deg) scale(1.2)}
.telegram-support span{flex:1;font-size:0.9375rem;font-weight:600;margin:0 0.75rem}
.telegram-support i:last-child{font-size:1.125rem;color:#666;transition:transform 0.3s}
.telegram-support:hover i:last-child{transform:translateX(6px) scale(1.2)}

/* Trust Seals (компактные) */
.trust-seals.compact{display:flex;gap:0.75rem;padding:1rem;background:#f9fafb;border-radius:10px;margin-bottom:1.5rem}
.trust-seals.compact .seal{display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#666;flex:1}
.trust-seals.compact .seal i{font-size:1rem;color:#10b981}
.trust-seals.compact .seal:nth-child(2) i{color:#3b82f6}
.trust-seals.compact .seal:nth-child(3) i{color:#fbbf24}

/* Модальное окно "Купить в 1 клик" */
.quick-order-modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.75);z-index:2000;display:flex;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(8px);animation:fadeIn 0.3s}
.quick-order-content{background:#fff;border-radius:20px;max-width:500px;width:100%;max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideUpModal 0.3s}
@keyframes slideUpModal{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
.quick-order-content .modal-close{position:absolute;top:1rem;right:1rem;width:40px;height:40px;border-radius:50%;background:#f3f4f6;border:none;font-size:1.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:10}
.quick-order-content .modal-close:hover{background:#e5e7eb;transform:rotate(90deg)}
.quick-order-content .modal-header{text-align:center;padding:2rem 2rem 1rem;border-bottom:1px solid #f3f4f6}
.modal-icon{width:64px;height:64px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;box-shadow:0 8px 24px rgba(251,191,36,0.3)}
.modal-icon i{font-size:2rem;color:#fff}
.quick-order-content h2{font-size:1.75rem;font-weight:800;margin-bottom:0.5rem}
.modal-subtitle{color:#666;font-size:0.9375rem;margin:0}
.quick-order-content .modal-body{padding:2rem}
.quick-order-product{display:flex;align-items:center;gap:1rem;padding:1rem;background:#f9fafb;border-radius:12px;margin-bottom:1.5rem}
.quick-order-product img{width:64px;height:64px;object-fit:cover;border-radius:10px;background:#fff;flex-shrink:0}
.quick-order-product .product-info{flex:1;min-width:0}
.quick-order-product .brand{font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:0.25rem}
.quick-order-product .name{font-size:0.9375rem;font-weight:600;color:#000;margin-bottom:0.25rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.quick-order-product .price{font-size:1.125rem;font-weight:800;color:#000}
.quick-order-content .form-group{margin-bottom:1.25rem}
.quick-order-content .form-group label{display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;color:#333}
.quick-order-content .form-group label i{font-size:1rem;color:#666}
.quick-order-content .form-control{width:100%;padding:0.875rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:0.9375rem;transition:all 0.2s;font-family:inherit}
.quick-order-content .form-control:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.quick-order-content .form-control::placeholder{color:#9ca3af}
.quick-order-benefits{background:#ecfdf5;border:2px solid #d1fae5;border-radius:10px;padding:1rem;margin-bottom:1.5rem}
.quick-order-benefits .benefit{display:flex;align-items:start;gap:0.625rem;font-size:0.875rem;color:#059669;margin-bottom:0.5rem}
.quick-order-benefits .benefit:last-child{margin-bottom:0}
.quick-order-benefits .benefit i{font-size:1rem;margin-top:0.125rem;flex-shrink:0}
.quick-order-content .btn-submit{width:100%;padding:1rem 1.5rem;background:#000;color:#fff;border:none;border-radius:12px;font-size:1.125rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.625rem;transition:all 0.2s}
.quick-order-content .btn-submit:hover{background:#333;transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,0.2)}
.quick-order-content .btn-submit:active{transform:translateY(0)}
.quick-order-content .btn-submit i{font-size:1.25rem}
.quick-order-content .btn-submit:disabled{background:#d1d5db;cursor:not-allowed;transform:none}

@media (max-width:640px){
.action-buttons{grid-template-columns:1fr;gap:0.625rem}
.btn-order{padding:0.875rem 1.25rem;font-size:0.9375rem}
.telegram-support{padding:0.875rem 1rem}
.telegram-support span{font-size:0.875rem}
.trust-seals.compact{flex-direction:column;gap:0.5rem}
.quick-order-content{margin:0.5rem;max-height:95vh}
.quick-order-content .modal-header{padding:1.5rem 1.5rem 1rem}
.quick-order-content .modal-body{padding:1.5rem}
.quick-order-product{padding:0.875rem}
.quick-order-product img{width:56px;height:56px}
}

@media (min-width:768px){
.reviews-summary{grid-template-columns:200px 1fr}
.rating-large-block{text-align:left}
.rating-stars-big{justify-content:flex-start}
.proof-stats{grid-template-columns:repeat(3,1fr)}
}

@media (max-width:767px){
.look-grid{grid-template-columns:1fr;gap:1.5rem}
.look-total{flex-direction:column}
.btn-add-all{width:100%;justify-content:center}
.look-header{flex-direction:column;align-items:flex-start;gap:0.75rem}
.look-header i{align-self:flex-end}
.look-subtitle{font-size:0.875rem}
.size-stats-grid{grid-template-columns:1fr;gap:0.75rem}
.stat-item{padding:0.75rem 0.5rem}
.size-finder-options{grid-template-columns:repeat(4,1fr)}
.color-variations{gap:0.5rem}
.color-variation{padding:0.5rem 0.75rem;font-size:0.8125rem}
.size-compact{min-width:50px;padding:0.5rem 0.75rem}
.size-compact .size-value{font-size:0.9375rem}
.size-table{font-size:0.8125rem}
.size-table th,.size-table td{padding:0.625rem 0.375rem}
.size-table-modal-content{padding:1.5rem;max-width:95vw}
.reviews-header h2,.qa-header h2,.similar-header h2{font-size:1.25rem}
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

/* Size Table Modal */
.size-table-modal{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.75);z-index:3000;display:flex;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(8px);animation:fadeIn 0.3s}
.size-table-modal-content{background:#fff;border-radius:16px;max-width:900px;width:100%;max-height:90vh;overflow-y:auto;padding:2rem;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideUp 0.3s}
.size-table-modal-close{position:absolute;top:1rem;right:1rem;width:40px;height:40px;border-radius:50%;background:#f3f4f6;border:none;font-size:1.5rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:10}
.size-table-modal-close:hover{background:#e5e7eb;transform:rotate(90deg)}
.size-table-modal h2{font-size:1.5rem;font-weight:800;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem}
.table-responsive{overflow-x:auto;-webkit-overflow-scrolling:touch}
.size-table{width:100%;border-collapse:collapse;margin:1rem 0;min-width:600px}
.size-table thead{background:#f9fafb}
.size-table th{padding:0.875rem;text-align:center;font-size:0.8125rem;font-weight:700;color:#666;border-bottom:2px solid #e5e7eb;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap}
.size-table td{padding:1rem;text-align:center;font-size:0.9375rem;border-bottom:1px solid #f3f4f6;white-space:nowrap}
.size-table tbody tr{transition:all 0.2s}
.size-table tbody tr:hover{background:#f9fafb}
.size-table tbody tr.out-of-stock{opacity:0.5}
.size-table tbody tr.out-of-stock td{text-decoration:line-through}
.size-table-hint{margin-top:1rem;padding:1rem;background:#fff8e6;border-radius:8px;display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#666;line-height:1.5;border:1px solid #fbbf24}
.size-table-hint i{color:#fbbf24;font-size:1rem;flex-shrink:0}

/* СТАРЫЕ стили карусели похожих товаров - удалены, используется новый адаптивный блок */

</style>

<!-- jQuery УДАЛЕН - используем vanilla JS -->
<script>
// КРИТИЧНО: Определяем все глобальные функции ДО загрузки cart.js

// Уведомления
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Проверка наличия товара в корзине
function checkProductInCart(productId) {
    fetch(`/cart/has-product?productId=${productId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.inCart) {
            showProductInCartIndicator();
        }
    })
    .catch(error => {
        console.log('Ошибка проверки товара в корзине:', error);
    });
}

// Показать индикатор "Товар в корзине"
function showProductInCartIndicator() {
    const indicator = document.getElementById('productInCartIndicator');
    if (indicator) {
        indicator.style.display = 'block';
        
        // При клике на индикатор - переход в корзину
        indicator.addEventListener('click', function() {
            window.location.href = '/cart';
        });
    }
}

// Скрыть индикатор
function hideProductInCartIndicator() {
    const indicator = document.getElementById('productInCartIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// Инициализация при загрузке
// Глобальные функции инициализированы

// Проверяем товар в корзине при загрузке страницы
const productId = <?= $product->id ?>;
checkProductInCart(productId);
</script>
<?php
$this->registerJsFile('@web/js/cart.js', ['position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/favorites.js', ['position' => \yii\web\View::POS_END]);
?>
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

// ИСПРАВЛЕНО (Проблема #15): Унифицированный wrapper
function toggleFav(e, id) {
    e.stopPropagation();
    e.preventDefault();
    // Вызываем глобальную функцию с правильными параметрами
    if (typeof window.toggleFavorite === 'function') {
        window.toggleFavorite(e, id);
    }
}

function toggleZoom(){
    document.getElementById('mainImgContainer').classList.toggle('zoomed');
}

// Обновление цены при выборе размера
document.addEventListener('DOMContentLoaded', function() {
    const sizeInputs = document.querySelectorAll('input[name="size"]');
    const priceElement = document.getElementById('productPrice');
    const sizeLinkElement = document.getElementById('selectedSizeLink');
    const sizeValueElement = document.getElementById('selectedSizeValue');
    
    if (sizeInputs.length > 0 && priceElement) {
        const hasRange = priceElement.dataset.hasRange === 'true';
        const minPrice = parseFloat(priceElement.dataset.minPrice);
        const maxPrice = parseFloat(priceElement.dataset.maxPrice);
        
        sizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.checked) {
                    const newPrice = parseFloat(this.dataset.price);
                    const selectedSize = this.value;
                    
                    if (newPrice && newPrice > 0) {
                        // Форматируем цену
                        const formatter = new Intl.NumberFormat('ru-BY', {
                            style: 'currency',
                            currency: 'BYN',
                            minimumFractionDigits: 2
                        });
                        
                        // Показываем конкретную цену выбранного размера
                        priceElement.textContent = formatter.format(newPrice);
                        
                        // Добавляем плавную анимацию
                        priceElement.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            priceElement.style.transform = 'scale(1)';
                        }, 200);
                    }
                    
                    // Показываем ссылку на каталог с выбранным размером
                    if (sizeLinkElement && sizeValueElement && selectedSize) {
                        sizeValueElement.textContent = selectedSize;
                        sizeLinkElement.style.display = 'flex';
                        sizeLinkElement.onclick = function() {
                            window.location.href = '/catalog?size=' + encodeURIComponent(selectedSize);
                        };
                    }
                }
            });
        });
        
        // Если убрали выбор размера - возвращаем диапазон и скрываем ссылку
        if (hasRange) {
            // Следим за сбросом выбора
            document.addEventListener('click', function(e) {
                // Если кликнули на уже выбранный размер - сбрасываем
                if (e.target.matches('input[name="size"]:checked')) {
                    setTimeout(() => {
                        const anyChecked = document.querySelector('input[name="size"]:checked');
                        if (!anyChecked) {
                            // Возвращаем диапазон цен
                            const formatter = new Intl.NumberFormat('ru-BY', {
                                style: 'currency',
                                currency: 'BYN',
                                minimumFractionDigits: 2
                            });
                            priceElement.innerHTML = formatter.format(minPrice) + 
                                '<span class="price-separator"> - </span>' + 
                                formatter.format(maxPrice);
                            
                            // Скрываем ссылку на каталог
                            if (sizeLinkElement) {
                                sizeLinkElement.style.display = 'none';
                            }
                        }
                    }, 10);
                }
            });
        }
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
        // Показываем индикатор после добавления
        setTimeout(() => showProductInCartIndicator(), 500);
    } else {
        alert('Товар добавлен в корзину');
        setTimeout(() => showProductInCartIndicator(), 500);
    }
}

// Функции для работы со sticky bar
function toggleStickySizeDropdown() {
    const dropdown = document.getElementById('stickySizeDropdown');
    const btn = document.getElementById('stickySizeBtn');
    
    if (dropdown && btn) {
        dropdown.classList.toggle('show');
        btn.classList.toggle('active');
    }
}

// Инициализация обработчиков размеров после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Инициализация обработчиков стики-панели');
    
    // Event Delegation на родительский dropdown
    const dropdown = document.getElementById('stickySizeDropdown');
    
    if (dropdown) {
        console.log('🔧 Dropdown найден, добавляем event delegation');
        
        dropdown.addEventListener('click', function(e) {
            console.log('🔵 Клик в dropdown, target:', e.target);
            
            // Находим ближайший .sticky-size-option
            const sizeOption = e.target.closest('.sticky-size-option');
            
            if (!sizeOption) {
                console.log('⚠️ Клик не на опцию размера');
                return;
            }
            
            console.log('🔵 Клик на размер! element:', sizeOption);
            
            const size = sizeOption.dataset.size;
            const price = sizeOption.dataset.price;
            
            console.log('🔵 Извлечены данные - size:', size, 'price:', price);
            
            if (!size) {
                console.error('❌ size пустой!');
                return;
            }
            
            // Обновляем текст кнопки
            const label = document.getElementById('stickySizeLabel');
            if (label) {
                label.textContent = size;
                // Обновлен label
            }
            
            // Обновляем цену в sticky bar
            const stickyPrice = document.querySelector('#stickyBar .sticky-price');
            if (stickyPrice && price) {
                const formatter = new Intl.NumberFormat('ru-BY', {
                    style: 'currency',
                    currency: 'BYN',
                    minimumFractionDigits: 2
                });
                stickyPrice.textContent = formatter.format(price);
                
                // Анимация изменения цены
                stickyPrice.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    stickyPrice.style.transform = 'scale(1)';
                }, 200);
            }
            
            // Выделяем выбранный размер
            const allOptions = document.querySelectorAll('.sticky-size-option');
            allOptions.forEach(opt => opt.classList.remove('selected'));
            sizeOption.classList.add('selected');
            
            // Сохраняем выбранный размер
            window.selectedStickySize = size;
            // Размер сохранён
            
            // Закрываем dropdown
            toggleStickySizeDropdown();
        });
        
        // Проверяем сколько опций есть
        const options = dropdown.querySelectorAll('.sticky-size-option');
        console.log('🔧 Найдено опций размеров:', options.length);
        options.forEach((opt, idx) => {
            console.log(`  Опция ${idx}: size=${opt.dataset.size}, price=${opt.dataset.price}`);
        });
    } else {
        console.error('❌ Dropdown не найден!');
    }
});

// Добавление в корзину из sticky панели
function addToCartFromSticky() {
    console.log('🟢 addToCartFromSticky вызвана');
    console.log('🟢 window.selectedStickySize:', window.selectedStickySize);
    
    const size = window.selectedStickySize;
    
    if (!size) {
        console.warn('⚠️ Размер не выбран');
        showNotification('Пожалуйста, выберите размер', 'warning');
        // Открываем dropdown размеров
        const dropdown = document.getElementById('stickySizeDropdown');
        const btn = document.getElementById('stickySizeBtn');
        if (dropdown && !dropdown.classList.contains('show')) {
            toggleStickySizeDropdown();
        }
        return;
    }
    
    const productId = <?= $product->id ?>;
    console.log('🟢 productId:', productId, 'size:', size);
    console.log('🟢 typeof addToCart:', typeof addToCart);
    console.log('🟢 typeof showNotification:', typeof showNotification);
    
    // Добавляем товар в корзину через функцию из cart.js
    if (typeof addToCart === 'function') {
        // Вызываем addToCart
        addToCart(productId, 1, size, null);
        // Показываем индикатор после успешного добавления
        setTimeout(() => showProductInCartIndicator(), 500);
    } else {
        // Fallback - используем FormData как в cart.js
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('size', size);
        
        // Получаем CSRF токен
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        formData.append('_csrf', csrfToken);
        
        fetch('/cart/add', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('✓ Товар добавлен в корзину', 'success');
                // Показываем индикатор
                setTimeout(() => showProductInCartIndicator(), 500);
                // Обновляем счетчик корзины
                if (typeof updateCartCount === 'function') {
                    updateCartCount(data.count);
                } else {
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) {
                        cartCount.textContent = data.count;
                        cartCount.style.display = data.count > 0 ? 'flex' : 'none';
                    }
                }
            } else {
                showNotification(data.message || 'Ошибка добавления в корзину', 'error');
            }
        })
        .catch(error => {
            showNotification('Ошибка соединения', 'error');
        });
    }
}

// Закрытие dropdown при клике вне его
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('stickySizeDropdown');
    const btn = document.getElementById('stickySizeBtn');
    
    if (dropdown && btn && dropdown.classList.contains('show')) {
        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            toggleStickySizeDropdown();
        }
    }
});

// Старый обработчик sticky bar удалён - используется улучшенная версия в DOMContentLoaded

// Live viewers count - УДАЛЕНО (было fake)

// Review filters - будет активировано при подключении реальных отзывов

// Accordion для описания
function toggleDescription() {
    const content = document.getElementById('descContent');
    const icon = document.getElementById('descToggleIcon');
    const header = icon.closest('.desc-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// Accordion для характеристик
function toggleMainSpecs() {
    const content = document.getElementById('mainSpecsContent');
    const icon = document.getElementById('mainSpecsToggleIcon');
    const header = icon.closest('.specs-header-toggle');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// ДОБАВЛЕНО: Accordion для рекомендации размера
function toggleSizeRec() {
    const content = document.getElementById('sizeRecContent');
    const icon = document.getElementById('sizeRecToggleIcon');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Accordion для Complete the Look
function toggleCompleteLook() {
    const content = document.getElementById('completeLookContent');
    const icon = document.getElementById('completeLookToggleIcon');
    const header = icon.closest('.look-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// Accordion для Model Variants
function toggleVariants() {
    const content = document.getElementById('variantsContent');
    const icon = document.getElementById('variantsToggleIcon');
    const header = icon.closest('.variants-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// Accordion для отзывов
function toggleReviews() {
    const content = document.getElementById('reviewsContent');
    const icon = document.getElementById('reviewsToggleIcon');
    const header = icon.closest('.reviews-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// Accordion для карусели похожих товаров (СТАРЫЙ - оставлен для совместимости)
function toggleRelatedCarousel() {
    const content = document.getElementById('relatedCarouselContent');
    const icon = document.getElementById('relatedCarouselToggleIcon');
    const header = icon.closest('.carousel-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('active');
    } else {
        content.style.display = 'none';
        header.classList.remove('active');
    }
}

// Карусель - прокрутка (СТАРЫЙ - оставлен для совместимости)
function scrollCarousel(direction) {
    const wrapper = document.querySelector('.carousel-wrapper');
    const track = document.getElementById('carouselTrack');
    if (!wrapper || !track) return;
    
    const items = track.querySelectorAll('.carousel-item');
    if (items.length === 0) return;
    
    const scrollAmount = 200;
    
    if (direction === -1) {
        wrapper.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

// НОВЫЙ Accordion для блока похожих товаров
function toggleRelatedProducts() {
    const content = document.getElementById('relatedContent');
    const icon = document.getElementById('relatedToggleIcon');
    const header = icon.closest('.related-header');
    
    if (!content || !icon || !header) return;
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('active');
    } else {
        content.style.display = 'none';
        header.classList.remove('active');
    }
}

// НОВАЯ функция прокрутки карусели похожих товаров
function scrollRelatedCarousel(direction) {
    const wrapper = document.getElementById('relatedCarouselWrapper');
    if (!wrapper) return;
    
    // Получаем ширину одной карточки + gap
    const card = wrapper.querySelector('.related-product-card');
    if (!card) return;
    
    const cardWidth = card.offsetWidth;
    const gap = 16; // 1rem в пикселях (примерно)
    const scrollAmount = (cardWidth + gap) * 2; // Прокручиваем по 2 карточки
    
    if (direction === -1) {
        wrapper.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

// Accordion для Q&A
function toggleQA() {
    const content = document.getElementById('qaContent');
    const icon = document.getElementById('qaToggleIcon');
    const header = icon.closest('.qa-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'flex';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// Accordion для похожих товаров
function toggleSimilar() {
    const content = document.getElementById('similarContent');
    const icon = document.getElementById('similarToggleIcon');
    const header = icon.closest('.similar-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'grid';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
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
        alert('Все товары из образа добавлены в корзину!');
    } else {
        alert('Функция корзины не найдена');
    }
    <?php else: ?>
    alert('Нет похожих товаров');
    <?php endif; ?>
}

// Color Selection
function selectColor(button) {
    // Remove active class from all color buttons
    document.querySelectorAll('.color-variation').forEach(btn => btn.classList.remove('active'));
    // Add active class to selected button
    button.classList.add('active');
    // Update selected color name
    const colorName = button.dataset.colorName;
    document.getElementById('selectedColorName').textContent = colorName;
}

// Gallery Thumbnails Navigation
function switchToSlide(index) {
    const slides = document.querySelectorAll('.swipe-slide');
    const track = document.querySelector('.swipe-track');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const dots = document.querySelectorAll('.swipe-dot');
    
    if (!slides.length || !track) return;
    
    // Обновляем активный слайд
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
    
    // Скроллим к нужному слайду
    track.style.transform = `translateX(-${index * 100}%)`;
    
    // Обновляем активную миниатюру
    thumbnails.forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
    
    // Обновляем точки пагинации
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
    
    // Скроллим миниатюры, чтобы активная была видна
    const activeThumb = document.querySelector('.thumbnail-item.active');
    if (activeThumb) {
        activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
}

function scrollThumbnails(direction) {
    const wrapper = document.querySelector('.thumbnails-wrapper');
    if (!wrapper) return;
    
    const scrollAmount = 120; // ширина одной миниатюры + gap
    const currentScroll = wrapper.scrollLeft;
    
    if (direction === 'prev') {
        wrapper.scrollTo({ left: currentScroll - scrollAmount, behavior: 'smooth' });
    } else {
        wrapper.scrollTo({ left: currentScroll + scrollAmount, behavior: 'smooth' });
    }
}

// Touch swipe для галереи (улучшенная версия)
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.swipe-track');
    const slides = document.querySelectorAll('.swipe-slide');
    
    if (!track || !slides.length) return;
    
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    let currentIndex = 0;
    
    track.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        isDragging = true;
    }, { passive: true });
    
    track.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
    }, { passive: true });
    
    track.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;
        
        const diff = startX - currentX;
        const threshold = 50;
        
        if (Math.abs(diff) > threshold) {
            if (diff > 0 && currentIndex < slides.length - 1) {
                currentIndex++;
            } else if (diff < 0 && currentIndex > 0) {
                currentIndex--;
            }
            switchToSlide(currentIndex);
        }
    });
    
    // Mouse drag для десктопа
    track.addEventListener('mousedown', (e) => {
        startX = e.clientX;
        isDragging = true;
        track.style.cursor = 'grabbing';
    });
    
    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        currentX = e.clientX;
    });
    
    document.addEventListener('mouseup', () => {
        if (!isDragging) return;
        isDragging = false;
        track.style.cursor = 'grab';
        
        const diff = startX - currentX;
        const threshold = 50;
        
        if (Math.abs(diff) > threshold) {
            if (diff > 0 && currentIndex < slides.length - 1) {
                currentIndex++;
            } else if (diff < 0 && currentIndex > 0) {
                currentIndex--;
            }
            switchToSlide(currentIndex);
        }
    });
});

// Size System Switcher
let currentSizeSystem = 'eu';
function switchSizeSystem(system) {
    currentSizeSystem = system;
    
    // Update active tab
    document.querySelectorAll('.size-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.system === system);
    });
    
    // Update size values in compact labels
    document.querySelectorAll('.size-compact').forEach(sizeLabel => {
        const valueSpan = sizeLabel.querySelector('.size-value');
        let value = '';
        
        switch(system) {
            case 'eu':
                value = sizeLabel.dataset.eu;
                break;
            case 'us':
                value = sizeLabel.dataset.us || sizeLabel.dataset.eu;
                break;
            case 'uk':
                value = sizeLabel.dataset.uk || sizeLabel.dataset.eu;
                break;
            case 'cm':
                value = sizeLabel.dataset.cm || sizeLabel.dataset.eu;
                break;
        }
        
        if (valueSpan && value) {
            valueSpan.textContent = value;
        }
    });
}

// Open Size Table Modal
function openSizeTableModal() {
    <?php 
    // Группируем размеры по EU размеру
    $sizesGrouped = [];
    foreach ($product->availableSizes as $size): 
        $euSize = $size->eu_size ?: $size->size;
        if (!isset($sizesGrouped[$euSize])) {
            $sizesGrouped[$euSize] = $size;
        }
    endforeach;
    ?>
    
    const modal = document.createElement('div');
    modal.id = 'sizeTableModalElement';
    modal.className = 'size-table-modal';
    modal.innerHTML = `
        <div class="size-table-modal-content">
            <button class="size-table-modal-close" onclick="closeSizeTableModal()">
                <i class="bi bi-x"></i>
            </button>
            <h2><i class="bi bi-table"></i> Таблица размеров</h2>
            <div class="table-responsive">
                <table class="size-table">
                    <thead>
                        <tr>
                            <th>EU</th>
                            <th>US</th>
                            <th>UK</th>
                            <th>CM</th>
                            <th>Наличие</th>
                            <th>Цена</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sizesGrouped as $size): 
                            $priceByn = $size->getPriceByn();
                            $inStock = $size->inStock();
                        ?>
                        <tr class="size-row <?= $inStock ? 'available' : 'out-of-stock' ?>" 
                            onclick="selectSizeFromTable('<?= Html::encode($size->size) ?>', <?= $inStock ? 'true' : 'false' ?>)"
                            style="cursor: <?= $inStock ? 'pointer' : 'not-allowed' ?>">
                            <td><strong><?= Html::encode($size->eu_size ?: $size->size) ?></strong></td>
                            <td><?= Html::encode($size->us_size ?: '—') ?></td>
                            <td><?= Html::encode($size->uk_size ?: '—') ?></td>
                            <td><?= Html::encode($size->cm_size ? $size->cm_size . ' см' : '—') ?></td>
                            <td>
                                <?php if ($inStock): ?>
                                    <span class="stock-badge in-stock">✓ В наличии</span>
                                <?php else: ?>
                                    <span class="stock-badge out-stock">✗ Нет</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $priceByn ? Yii::$app->formatter->asCurrency($priceByn, 'BYN') : '—' ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="size-table-hint">
                <i class="bi bi-info-circle"></i>
                <small>💡 Кликните на строку, чтобы выбрать размер. Измерьте длину стопы в см и сравните с таблицей</small>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Close on background click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeSizeTableModal();
        }
    });
}

function closeSizeTableModal() {
    const modal = document.getElementById('sizeTableModalElement');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

// Select Size From Table
function selectSizeFromTable(sizeValue, inStock) {
    if (!inStock) return;
    
    // Find and check the radio button with this size
    const sizeInput = document.querySelector(`input[name="size"][value="${sizeValue}"]`);
    if (sizeInput && !sizeInput.disabled) {
        sizeInput.checked = true;
        
        // Trigger change event to update price
        const event = new Event('change', { bubbles: true });
        sizeInput.dispatchEvent(event);
        
        // Close modal
        closeSizeTableModal();
        
        // Scroll to quick select
        const quickSelect = document.querySelector('.sizes-quick-select');
        if (quickSelect) {
            quickSelect.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        // Visual feedback
        const parent = sizeInput.closest('.size-compact');
        if (parent) {
            parent.style.animation = 'pulse 0.5s ease';
            setTimeout(() => {
                parent.style.animation = '';
            }, 500);
        }
    }
}

// Add pulse animation
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
`;
document.head.appendChild(style);

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSizeTableModal();
    }
});

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

// showNotification уже определена выше до загрузки cart.js

// Back button event listener
document.addEventListener('DOMContentLoaded', function() {
    const backBtn = document.getElementById('backBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            history.back();
        });
    }
    
    // Sticky panel — правильная логика с оптимальным порогом
    const stickyBar = document.getElementById('stickyBar');
    const mainBtn = document.querySelector('.btn-order');
    
    if (stickyBar && mainBtn) {
        // ПРИНУДИТЕЛЬНО устанавливаем критичные стили inline
        stickyBar.style.position = 'fixed';
        stickyBar.style.bottom = '0';
        stickyBar.style.left = '0';
        stickyBar.style.right = '0';
        stickyBar.style.zIndex = '9999';
        stickyBar.style.width = '100%';
        stickyBar.style.display = 'flex';
        stickyBar.style.background = '#ffffff';
        stickyBar.style.padding = '1rem 1.5rem';
        stickyBar.style.boxShadow = '0 -4px 20px rgba(0,0,0,0.15)';
        stickyBar.style.alignItems = 'center';
        stickyBar.style.gap = '1rem';
        stickyBar.style.minHeight = '72px';
        stickyBar.style.borderTop = '1px solid #e5e7eb';
        stickyBar.style.transition = 'transform 0.3s ease-in-out, opacity 0.3s ease-in-out';
        // Изначально скрыта
        stickyBar.style.transform = 'translateY(100%)';
        stickyBar.style.opacity = '0';
        
        const SCROLL_THRESHOLD = 200; // Порог 200px для более раннего появления

        const updateStickyVisibility = () => {
            const offset = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            const mainBtnRect = mainBtn.getBoundingClientRect();
            
            // Показываем sticky bar когда основная кнопка уходит за верх экрана
            // ИЛИ когда прокрутили больше порога
            if (mainBtnRect.top < 0 || offset > SCROLL_THRESHOLD) {
                if (!stickyBar.classList.contains('visible')) {
                    stickyBar.classList.add('visible');
                    stickyBar.style.transform = 'translateY(0)';
                    stickyBar.style.opacity = '1';
                }
            } else {
                if (stickyBar.classList.contains('visible')) {
                    stickyBar.classList.remove('visible');
                    stickyBar.style.transform = 'translateY(100%)';
                    stickyBar.style.opacity = '0';
                }
            }
        };

        // Находим ВСЕ потенциально скроллируемые элементы и добавляем обработчики
        const scrollableElements = [
            window,
            document,
            document.documentElement,
            document.body,
            document.querySelector('.product-page-optimized'),
            document.querySelector('main'),
            document.querySelector('#content')
        ].filter(el => el !== null);

        scrollableElements.forEach(element => {
            element.addEventListener('scroll', updateStickyVisibility, { passive: true });
        });
        
        // Запасной вариант: проверяем позицию кнопки каждые 200ms
        setInterval(() => {
            const mainBtnRect = mainBtn.getBoundingClientRect();
            const offset = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            
            if (mainBtnRect.top < 0 || offset > SCROLL_THRESHOLD) {
                if (!stickyBar.classList.contains('visible')) {
                    stickyBar.classList.add('visible');
                    stickyBar.style.transform = 'translateY(0)';
                    stickyBar.style.opacity = '1';
                }
            } else {
                if (stickyBar.classList.contains('visible')) {
                    stickyBar.classList.remove('visible');
                    stickyBar.style.transform = 'translateY(100%)';
                    stickyBar.style.opacity = '0';
                }
            }
        }, 200);

        // Проверяем сразу при загрузке
        updateStickyVisibility();
    }
});

// Финальная проверка доступности всех функций
window.addEventListener('load', function() {
    console.log('📊 Проверка доступности функций:');
    console.log('  - showNotification:', typeof showNotification);
    console.log('  - addToCart:', typeof addToCart);
    console.log('  - addToCartFromSticky:', typeof addToCartFromSticky);
    console.log('  - toggleStickySizeDropdown:', typeof toggleStickySizeDropdown);
    console.log('  - window.selectedStickySize:', window.selectedStickySize);
    
    // Проверяем наличие элементов
    const stickyBar = document.getElementById('stickyBar');
    const stickyDropdown = document.getElementById('stickySizeDropdown');
    const stickyBtn = document.getElementById('stickySizeBtn');
    const addBtn = document.querySelector('.sticky-add-cart');
    
    console.log('📊 Проверка элементов DOM:');
    console.log('  - stickyBar:', !!stickyBar);
    console.log('  - stickySizeDropdown:', !!stickyDropdown);
    console.log('  - stickySizeBtn:', !!stickyBtn);
    console.log('  - sticky-add-cart button:', !!addBtn);
    
    if (stickyDropdown) {
        const options = stickyDropdown.querySelectorAll('.sticky-size-option');
        console.log('  - Количество опций размеров:', options.length);
        if (options.length > 0) {
            console.log('  - Первая опция data-size:', options[0].dataset.size);
            console.log('  - Первая опция data-price:', options[0].dataset.price);
            
            // Проверяем обработчики
            const hasListeners = options[0].onclick !== null || 
                                (options[0]._listeners && options[0]._listeners.click);
            console.log('  - У первой опции есть обработчик клика:', hasListeners);
        }
    }
    
    console.log('✅ Все проверки завершены');
});
</script>

<style>
/* Индикатор "Товар в корзине" */
.product-in-cart-indicator {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9998;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
    padding: 0.875rem 1.25rem;
    animation: slideInRight 0.4s ease-out, pulse 2.5s ease-in-out infinite;
    cursor: pointer;
    transition: all 0.3s;
    min-width: 140px;
}

.product-in-cart-indicator:hover {
    transform: translateY(-3px) scale(1.08);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.6);
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.indicator-content {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.9375rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.indicator-content i {
    font-size: 1.5rem;
    animation: bounce 1.5s ease-in-out infinite;
}

.indicator-hint {
    font-size: 0.6875rem;
    opacity: 0.85;
    text-align: center;
    font-weight: 500;
    letter-spacing: 0.3px;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
    }
    50% {
        box-shadow: 0 6px 30px rgba(16, 185, 129, 0.6);
    }
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

/* Адаптив для мобильных */
@media (max-width: 768px) {
    .product-in-cart-indicator {
        top: 65px;
        right: 10px;
        padding: 0.625rem 0.875rem;
        min-width: auto;
    }
    
    .indicator-content {
        font-size: 0.8125rem;
        margin-bottom: 0.125rem;
        gap: 0.5rem;
    }
    
    .indicator-content i {
        font-size: 1.25rem;
    }
    
    .indicator-text {
        font-size: 0.8125rem;
    }
    
    .indicator-hint {
        font-size: 0.625rem;
    }
}

@media (max-width: 480px) {
    .product-in-cart-indicator {
        top: 60px;
        right: 8px;
        padding: 0.5rem 0.75rem;
        border-radius: 12px;
    }
    
    .indicator-content {
        margin-bottom: 0;
    }
    
    .indicator-text {
        display: none;
    }
    
    .indicator-hint {
        display: none;
    }
    
    .indicator-content i {
        font-size: 1.5rem;
    }
}

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
.notification-warning {
    border-left: 4px solid #f59e0b;
}
.notification-info {
    border-left: 4px solid #3b82f6;
}

/* ============================================
   PREMIUM IMAGE GALLERY MODAL
   ============================================ */

.image-gallery-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.97);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    animation: fadeIn 0.3s ease;
    overflow: hidden;
}

.gallery-modal-content {
    width: 100%;
    height: 100%;
    max-width: 100vw;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.gallery-close {
    position: absolute;
    top: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    z-index: 10;
}

.gallery-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg) scale(1.1);
}

.gallery-scroll-container {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 6rem 1rem 6rem;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    height: 100%;
}

.gallery-image-item {
    width: 100%;
    max-width: 100%;
    min-height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    padding: 2rem;
    box-sizing: border-box;
}

.gallery-image-item:hover {
    /* hover отключен для мобильных */
}

.gallery-image-item img {
    max-width: 100%;
    max-height: 90vh;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 12px;
    box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
    background: #fff;
    padding: 1rem;
}

.gallery-counter {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    z-index: 10;
}

@media (max-width: 767px) {
    .gallery-close {
        top: 1rem;
        right: 1rem;
        width: 48px;
        height: 48px;
        font-size: 1.5rem;
        background: rgba(0, 0, 0, 0.7);
        border-color: rgba(255, 255, 255, 0.3);
    }
    
    .gallery-scroll-container {
        padding: 4rem 0.5rem 5rem;
    }
    
    .gallery-image-item {
        padding: 1rem 0.5rem;
        min-height: 100vh;
    }
    
    .gallery-image-item img {
        max-height: 85vh;
        padding: 0.5rem;
        border-radius: 8px;
    }
    
    .gallery-counter {
        bottom: 1.5rem;
        padding: 0.75rem 1.25rem;
        font-size: 0.9375rem;
        background: rgba(0, 0, 0, 0.85);
        font-weight: 700;
    }
}

@media (min-width: 768px) {
    .gallery-scroll-container {
        padding: 4rem 2rem 4rem;
    }
    
    .gallery-image-item {
        max-width: 1200px;
        margin: 0 auto;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>
