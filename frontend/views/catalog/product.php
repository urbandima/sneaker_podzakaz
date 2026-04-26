<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product|stdClass $product */
/** @var app\backend\modules\catalog\models\Product[]|stdClass[] $similarProducts */
/** @var bool $isFavorite */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use app\frontend\assets\ProductAsset;
use app\backend\shared\helpers\ProductCardHelper;
use app\backend\shared\helpers\ImageHelper;
use app\backend\shared\helpers\TextHelper;
use app\backend\shared\components\SchemaOrgGenerator;

// Регистрируем AssetBundle для страницы товара (все стили автоматически)
ProductAsset::register($this);

// Helper функции для поддержки как Product модели, так и stdClass для demo режима
function getProductProperty($product, $property, $default = null) {
    if (method_exists($product, $property)) {
        return $product->$property();
    }
    return $product->$property ?? $default;
}

function getProductMethod($product, $method, $default = null) {
    if (method_exists($product, $method)) {
        return $product->$method();
    }
    return $default;
}

$productTitle = getProductMethod($product, 'getDisplayTitle') ?? ($product->title ?? $product->name ?? 'Товар');
$productId = getProductMethod($product, 'getId') ?? ($product->id ?? 0);

$this->title = $productTitle . ' | СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'product-id', 'content' => $productId]);

// ============================================
// ОПТИМИЗАЦИЯ ЗАГРУЗКИ РЕСУРСОВ
// ============================================

// Инициализация размера по умолчанию
$defaultSizeField = ProductCardHelper::resolveSizeField(ProductCardHelper::DEFAULT_SIZE_SYSTEM);
$productPriceView = ProductCardHelper::calculatePriceView($product, null, [], $defaultSizeField);

$galleryPlaceholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="100%" height="100%" fill="#f3f4f6"/><text x="50%" y="50%" font-family="Arial" font-size="28" fill="#94a3b8" text-anchor="middle" dominant-baseline="middle">Фото в обработке</text></svg>';
$galleryPlaceholderDataUri = 'data:image/svg+xml;base64,' . base64_encode($galleryPlaceholderSvg);

$galleryImages = [];
$seenGalleryUrls = [];
if (!empty($product->images)) {
    foreach ($product->images as $img) {
        $url = $img->getUrl();
        if (!$url) {
            continue;
        }
        $webpUrl = ImageHelper::getWebpUrl($url);
        if (isset($seenGalleryUrls[$webpUrl])) {
            continue;
        }
        $seenGalleryUrls[$webpUrl] = true;
        $galleryImages[] = [
            'url' => $webpUrl,
            'alt' => $product->name . ' — фото ' . (count($galleryImages) + 1),
            'placeholder' => false,
        ];
    }
}

if (empty($galleryImages)) {
    $fallbackUrl = $product->getMainImageUrl();
    $galleryImages[] = [
        'url' => $fallbackUrl ? ImageHelper::getWebpUrl($fallbackUrl) : $galleryPlaceholderDataUri,
        'alt' => $product->name,
        'placeholder' => $fallbackUrl ? false : true,
    ];
}

$galleryImagesJson = Json::encode(array_column($galleryImages, 'url'), JSON_UNESCAPED_SLASHES);
$this->registerJsVar('productGalleryImages', array_column($galleryImages, 'url'));

// SEO параметры
$this->params['description'] = $product->description 
    ? Html::encode(mb_substr(strip_tags($product->description), 0, 160)) 
    : Html::encode($product->getDisplayTitle() . ' - купить оригинальные кроссовки в Минске. Цена: ' . Yii::$app->formatter->asCurrency($product->price, 'BYN'));
$this->params['keywords'] = implode(', ', array_filter([
    $product->brand?->name ?? ($product->brand_name ?? ''),
    $product->name,
    $product->category?->name ?? ($product->category_name ?? ''),
    'кроссовки',
    'обувь',
    'купить',
    'Минск',
    'Беларусь'
]));
$this->params['image'] = $product->getMainImageUrl();
$this->params['og:type'] = 'product';

// ============================================
// РАЗМЕРЫ И ЦЕНЫ
// ============================================

$availableSizes = ProductCardHelper::getAvailableSizes($product);
$selectedSize = $defaultSizeField;
$sizeLabelMap = ['eu_size' => 'EU', 'us_size' => 'US', 'uk_size' => 'UK', 'cm_size' => 'CM'];
$selectedSizeLabel = $sizeLabelMap[$selectedSize] ?? strtoupper(str_replace('_size', '', $selectedSize));

// Данные для sticky bar
$priceData = [
    'price' => $productPriceView['currentPrice'] ? number_format($productPriceView['currentPrice'], 0, '.', ' ') . ' BYN' : number_format($product->price, 0, '.', ' ') . ' BYN',
    'oldPrice' => $productPriceView['oldPrice'] ? number_format($productPriceView['oldPrice'], 0, '.', ' ') . ' BYN' : null,
    'discount' => $productPriceView['discountPercent'] ?? 0,
    'selectedSize' => $selectedSize,
    'availableSizes' => $availableSizes,
    'productId' => $productId,
    'productName' => $productTitle,
    'productUrl' => $product->getMainImageUrl() ?? '',
];

