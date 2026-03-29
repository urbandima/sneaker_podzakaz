<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product|stdClass $product */
/** @var app\backend\modules\catalog\models\Product[]|stdClass[] $similarProducts */
/** @var bool $isFavorite */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use app\frontend\assets\ProductAsset;
use app\helpers\ProductCardHelper;
use app\helpers\ImageHelper;
use app\backend\shared\components\SchemaOrgGenerator;

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
if (!empty($product->images)) {
    foreach ($product->images as $idx => $img) {
        $url = $img->getUrl();
        if (!$url) {
            continue;
        }
        $galleryImages[] = [
            'url' => ImageHelper::getWebpUrl($url),
            'alt' => $product->name . ' — фото ' . ($idx + 1),
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
// SCHEMA.ORG МИКРОРАЗМЕТКА (JSON-LD)
// ============================================
// Используем компонент SchemaOrgGenerator для генерации полной разметки
echo SchemaOrgGenerator::render($product);
?>

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
            <?php if ($product->category ?? null): ?>
            <a href="<?= $product->category->getUrl() ?>"><?= Html::encode($product->category->name) ?></a> /
            <?php endif; ?>
            <span><?= Html::encode($product->name) ?></span>
        </nav>

        <div class="product-layout">
            <!-- Галерея с миниатюрами -->
            <div class="product-gallery-wrapper">
                <!-- Swipe Gallery для mobile + desktop -->
                <div class="product-gallery-swipe">
                    <?php if (count($galleryImages) > 1): ?>
                    <button class="gallery-arrow prev" type="button" onclick="galleryPrevSlide()" aria-label="Предыдущее фото">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="gallery-arrow next" type="button" onclick="galleryNextSlide()" aria-label="Следующее фото">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <?php endif; ?>
                    <div class="swipe-track">
                        <?php foreach ($galleryImages as $index => $image): ?>
                        <div class="swipe-slide <?= $index === 0 ? 'active' : '' ?><?= $image['placeholder'] ? ' swipe-slide--placeholder' : '' ?>"
                             data-index="<?= $index ?>"
                             data-full-src="<?= Html::encode($image['url']) ?>"
                             data-placeholder="<?= $image['placeholder'] ? '1' : '0' ?>"
                             onclick="openImageModal(<?= $index ?>)">
                            <img src="<?= Html::encode($image['url']) ?>"
                                 alt="<?= Html::encode($image['alt']) ?>"
                                 loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                 <?= $index === 0 ? 'fetchpriority="high" decoding="async"' : 'decoding="async"' ?>>
                            <div class="zoom-icon"><i class="bi bi-zoom-in"></i></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($galleryImages) > 1): ?>
                    <div class="swipe-pagination">
                        <?php foreach ($galleryImages as $index => $image): ?>
                        <span class="swipe-dot <?= $index === 0 ? 'active' : '' ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <button class="fav-btn <?= $isFavorite ? 'active' : '' ?>" onclick="toggleFav(event,<?= $product->id ?>)" aria-label="Добавить в избранное">
                        <i class="bi bi-heart-fill"></i>
                    </button>
                </div>
                
                <div class="gallery-zoom-view" id="galleryZoomView" aria-hidden="true">
                    <div class="gallery-zoom-hint">
                        <i class="bi bi-cursor"></i>
                        Наведите курсор для увеличения
                    </div>
                </div>
                
                <!-- Миниатюры под галереей -->
                <?php if (count($galleryImages) > 1): ?>
                <div class="product-thumbnails-carousel">
                    <button class="thumb-nav thumb-prev" onclick="scrollThumbnails('prev')" aria-label="Предыдущие миниатюры">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="thumbnails-wrapper">
                        <div class="thumbnails-track">
                            <?php foreach ($galleryImages as $index => $image): ?>
                            <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>" 
                                 data-index="<?= $index ?>" 
                                 onclick="switchToSlide(<?= $index ?>)">
                                <img src="<?= Html::encode($image['url']) ?>" 
                                     alt="<?= Html::encode($image['alt']) ?>" 
                                     loading="lazy">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="thumb-nav thumb-next" onclick="scrollThumbnails('next')" aria-label="Следующие миниатюры">
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

                <?php
                    $priceDataAttributes = [
                        'data-base-price' => $product->price,
                        'data-has-range' => $productPriceView['showRange'] ? 'true' : 'false',
                    ];
                    if ($priceProductMin = $productPriceView['minPrice']) {
                        $priceDataAttributes['data-min-price'] = $priceProductMin;
                    }
                    if ($priceProductMax = $productPriceView['maxPrice']) {
                        $priceDataAttributes['data-max-price'] = $priceProductMax;
                    }
                    $priceAttrString = '';
                    foreach ($priceDataAttributes as $attr => $value) {
                        $priceAttrString .= sprintf(' %s="%s"', $attr, Html::encode($value));
                    }
                ?>
                <div class="price-block">
                    <?php if ($productPriceView['showOldPrice'] && $productPriceView['oldPrice'] !== null): ?>
                        <span class="old"><?= Yii::$app->formatter->asCurrency($productPriceView['oldPrice'], ProductCardHelper::PRICE_CURRENCY) ?></span>
                        <?php if ($productPriceView['discountPercent'] !== null): ?>
                            <span class="disc">-<?= $productPriceView['discountPercent'] ?>%</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($productPriceView['showRange'] && $productPriceView['minPrice'] !== null && $productPriceView['maxPrice'] !== null): ?>
                        <span class="current" id="productPrice"<?= $priceAttrString ?>>
                            <?= Yii::$app->formatter->asCurrency($productPriceView['minPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                            <span class="price-separator"> - </span>
                            <?= Yii::$app->formatter->asCurrency($productPriceView['maxPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                        </span>
                    <?php else: ?>
                        <span class="current" id="productPrice"<?= $priceAttrString ?>>
                            <?= Yii::$app->formatter->asCurrency($productPriceView['currentPrice'] ?? $product->price, ProductCardHelper::PRICE_CURRENCY) ?>
                        </span>
                    <?php endif; ?>
                </div>


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

        <?php
        $decodedProperties = [];
        if (!empty($product->properties)) {
            $decodedProperties = json_decode($product->properties, true);
            if (!is_array($decodedProperties)) {
                $decodedProperties = [];
            }
        }

        $specsCount = 0;
        if ($product->brand) {
            $specsCount++;
        }
        if (!empty($product->category)) {
            $specsCount++;
        }
        if ($product->series_name) {
            $specsCount++;
        }
        if ($product->style_code) {
            $specsCount++;
        }
        if (!empty($product->gender)) {
            $specsCount++;
        }
        if (!empty($product->season)) {
            $specsCount++;
        }
        if ($product->country || $product->country_of_origin) {
            $specsCount++;
        }
        if ($product->release_year) {
            $specsCount++;
        }
        if ($product->weight) {
            $specsCount++;
        }
        if (!empty($product->material)) {
            $specsCount++;
        }
        if (!empty($product->fastening)) {
            $specsCount++;
        }
        if (!empty($product->height)) {
            $specsCount++;
        }
        if (!empty($decodedProperties)) {
            foreach ($decodedProperties as $prop) {
                if (!empty($prop['key']) && !empty($prop['value'])) {
                    $specsCount++;
                }
            }
        }

        $reviewsCount = !empty($product->reviews) ? count($product->reviews) : (int)($product->reviews_count ?? 0);
        $qaCount = !empty($product->questions) ? count($product->questions) : 0;

        $pluralizeRu = static function (int $number, array $forms): string {
            $number = abs($number) % 100;
            $n1 = $number % 10;

            if ($number > 10 && $number < 20) {
                return $forms[2];
            }

            if ($n1 > 1 && $n1 < 5) {
                return $forms[1];
            }

            if ($n1 === 1) {
                return $forms[0];
            }

            return $forms[2];
        };
        ?>

        <!-- Характеристики товара -->
        <div class="product-specs-section premium-accordion">
            <div class="premium-accordion-header specs-header-toggle" onclick="toggleMainSpecs()">
                <h2>
                    <span class="accordion-icon">📋</span>
                    Характеристики
                    <?php if ($specsCount > 0): ?>
                    <span class="premium-accordion-count">
                        (<?= $specsCount ?> <?= $pluralizeRu($specsCount, ['характеристика', 'характеристики', 'характеристик']) ?>)
                    </span>
                    <?php endif; ?>
                </h2>
                <i class="bi bi-chevron-down toggle-icon" id="mainSpecsToggleIcon"></i>
            </div>
            <div class="premium-accordion-content" id="mainSpecsContent" style="display:none">

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
                <?php if (!empty($product->material) || !empty($product->fastening) || !empty($product->height)): ?>
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
                    </table>
                </div>
                <?php endif; ?>

                <?php 
                // Дополнительные характеристики из Poizon properties
                if (!empty($decodedProperties)):
                ?>
                <div class="spec-section">
                    <h3>Дополнительные характеристики</h3>
                    <table class="specs-table">
                        <?php 
                        foreach ($decodedProperties as $prop):
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

        <!-- Похожие товары - карусель -->
        <?php
        // Все похожие товары уже получены из контроллера через ProductRepository
        // с многоуровневой стратегией поиска (related_products, series_name, brand+category, etc)
        ?>
        
        <!-- Показываем блок только если есть товары -->
        <?php if (!empty($similarProducts)): ?>
        <?php $relatedSizeField = ProductCardHelper::resolveSizeField(ProductCardHelper::DEFAULT_SIZE_SYSTEM); ?>
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
            
            .related-price-range,
            .related-price-block {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            
            .related-old-price {
                font-size: 0.75rem;
                color: #9ca3af;
                text-decoration: line-through;
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
                    <span class="related-count">(<?= count($similarProducts) ?>)</span>
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
                            <?php foreach ($similarProducts as $item): 
                                $priceView = ProductCardHelper::calculatePriceView($item, null, [], $relatedSizeField);
                                $showRange = $priceView['showRange'] && $priceView['minPrice'] !== null && $priceView['maxPrice'] !== null;
                            ?>
                            <a href="<?= $item->getUrl() ?>" class="related-product-card">
                                <div class="related-product-image">
                                    <img src="<?= $item->getMainImageUrl() ?>" 
                                         alt <?= Html::encode($item->name) ?> 
                                         loading="lazy">
                                    <?php if ($priceView['discountPercent'] !== null): ?>
                                        <span class="related-discount-badge">-<?= $priceView['discountPercent'] ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div class="related-product-info">
                                    <?php if ($item->brand): ?>
                                        <div class="related-brand"><?= Html::encode($item->brand->name) ?></div>
                                    <?php endif; ?>
                                    <div class="related-product-name"><?= Html::encode($item->name) ?></div>
                                    <?php if ($showRange): ?>
                                        <div class="related-price-range">
                                            <span class="related-price-from">
                                                от <?= Yii::$app->formatter->asCurrency($priceView['minPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                                            </span>
                                            <span class="related-price-to">
                                                до <?= Yii::$app->formatter->asCurrency($priceView['maxPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="related-price-block">
                                            <?php if ($priceView['showOldPrice'] && $priceView['oldPrice'] !== null): ?>
                                                <span class="related-old-price">
                                                    <?= Yii::$app->formatter->asCurrency($priceView['oldPrice'], ProductCardHelper::PRICE_CURRENCY) ?>
                                                </span>
                                            <?php endif; ?>
                                            <span class="related-price">
                                                <?= Yii::$app->formatter->asCurrency($priceView['currentPrice'] ?? $item->price, ProductCardHelper::PRICE_CURRENCY) ?>
                                            </span>
                                        </div>
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
        <div class="reviews-enhanced premium-accordion" id="reviews">
            <div class="premium-accordion-header reviews-header" onclick="toggleReviews()">
                <h2>
                    <span class="accordion-icon">💬</span>
                    Отзывы покупателей
                    <span class="premium-accordion-count">
                        <?php if ($reviewsCount > 0): ?>
                            (<?= $reviewsCount ?> <?= $pluralizeRu($reviewsCount, ['отзыв', 'отзыва', 'отзывов']) ?>)
                        <?php else: ?>
                            Будьте первым
                        <?php endif; ?>
                    </span>
                </h2>
                <i class="bi bi-chevron-down toggle-icon" id="reviewsToggleIcon"></i>
            </div>
            <div class="premium-accordion-content reviews-list" id="reviewsContent" style="display:none">
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
                        <i class="bi bi-chat-left-text placeholder-icon"></i>
                        <h3>Отзывов пока нет</h3>
                        <p>Будьте первым, кто оставит отзыв о этом товаре</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Q&A раздел -->
        <div class="community-qa premium-accordion">
            <div class="premium-accordion-header qa-header" onclick="toggleQA()">
                <h2>
                    <span class="accordion-icon">❓</span>
                    Вопросы и ответы
                    <span class="premium-accordion-count">
                        <?php if ($qaCount > 0): ?>
                            (<?= $qaCount ?> <?= $pluralizeRu($qaCount, ['вопрос', 'вопроса', 'вопросов']) ?>)
                        <?php else: ?>
                            Вопросов пока нет
                        <?php endif; ?>
                    </span>
                </h2>
                <i class="bi bi-chevron-down toggle-icon" id="qaToggleIcon"></i>
            </div>
            <div class="premium-accordion-content qa-list" id="qaContent" style="display:none">
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
                        <i class="bi bi-question-circle placeholder-icon"></i>
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
            <?php 
                $stickyPriceRange = $product->getPriceRange();
                $stickyHasRange = $stickyPriceRange && $product->hasPriceRange();
            ?>
            <div
                class="sticky-price"
                id="stickyPrice"
                data-base-price="<?= $product->price ?>"
                data-has-range="<?= $stickyHasRange ? 'true' : 'false' ?>"
                <?php if ($stickyHasRange): ?>
                    data-min-price="<?= $stickyPriceRange['min'] ?>"
                    data-max-price="<?= $stickyPriceRange['max'] ?>"
                <?php endif; ?>
            >
                <?php if ($stickyHasRange): ?>
                    <?= Yii::$app->formatter->asCurrency($stickyPriceRange['min'], 'BYN') ?>
                    <span class="price-separator"> - </span>
                    <?= Yii::$app->formatter->asCurrency($stickyPriceRange['max'], 'BYN') ?>
                <?php else: ?>
                    <?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?>
                <?php endif; ?>
            </div>
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
                <div class="sizes-empty">Нет доступных размеров</div>
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
                    <?php 
                        $quickPriceRange = $product->getPriceRange();
                        $quickHasRange = $quickPriceRange && $product->hasPriceRange();
                    ?>
                    <div
                        class="price"
                        id="quickOrderPrice"
                        data-base-price="<?= $product->price ?>"
                        data-has-range="<?= $quickHasRange ? 'true' : 'false' ?>"
                        <?php if ($quickHasRange): ?>
                            data-min-price="<?= $quickPriceRange['min'] ?>"
                            data-max-price="<?= $quickPriceRange['max'] ?>"
                        <?php endif; ?>
                    >
                        <?php if ($quickHasRange): ?>
                            <?= Yii::$app->formatter->asCurrency($quickPriceRange['min'], 'BYN') ?>
                            <span class="price-separator"> - </span>
                            <?= Yii::$app->formatter->asCurrency($quickPriceRange['max'], 'BYN') ?>
                        <?php else: ?>
                            <?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <form id="quickOrderForm" onsubmit="submitQuickOrder(event)" data-product-id="<?= $product->id ?>">
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

<!-- Inline JavaScript для функций с PHP данными -->
<script>
// Size System Switcher - глобальная функция
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

// Open Size Table Modal - глобальная функция с PHP данными
function openSizeTableModal() {
    <?php 
    // Группируем размеры по EU размеру
    $sizesGrouped = [];
    if (!empty($product->availableSizes)) {
        foreach ($product->availableSizes as $size) {
            $euSize = $size->eu_size ?: $size->size;
            if (!isset($sizesGrouped[$euSize])) {
                $sizesGrouped[$euSize] = $size;
            }
        }
    }
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

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSizeTableModal();
    }
});

// Add pulse animation
if (!document.getElementById('pulse-animation-style')) {
    const style = document.createElement('style');
    style.id = 'pulse-animation-style';
    style.textContent = `
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    `;
    document.head.appendChild(style);
}

// ============================================================================
// Функции для работы с изображениями (требуют PHP данные)
// ============================================================================

// Массив изображений товара
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
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
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

// ============================================================================
// Функции для аккордеонов и UI интерактивности
// ============================================================================

// Accordion для характеристик
function toggleMainSpecs() {
    const content = document.getElementById('mainSpecsContent');
    const icon = document.getElementById('mainSpecsToggleIcon');
    const header = icon ? icon.closest('.specs-header-toggle') : null;
    
    if (content) {
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            if (header) header.classList.add('open');
        } else {
            content.style.display = 'none';
            if (header) header.classList.remove('open');
        }
    }
}

// Accordion для блока похожих товаров
function toggleRelatedProducts() {
    const content = document.getElementById('relatedContent');
    const icon = document.getElementById('relatedToggleIcon');
    const header = icon ? icon.closest('.related-header') : null;
    
    if (!content || !icon || !header) return;
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('active');
    } else {
        content.style.display = 'none';
        header.classList.remove('active');
    }
}

// Функция прокрутки карусели похожих товаров
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

// Accordion для отзывов
function toggleReviews() {
    const content = document.getElementById('reviewsContent');
    const icon = document.getElementById('reviewsToggleIcon');
    const header = icon ? icon.closest('.reviews-header') : null;
    
    if (content) {
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            if (header) header.classList.add('open');
        } else {
            content.style.display = 'none';
            if (header) header.classList.remove('open');
        }
    }
}

// Accordion для Q&A
function toggleQA() {
    const content = document.getElementById('qaContent');
    const icon = document.getElementById('qaToggleIcon');
    const header = icon ? icon.closest('.qa-header') : null;
    
    if (content) {
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'flex';
            if (header) header.classList.add('open');
        } else {
            content.style.display = 'none';
            if (header) header.classList.remove('open');
        }
    }
}

// Accordion для описания товара (если есть)
function toggleDescription() {
    const content = document.getElementById('descContent');
    const icon = document.getElementById('descToggleIcon');
    
    if (content && icon) {
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
}

// ============================================================================
// Функции модальных окон теперь в /web/js/product-modals.js
// ============================================================================

// ============================================================================
// Дополнительные функции для UI (могут не быть определены в product-page.js)
// ============================================================================

// Закрытие галереи изображений (если используется)
function closeImageGallery() {
    const gallery = document.getElementById('imageGalleryModal');
    if (gallery) {
        gallery.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// ВАЖНО: Остальные функции (scrollThumbnails, switchToSlide, selectColor, createOrder,
// closeSizeGuide, recommendSize, toggleStickySizeDropdown, addToCartFromSticky)
// определены в product-page.js и должны быть доступны после загрузки файла
</script>