// Schema.org разметка
$schemaData = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $productTitle,
    'brand' => [
        '@type' => 'Brand',
        'name' => $product->brand?->name ?? ($product->brand_name ?? ''),
    ],
    'image' => array_column($galleryImages, 'url'),
    'description' => $this->params['description'],
    'offers' => [
        '@type' => 'Offer',
        'url' => Url::current(),
        'priceCurrency' => 'BYN',
        'price' => $productPriceView['currentPrice'] ?? $product->price,
        'availability' => 'https://schema.org/InStock',
    ],
];

$this->registerJs('window.productData = ' . Json::encode($priceData), \yii\web\View::POS_END);
$this->registerJs('window.schemaData = ' . Json::encode($schemaData), \yii\web\View::POS_END);

// Данные для JavaScript
$this->registerJsVar('productData', $priceData);
$this->registerJsVar('schemaData', $schemaData);

// Подключение модальных окон
$this->registerJsFile('/js/product-modals.js', [
    'depends' => [ProductAsset::class],
    'position' => \yii\web\View::POS_END,
]);

// Подключение лайтбокса
$this->registerCssFile('https://cdn.jsdelivr.net/npm/lightbox2@2.11.4/dist/css/lightbox.min.css');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/lightbox2@2.11.4/dist/js/lightbox-plus-jquery.min.js', [
    'position' => \yii\web\View::POS_END,
]);

// Инициализация Lightbox
$this->registerJs("
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': 'Фото %1 из %2'
    });
", \yii\web\View::POS_READY);

// Инициализация галереи — функции определены в product-page.js
// (initProductGallery/initSizeSelector/initStickyPurchaseBar запускаются автоматически)

// Инициализация избранного
$isFavorite = !empty($isFavorite) ? $isFavorite : false;
$this->registerJsVar('isFavorite', $isFavorite);

// Данные для похожих товаров
$similarProductsData = [];
if (!empty($similarProducts)) {
    foreach ($similarProducts as $similar) {
        $similarPrice = ProductCardHelper::calculatePriceView($similar, null, [], $defaultSizeField);
        $similarProductsData[] = [
            'id' => $similar->id,
            'name' => $similar->name,
            'price' => $similarPrice['currentPrice'] ? number_format($similarPrice['currentPrice'], 0, '.', ' ') . ' BYN' : number_format($similar->price, 0, '.', ' ') . ' BYN',
            'oldPrice' => $similarPrice['oldPrice'] ? number_format($similarPrice['oldPrice'], 0, '.', ' ') . ' BYN' : null,
            'discount' => $similarPrice['discountPercent'] ?? 0,
            'image' => $similar->getMainImageUrl(),
            'url' => Url::to(['/catalog/product', 'slug' => $similar->slug ?? $similar->id]),
            'brand' => $similar->brand?->name ?? ($similar->brand_name ?? ''),
        ];
    }
}
$this->registerJsVar('similarProducts', $similarProductsData);

// Отзывы
$reviews = [];
if (!empty($product->reviews)) {
    foreach ($product->reviews as $review) {
        $reviews[] = [
            'id' => $review->id,
            'author' => $review->author,
            'rating' => $review->rating,
            'text' => $review->text,
            'date' => $review->created_at,
            'helpful' => $review->helpful ?? 0,
        ];
    }
}
$this->registerJsVar('productReviews', $reviews);

// Вопросы и ответы
$questions = [];
if (!empty($product->questions)) {
    foreach ($product->questions as $question) {
        $questions[] = [
            'id' => $question->id,
            'question' => $question->question,
            'answer' => $question->answer,
            'date' => $question->created_at,
            'author' => $question->author ?? 'Гость',
        ];
    }
}
$this->registerJsVar('productQuestions', $questions);

// Данные для видео
$productVideo = null;
if (!empty($product->video_url)) {
    $productVideo = $product->video_url;
}
$this->registerJsVar('productVideo', $productVideo);

?>

<div class="product-page-optimized">
    <!-- Schema.org микроразметка для SEO -->
    <script type="application/ld+json"><?= Json::encode($schemaData) ?></script>

    <!-- Хлебные крошки -->
    <nav class="breadcrumb">
        <a href="<?= Url::to(['/']) ?>">Главная</a>
        <span class="breadcrumb-separator">/</span>
        <a href="<?= Url::to(['/catalog']) ?>">Каталог</a>
        <?php if ($product->category): ?>
            <span class="breadcrumb-separator">/</span>
            <a href="<?= Url::to(['/catalog/category', 'slug' => $product->category->slug]) ?>">
                <?= Html::encode($product->category->name) ?>
            </a>
        <?php endif; ?>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-current"><?= Html::encode($productTitle) ?></span>
    </nav>

    <!-- Основной контент товара -->
    <div class="product-content">
        <!-- Левая колонка - Галерея -->
        <div class="product-gallery-section">
            <?php if (count($galleryImages) > 1): ?>
                <!-- Превью галереи -->
                <div class="gallery-thumbnails">
                    <?php foreach ($galleryImages as $idx => $img): ?>
                        <div class="thumbnail-item <?= $idx === 0 ? 'active' : '' ?>" 
                             onclick="changeMainImage('<?= $idx ?>')"
                             data-index="<?= $idx ?>">
                            <img src="<?= $img['url'] ?>" 
                                 alt="<?= Html::encode($img['alt']) ?>"
                                 loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Основное изображение -->
            <div class="main-image-container">
                <div class="main-image-wrapper">
                    <?php if ($productVideo): ?>
                        <!-- Видео превью -->
                        <div class="video-preview" onclick="openVideoModal()">
                            <img src="<?= $galleryImages[0]['url'] ?>" 
                                 alt="<?= Html::encode($productTitle) ?>"
                                 id="mainImage">
                            <div class="video-play-button">
                                <i class="bi bi-play-circle"></i>
                                <span>Смотреть видео-обзор</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Обычное изображение -->
                        <img src="<?= $galleryImages[0]['url'] ?>" 
                             alt="<?= Html::encode($productTitle) ?>"
                             id="mainImage"
                             loading="eager">
                    <?php endif; ?>
                    
                    <!-- Бейджи -->
                    <div class="image-badges">
                        <?php if ($productPriceView['discountPercent'] > 0): ?>
                            <span class="badge discount">-<?= $productPriceView['discountPercent'] ?>%</span>
                        <?php endif; ?>
                        <?php if ($product->is_new ?? false): ?>
                            <span class="badge new">Новинка</span>
                        <?php endif; ?>
                        <?php if ($product->is_hit ?? false): ?>
                            <span class="badge hit">Хит</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Кнопки действий на изображении -->
                <div class="image-actions">
                    <button class="action-btn" onclick="toggleFavorite()" title="Добавить в избранное">
                        <i class="bi <?= $isFavorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                    </button>
                    <button class="action-btn" onclick="shareProduct()" title="Поделиться">
                        <i class="bi bi-share"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Правая колонка - Информация о товаре -->
        <div class="product-info-section">
            <!-- Бренд и артикул -->
            <div class="product-meta">
                <?php if ($product->brand): ?>
                    <div class="brand-info">
                        <img src="<?= $product->brand->logo_url ?? '' ?>" 
                             alt="<?= Html::encode($product->brand->name) ?>"
                             class="brand-logo"
                             onerror="this.style.display='none'">
                        <span class="brand-name"><?= Html::encode($product->brand->name) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="product-sku">
                    Артикул: <?= $product->sku ?? $product->id ?>
                </div>
            </div>

            <!-- Название товара -->
            <h1 class="product-title"><?= Html::encode($productTitle) ?></h1>

            <!-- Теги товара -->
            <?= \app\frontend\widgets\ProductTagsWidget::widget([
                'product' => $product,
                'style' => 'chips',
                'limit' => 5,
                'showLinks' => true,
                'containerClass' => 'product-tags--inline',
            ]) ?>

            <!-- Рейтинг и отзывы -->
            <?php $reviewCount = count($reviews); ?>
            <div class="product-rating-section">
                <?php if ($reviewCount > 0): ?>
                    <div class="rating-stars">
                        <?php $rating = $product->rating ?? 0; for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?= $i <= round($rating) ? '-fill' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-value"><?= number_format((float)($product->rating ?? 0), 1) ?></span>
                    <a href="#reviews" class="reviews-link">
                        (<?= TextHelper::formatReviewCount($reviewCount) ?>)
                    </a>
                <?php else: ?>
                    <a href="#reviews" class="reviews-link reviews-link--empty">
                        <i class="bi bi-chat"></i> Нет отзывов — оставьте первый
                    </a>
                <?php endif; ?>
            </div>

            <!-- Цена -->
            <div class="product-price-section">
                <div class="price-main">
                    <span class="current-price"><?= $productPriceView['currentPrice'] ? number_format($productPriceView['currentPrice'], 0, '.', ' ') . ' BYN' : number_format($product->price, 0, '.', ' ') . ' BYN' ?></span>
                    <?php if (!empty($productPriceView['oldPrice'])): ?>
                        <span class="old-price"><?= number_format($productPriceView['oldPrice'], 0, '.', ' ') . ' BYN' ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($productPriceView['discountPercent'])): ?>
                    <div class="discount-info">
                        <span class="discount-badge">Скидка <?= $productPriceView['discountPercent'] ?>%</span>
                        <span class="discount-description">
                            Экономия: <?= Yii::$app->formatter->asCurrency($productPriceView['oldPrice'] - $productPriceView['currentPrice'], 'BYN') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Выбор размера -->
            <div class="size-selector-section">
                <div class="size-header">
                    <h3>Выберите размер <span class="size-system-label-badge" title="Система размеров">EU</span></h3>
                    <button type="button" class="size-guide-btn" onclick="openSizeGuide()">
                        <i class="bi bi-rulers"></i>
                        Таблица размеров
                    </button>
                </div>

                <div class="size-grid" id="sizeGrid">
                    <?php foreach ($availableSizes as $size => $available): ?>
                        <button type="button" 
                                class="size-btn <?= !$available ? 'unavailable' : '' ?> <?= $size === $selectedSize ? 'active' : '' ?>"
                                data-size="<?= $size ?>"
                                data-available="<?= $available ? '1' : '0' ?>"
                                onclick="selectSize('<?= $size ?>')"
                                <?= !$available ? 'disabled' : '' ?>>
                            <?= $size ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="size-info">
                    <div class="selected-size-display">
                        Выбрано: <strong id="selectedSizeDisplay">Не выбран</strong>
                    </div>
                    <div class="size-legend">
                        <span class="legend-item available">● В наличии</span>
                        <span class="legend-item unavailable">● Нет в наличии</span>
                    </div>
                </div>
            </div>

            <!-- Кнопки покупки -->
            <div class="purchase-actions">
                <?php if ($product->price > 0): ?>
                <button type="button"
                        class="btn btn-primary btn-large add-to-cart-btn"
                        onclick="addToCart()"
                        id="addToCartBtn">
                    <i class="bi bi-cart-plus"></i>
                    <span>Добавить в корзину</span>
                </button>
                <button type="button"
                        class="btn btn-secondary btn-large buy-one-click-btn"
                        onclick="openOneClickModal()">
                    <i class="bi bi-lightning"></i>
                    <span>Купить в 1 клик</span>
                </button>
                <?php else: ?>
                <button type="button"
                        class="btn btn-outline btn-large"
                        onclick="openOneClickModal()">
                    <i class="bi bi-envelope"></i>
                    <span>Цена по запросу — оставить заявку</span>
                </button>
                <?php endif; ?>
            </div>

            <!-- Описание товара -->
            <?php if (!empty($product->description)): ?>
                <div class="product-description-section">
                    <h3>Описание</h3>
                    <div class="description-content">
                        <?= $product->description ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Sticky Purchase Bar -->
    <div class="sticky-purchase-bar" id="stickyBar">
        <div class="sticky-content">
            <div class="sticky-product-info">
                <img src="<?= $galleryImages[0]['url'] ?>" alt="<?= Html::encode($productTitle) ?>">
                <div class="sticky-details">
                    <div class="sticky-title"><?= Html::encode($productTitle) ?></div>
                    <div class="sticky-price"><?= $productPriceView['currentPrice'] ? number_format($productPriceView['currentPrice'], 0, '.', ' ') . ' BYN' : number_format($product->price, 0, '.', ' ') . ' BYN' ?></div>
                </div>
            </div>

            <div class="sticky-size-selector">
                <button type="button" 
                        class="sticky-size-btn"
                        id="stickySizeBtn"
                        onclick="toggleStickySizeDropdown()">
                    <span id="stickySizeDisplay">Размер</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                
                <div class="sticky-size-dropdown" id="stickySizeDropdown">
                    <?php foreach ($availableSizes as $size => $available): ?>
                        <button type="button" 
                                class="sticky-size-option <?= !$available ? 'unavailable' : '' ?>"
                                data-size="<?= $size ?>"
                                onclick="selectStickySize('<?= $size ?>')"
                                <?= !$available ? 'disabled' : '' ?>>
                            <?= $size ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="sticky-actions">
                <button type="button"
                        class="btn btn-primary sticky-add-cart"
                        onclick="addToCartFromSticky()">
                    В корзину
                </button>
            </div>
        </div>
    </div>

    <!-- Доставка и гарантии -->
    <div class="delivery-guarantee-section">
        <div class="delivery-grid">
            <div class="delivery-item">
                <i class="bi bi-truck"></i>
                <div class="delivery-item-content">
                    <h4>Доставка</h4>
                    <ul>
                        <li>По Минску — 1–2 дня</li>
                        <li>По Беларуси — 3–5 дней</li>
                        <li>Самовывоз — сегодня</li>
                    </ul>
                </div>
            </div>
            <div class="delivery-item">
                <i class="bi bi-shield-check"></i>
                <div class="delivery-item-content">
                    <h4>Гарантии</h4>
                    <ul>
                        <li>100% подлинность</li>
                        <li>14 дней на возврат</li>
                        <li>Сертификаты качества</li>
                    </ul>
                </div>
            </div>
            <div class="delivery-item">
                <i class="bi bi-credit-card"></i>
                <div class="delivery-item-content">
                    <h4>Оплата</h4>
                    <ul>
                        <li>Картой онлайн</li>
                        <li>При получении</li>
                        <li>Рассрочка</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Вкладки с информацией -->
    <?php
    $productSpecs = array_filter([
        'Бренд'              => $product->brand?->name ?? ($product->brand_name ?? null),
        'Категория'          => $product->category?->name ?? ($product->category_name ?? null),
        'Артикул'            => $product->sku ?? null,
        'Модель'             => !empty($product->model_name) ? $product->model_name : null,
        'Материал верха'     => $product->upper_material ?? null,
        'Материал подошвы'   => $product->sole_material ?? null,
        'Материал'           => $product->material ?? null,
        'Цвет'               => $product->color_description ?? null,
        'Пол'                => $product->gender ?? null,
        'Сезон'              => $product->season ?? null,
        'Страна'             => $product->country ?? null,
        'Серия'              => $product->series_name ?? null,
        'Дата выпуска'       => !empty($product->release_year) ? $product->release_year : null,
        'Вес (г)'            => !empty($product->weight) ? $product->weight : null,
    ]);
    ?>
    <div class="product-tabs-section">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="description">Описание</button>
            <button class="tab-btn" data-tab="features">Характеристики</button>
            <button class="tab-btn" data-tab="reviews">Отзывы (<?= count($reviews) ?>)</button>
            <button class="tab-btn" data-tab="questions">Вопросы (<?= count($questions) ?>)</button>
        </div>

        <div class="tabs-content">
            <!-- Описание -->
            <div class="tab-pane active" id="description-tab">
                <div class="tab-content-inner">
                    <?php if (!empty($product->description)): ?>
                        <?= $product->description ?>
                    <?php else: ?>
                        <p>Подробное описание товара появится в ближайшее время.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Характеристики -->
            <div class="tab-pane" id="features-tab">
                <div class="tab-content-inner">
                    <?php if (!empty($productSpecs)): ?>
                        <div class="features-table">
                            <?php foreach ($productSpecs as $label => $value): ?>
                                <div class="feature-row">
                                    <div class="feature-name"><?= Html::encode($label) ?></div>
                                    <div class="feature-value"><?= Html::encode($value) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Характеристики товара будут добавлены в ближайшее время.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Отзывы -->
            <div class="tab-pane" id="reviews-tab">
                <div class="tab-content-inner">
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-summary">
                            <div class="rating-overview">
                                <div class="rating-large"><?= $product->rating ?? 4.5 ?></div>
                                <div class="rating-stars-large">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= ($product->rating ?? 4) ? '-fill' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="reviews-count"><?= TextHelper::formatReviewCount(count($reviews)) ?></div>
                            </div>
                            <button class="btn btn-primary" onclick="openReviewModal()">
                                <i class="bi bi-pencil"></i>
                                Написать отзыв
                            </button>
                        </div>

                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="review-author"><?= Html::encode($review['author']) ?></div>
                                        <div class="review-date"><?= date('d.m.Y', strtotime($review['date'])) ?></div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="review-text"><?= Html::encode($review['text']) ?></div>
                                    <div class="review-actions">
                                        <button class="btn-link" onclick="markHelpful(<?= $review['id'] ?>)">
                                            Полезно (<?= $review['helpful'] ?>)
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="bi bi-chat-square-text"></i>
                            <h3>Отзывов пока нет</h3>
                            <p>Будьте первым, кто оставит отзыв об этом товаре!</p>
                            <button class="btn btn-primary" onclick="openReviewModal()">
                                <i class="bi bi-pencil"></i>
                                Написать отзыв
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Вопросы и ответы -->
            <div class="tab-pane" id="questions-tab">
                <div class="tab-content-inner">
                    <div class="questions-header">
                        <h3>Вопросы о товаре</h3>
                        <button class="btn btn-primary" onclick="openQuestionModal()">
                            <i class="bi bi-question-circle"></i>
                            Задать вопрос
                        </button>
                    </div>

                    <?php if (!empty($questions)): ?>
                        <div class="questions-list">
                            <?php foreach ($questions as $question): ?>
                                <div class="question-item">
                                    <div class="question-header">
                                        <div class="question-author"><?= Html::encode($question['author']) ?></div>
                                        <div class="question-date"><?= date('d.m.Y', strtotime($question['date'])) ?></div>
                                    </div>
                                    <div class="question-text">
                                        <strong>Вопрос:</strong> <?= Html::encode($question['question']) ?>
                                    </div>
                                    <?php if (!empty($question['answer'])): ?>
                                        <div class="answer-text">
                                            <strong>Ответ:</strong> <?= Html::encode($question['answer']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="answer-pending">
                                            <i class="bi bi-clock"></i>
                                            Ожидает ответа от администратора
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-questions">
                            <i class="bi bi-question-circle"></i>
                            <h3>Вопросов пока нет</h3>
                            <p>Задайте первый вопрос о этом товаре!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Похожие товары -->
    <?php if (!empty($similarProducts)): ?>
        <div class="similar-products-section">
            <div class="section-header">
                <h2>Похожие товары</h2>
                <a href="/catalog" class="similar-all-link">Смотреть все <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="similar-products-grid">
                <?php foreach ($similarProducts as $similar): ?>
                    <?php $similarPrice = ProductCardHelper::calculatePriceView($similar, null, [], $defaultSizeField); ?>
                    <a class="similar-product-card" href="<?= Url::to(['/catalog/product', 'slug' => $similar->slug ?? $similar->id]) ?>">
                        <div class="product-image">
                            <img src="<?= $similar->getMainImageUrl() ?>"
                                 alt="<?= Html::encode($similar->name) ?>"
                                 loading="lazy">
                        </div>
                        <div class="product-info">
                            <div class="product-brand"><?= Html::encode($similar->brand?->name ?? '') ?></div>
                            <h3 class="product-name"><?= Html::encode($similar->name) ?></h3>
                            <div class="product-price">
                                <span class="current-price"><?= $similarPrice['currentPrice'] ? number_format($similarPrice['currentPrice'], 0, '.', ' ') . ' BYN' : number_format($similar->price, 0, '.', ' ') . ' BYN' ?></span>
                                <?php if (!empty($similarPrice['oldPrice'])): ?>
                                    <span class="old-price"><?= number_format($similarPrice['oldPrice'], 0, '.', ' ') . ' BYN' ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Premium Image Gallery Modal -->
    <div class="image-gallery-modal" id="imageGalleryModal">
        <div class="gallery-modal-content">
            <button class="gallery-close" onclick="closeImageGallery()">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <div class="gallery-scroll-container">
                <?php if (!empty($product->images)): ?>
                    <div class="gallery-images-grid">
                        <?php foreach ($product->images as $idx => $img): ?>
                            <div class="gallery-image-item">
                                <img src="<?= ImageHelper::getWebpUrl($img->getUrl()) ?>" 
                                     alt="<?= Html::encode($product->name . ' — фото ' . ($idx + 1)) ?>"
                                     loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="gallery-placeholder">
                        <i class="bi bi-image"></i>
                        <p>Изображения товара будут добавлены в ближайшее время</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Size Guide Modal -->
    <div class="size-guide-modal" id="sizeGuideModal">
        <div class="size-guide-content">
            <button class="size-guide-close" onclick="closeSizeGuide()">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <h2>Таблица размеров</h2>
            
            <div class="size-guide-tabs">
                <button class="size-tab-btn active" data-system="eu">EU</button>
                <button class="size-tab-btn" data-system="us">US</button>
                <button class="size-tab-btn" data-system="uk">UK</button>
                <button class="size-tab-btn" data-system="cm">CM</button>
            </div>
            
            <div class="size-guide-table">
                <table>
                    <thead>
                        <tr>
                            <th>EU</th>
                            <th>US</th>
                            <th>UK</th>
                            <th>CM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>36</td>
                            <td>4</td>
                            <td>3.5</td>
                            <td>22.5</td>
                        </tr>
                        <tr>
                            <td>37</td>
                            <td>4.5</td>
                            <td>4</td>
                            <td>23</td>
                        </tr>
                        <tr>
                            <td>38</td>
                            <td>5</td>
                            <td>4.5</td>
                            <td>23.5</td>
                        </tr>
                        <tr>
                            <td>39</td>
                            <td>5.5</td>
                            <td>5</td>
                            <td>24</td>
                        </tr>
                        <tr>
                            <td>40</td>
                            <td>6</td>
                            <td>5.5</td>
                            <td>24.5</td>
                        </tr>
                        <tr>
                            <td>41</td>
                            <td>6.5</td>
                            <td>6</td>
                            <td>25</td>
                        </tr>
                        <tr>
                            <td>42</td>
                            <td>7</td>
                            <td>6.5</td>
                            <td>25.5</td>
                        </tr>
                        <tr>
                            <td>43</td>
                            <td>7.5</td>
                            <td>7</td>
                            <td>26</td>
                        </tr>
                        <tr>
                            <td>44</td>
                            <td>8</td>
                            <td>7.5</td>
                            <td>26.5</td>
                        </tr>
                        <tr>
                            <td>45</td>
                            <td>8.5</td>
                            <td>8</td>
                            <td>27</td>
                        </tr>
                        <tr>
                            <td>46</td>
                            <td>9</td>
                            <td>8.5</td>
                            <td>27.5</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="size-guide-tips">
                <h3>Как выбрать правильный размер</h3>
                <ul>
                    <li>Измерьте длину стопы от пятки до самого длинного пальца</li>
                    <li>Добавьте 1-1.5 см для удобства</li>
                    <li>Если у вас широкая стопа, выберите размер на 0.5 больше</li>
                    <li>Лучше примерять обувь во второй половине дня, когда нога немного отекает</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <?php if ($productVideo): ?>
        <div class="video-modal" id="videoModal">
            <div class="video-modal-content">
                <button class="video-modal-close" onclick="closeVideoModal()">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="video-container">
                    <?php
                    $videoId = '';
                    if (strpos($productVideo, 'youtube.com') !== false || strpos($productVideo, 'youtu.be') !== false) {
                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $productVideo, $matches)) {
                            $videoId = $matches[1];
                        }
                    }
                    ?>
                    <?php if ($videoId): ?>
                        <iframe src="https://www.youtube.com/embed/<?= $videoId ?>?rel=0&modestbranding=1" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    <?php else: ?>
                        <video controls>
                            <source src="<?= $productVideo ?>" type="video/mp4">
                            Ваш браузер не поддерживает видео.
                        </video>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- One Click Purchase Modal -->
    <div class="one-click-modal" id="oneClickModal">
        <div class="one-click-content">
            <button class="one-click-close" onclick="closeOneClickModal()">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <h2>Купить в 1 клик</h2>
            <p>Оставьте номер телефона и мы свяжемся с вами в течение 15 минут</p>
            
            <form class="one-click-form" onsubmit="submitOneClickOrder(event)">
                <div class="form-group">
                    <label for="oneClickName">Ваше имя</label>
                    <input type="text" id="oneClickName" required>
                </div>
                <div class="form-group">
                    <label for="oneClickPhone">Телефон</label>
                    <input type="tel" id="oneClickPhone" required>
                </div>
                <div class="form-group">
                    <label for="oneClickSize">Размер</label>
                    <input type="text" id="oneClickSize" value="<?= $selectedSize ?>" readonly>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="bi bi-telephone"></i>
                    Заказать звонок
                </button>
                
                <p class="form-note">
                    Нажимая кнопку, вы соглашаетесь с 
                    <a href="/privacy" target="_blank">политикой конфиденциальности</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="review-modal" id="reviewModal">
        <div class="review-modal-content">
            <button class="review-modal-close" onclick="closeReviewModal()">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <h2>Написать отзыв</h2>
            
            <form class="review-form" onsubmit="submitReview(event)">
                <div class="form-group">
                    <label for="reviewName">Ваше имя</label>
                    <input type="text" id="reviewName" required>
                </div>
                <div class="form-group">
                    <label for="reviewEmail">Email</label>
                    <input type="email" id="reviewEmail" required>
                </div>
                <div class="form-group">
                    <label>Оценка</label>
                    <div class="rating-input" id="reviewRating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="rating-star" data-rating="<?= $i ?>" onclick="setReviewRating(<?= $i ?>)">
                                <i class="bi bi-star"></i>
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reviewText">Отзыв</label>
                    <textarea id="reviewText" rows="5" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="bi bi-send"></i>
                    Отправить отзыв
                </button>
            </form>
        </div>
    </div>

    <!-- Question Modal -->
    <div class="question-modal" id="questionModal">
        <div class="question-modal-content">
            <button class="question-modal-close" onclick="closeQuestionModal()">
                <i class="bi bi-x-lg"></i>
            </button>
            
            <h2>Задать вопрос</h2>
            
            <form class="question-form" onsubmit="submitQuestion(event)">
                <div class="form-group">
                    <label for="questionName">Ваше имя</label>
                    <input type="text" id="questionName" required>
                </div>
                <div class="form-group">
                    <label for="questionEmail">Email</label>
                    <input type="email" id="questionEmail" required>
                </div>
                <div class="form-group">
                    <label for="questionText">Вопрос</label>
                    <textarea id="questionText" rows="4" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="bi bi-send"></i>
                    Задать вопрос
                </button>
            </form>
        </div>
    </div>

    <!-- Success Notification -->
    <div class="success-notification" id="successNotification">
        <div class="notification-content">
            <i class="bi bi-check-circle"></i>
            <span id="notificationText">Товар добавлен в корзину!</span>
        </div>
    </div>
</div>

<!-- JavaScript для страницы товара -->
<script>
// Глобальные переменные
let selectedSize = '<?= $selectedSize ?>';
let productId = <?= $productId ?>;
isFavorite = <?= $isFavorite ? 'true' : 'false' ?>;

// Инициализация галереи
function initProductGallery() {
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const mainImage = document.getElementById('mainImage');
    
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const index = this.dataset.index;
            changeMainImage(index);
        });
    });
}

// Смена основного изображения
function changeMainImage(index) {
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const images = window.productGalleryImages;
    
    if (images && images[index]) {
        mainImage.src = images[index];
        
        thumbnails.forEach((thumb, i) => {
            thumb.classList.toggle('active', i == index);
        });
    }
}

// Выбор размера
function selectSize(size) {
    const sizeBtn = document.querySelector(`.size-btn[data-size="${size}"]`);
    if (!sizeBtn || sizeBtn.disabled) return;

    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    sizeBtn.classList.add('active');
    selectedSize = size;

    const displayEl = document.getElementById('selectedSizeDisplay');
    if (displayEl) displayEl.textContent = size;

    const stickyEl = document.getElementById('stickySizeDisplay');
    if (stickyEl) stickyEl.textContent = size;

    const oneClickEl = document.getElementById('oneClickSize');
    if (oneClickEl) oneClickEl.value = size;

    // Sync with createOrder() in product-page.js
    window.selectedProductSize = size;

    // Обновляем данные товара
    if (window.productData) {
        window.productData.selectedSize = size;
    }
}

// Sticky размер
function toggleStickySizeDropdown() {
    const dropdown = document.getElementById('stickySizeDropdown');
    dropdown.classList.toggle('show');
}

function selectStickySize(size) {
    selectSize(size);
    toggleStickySizeDropdown();
}

// Добавление в корзину
function addToCart() {
    if (!selectedSize) {
        showNotification('Пожалуйста, выберите размер', 'error');
        return;
    }

    const btn = document.getElementById('addToCartBtn');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Добавление...</span>';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    formData.append('size', selectedSize);

    SH.fetch('/cart/add', { method: 'POST', body: formData })
        .then(function(data) {
            if (data.success) {
                if (typeof updateCartCount === 'function') updateCartCount(data.count);
                btn.innerHTML = '<i class="bi bi-check"></i> <span>Добавлено!</span>';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 1500);
                showNotification('Товар добавлен в корзину!', 'success');
                if (typeof openCartDrawer === 'function') openCartDrawer();
            } else {
                btn.innerHTML = originalText;
                btn.disabled = false;
                showNotification(data.message || 'Ошибка добавления', 'error');
            }
        })
        .catch(function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
            showNotification('Ошибка соединения', 'error');
        });
}

// Избранное
function toggleFavorite() {
    const btn = document.querySelector('.action-btn');
    const icon = btn.querySelector('i');
    
    isFavorite = !isFavorite;
    
    if (isFavorite) {
        icon.classList.remove('bi-heart');
        icon.classList.add('bi-heart-fill');
        showNotification('Добавлено в избранное', 'success');
    } else {
        icon.classList.remove('bi-heart-fill');
        icon.classList.add('bi-heart');
        showNotification('Удалено из избранного', 'info');
    }
}

// Модальные окна
function openSizeGuide() {
    document.getElementById('sizeGuideModal').classList.add('show');
}

function closeSizeGuide() {
    document.getElementById('sizeGuideModal').classList.remove('show');
}

function openVideoModal() {
    document.getElementById('videoModal').classList.add('show');
}

function closeVideoModal() {
    document.getElementById('videoModal').classList.remove('show');
}

function openOneClickModal() {
    document.getElementById('oneClickModal').classList.add('show');
}

function closeOneClickModal() {
    document.getElementById('oneClickModal').classList.remove('show');
}

function openReviewModal() {
    document.getElementById('reviewModal').classList.add('show');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.remove('show');
}

function openQuestionModal() {
    document.getElementById('questionModal').classList.add('show');
}

function closeQuestionModal() {
    document.getElementById('questionModal').classList.remove('show');
}

function openImageGallery() {
    document.getElementById('imageGalleryModal').classList.add('show');
}

function closeImageGallery() {
    document.getElementById('imageGalleryModal').classList.remove('show');
}

// Всплывающие уведомления
function showNotification(message, type = 'success') {
    const notification = document.getElementById('successNotification');
    const text = document.getElementById('notificationText');
    
    text.textContent = message;
    notification.className = `success-notification show ${type}`;
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Закрытие модальных окон по клику вне
var _productModalClasses = ['one-click-modal', 'review-modal', 'question-modal', 'image-gallery-modal', 'video-modal'];
document.addEventListener('click', function(event) {
    _productModalClasses.forEach(function(cls) {
        if (event.target.classList.contains(cls)) {
            event.target.classList.remove('show');
        }
    });
});

// Рейтинг для отзыва
var _reviewRating = 0;
function setReviewRating(value) {
    _reviewRating = value;
    document.querySelectorAll('#reviewRating .rating-star').forEach(function(star, idx) {
        var icon = star.querySelector('i');
        icon.className = idx < value ? 'bi bi-star-fill' : 'bi bi-star';
    });
}

// Отправка заказа в 1 клик
function submitOneClickOrder(event) {
    event.preventDefault();
    var form = event.target;
    var btn = form.querySelector('[type="submit"]');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправляем...';

    var formData = new FormData();
    formData.append('name', document.getElementById('oneClickName').value);
    formData.append('phone', document.getElementById('oneClickPhone').value);
    formData.append('size', document.getElementById('oneClickSize').value);
    formData.append('product_id', '<?= $productId ?>');
    formData.append('_csrf', SH.getCsrfToken());

    SH.fetch('/catalog/quick-order', { method: 'POST', body: formData })
        .then(function(data) {
            showNotification('Спасибо! Мы свяжемся с вами в ближайшее время.', 'success');
            closeOneClickModal();
            form.reset();
        })
        .catch(function() {
            showNotification('Ошибка отправки. Попробуйте позже.', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

// Отправка отзыва
function submitReview(event) {
    event.preventDefault();
    if (_reviewRating === 0) {
        showNotification('Пожалуйста, укажите оценку', 'error');
        return;
    }
    var btn = event.target.querySelector('[type="submit"]');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправляем...';

    var formData = new FormData();
    formData.append('name', document.getElementById('reviewName').value);
    formData.append('email', document.getElementById('reviewEmail').value);
    formData.append('rating', _reviewRating);
    formData.append('text', document.getElementById('reviewText').value);
    formData.append('product_id', '<?= $productId ?>');
    formData.append('_csrf', SH.getCsrfToken());

    SH.fetch('/catalog/submit-review', { method: 'POST', body: formData })
        .then(function() {
            showNotification('Спасибо за ваш отзыв!', 'success');
            closeReviewModal();
            event.target.reset();
            _reviewRating = 0;
            document.querySelectorAll('#reviewRating .rating-star i').forEach(function(i) { i.className = 'bi bi-star'; });
        })
        .catch(function() {
            showNotification('Ошибка отправки. Попробуйте позже.', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

// Отправка вопроса
function submitQuestion(event) {
    event.preventDefault();
    var btn = event.target.querySelector('[type="submit"]');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправляем...';

    var formData = new FormData();
    formData.append('name', document.getElementById('questionName').value);
    formData.append('email', document.getElementById('questionEmail').value);
    formData.append('question', document.getElementById('questionText').value);
    formData.append('product_id', '<?= $productId ?>');
    formData.append('_csrf', SH.getCsrfToken());

    SH.fetch('/catalog/submit-question', { method: 'POST', body: formData })
        .then(function() {
            showNotification('Спасибо! Ваш вопрос отправлен.', 'success');
            closeQuestionModal();
            event.target.reset();
        })
        .catch(function() {
            showNotification('Ошибка отправки. Попробуйте позже.', 'error');
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

// Инициализация вкладок
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
    
    // Таблица размеров
    const sizeTabBtns = document.querySelectorAll('.size-tab-btn');
    sizeTabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            sizeTabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

// Sticky Purchase Bar
function initStickyPurchaseBar() {
    const stickyBar = document.getElementById('stickyBar');
    const mainBtn = document.getElementById('addToCartBtn');
    
    if (!stickyBar || !mainBtn) return;
    
    stickyBar.style.position = 'fixed';
    stickyBar.style.bottom = '0';
    stickyBar.style.left = '0';
    stickyBar.style.right = '0';
    stickyBar.style.zIndex = '1000';
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

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    initProductGallery();
    initStickyPurchaseBar();
});

// Закрытие dropdown при клике вне
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('stickySizeDropdown');
    const btn = document.getElementById('stickySizeBtn');
    
    if (!btn.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Frequently Bought Together - Add all to cart
function addAllToCartFBT() {
    const fbtCards = document.querySelectorAll('.fbt-product-card');
    let addedCount = 0;
    
    fbtCards.forEach((card, index) => {
        // Get product info from the card
        const productName = card.querySelector('.fbt-product-name')?.textContent || '';
        const productPrice = card.querySelector('.fbt-product-price')?.textContent || '';
        
        // Add to cart (simulate API call)
        setTimeout(() => {
            addedCount++;
            
            // Show notification
            if (addedCount === fbtCards.length) {
                showNotification('Все товары добавлены в корзину!', 'success');
                
                // Update cart counter if exists
                const cartCounter = document.querySelector('.cart-counter');
                if (cartCounter) {
                    const currentCount = parseInt(cartCounter.textContent) || 0;
                    cartCounter.textContent = currentCount + fbtCards.length;
                }
            }
        }, index * 100);
    });
}

// Helper function to show notifications
function showNotification(message, type = 'info') {
    // Check if notification container exists
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = 'padding:12px 20px;border-radius:8px;background:#333;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.15);animation:slideIn 0.3s ease;';
    notification.textContent = message;
    
    // Add animation styles if not exists
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .notification { font-family: system-ui, -apple-system, sans-serif; font-size: 14px; }
            .notification-success { background: #22c55e !important; }
            .notification-error { background: #ef4444 !important; }
        `;
        document.head.appendChild(style);
    }
    
    container.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>
