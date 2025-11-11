<?php

namespace app\components;

use Yii;
use yii\caching\CacheInterface;
use yii\helpers\Url;
use app\models\Product;
use app\models\Category;

/**
 * Компонент для генерации Schema.org микроразметки (JSON-LD)
 * 
 * Поддерживает типы:
 * - Product (товар)
 * - Offer (предложение)
 * - BreadcrumbList (хлебные крошки)
 * - Organization (организация)
 * - AggregateRating (рейтинг)
 * - Review (отзывы)
 * 
 * @package app\components
 */
class SchemaOrgGenerator
{
    private const CACHE_TTL = 3600;

    private const CACHE_OPTION_KEYS = ['additionalFields'];

    private const TRANSLATIONS = [
        'material' => [
            'leather' => 'Кожа',
            'textile' => 'Текстиль',
            'synthetic' => 'Синтетика',
            'suede' => 'Замша',
            'mesh' => 'Сетка',
            'canvas' => 'Холст',
        ],
        'gender' => [
            'male' => 'Мужское',
            'female' => 'Женское',
            'unisex' => 'Унисекс',
        ],
        'season' => [
            'summer' => 'Лето',
            'winter' => 'Зима',
            'spring' => 'Весна',
            'autumn' => 'Осень',
            'fall' => 'Осень',
            'all-season' => 'Всесезонная',
            'demi-season' => 'Демисезон',
            'demi' => 'Демисезон',
            'all' => 'Всесезонная',
        ],
        'height' => [
            'low' => 'Низкие',
            'mid' => 'Средние',
            'high' => 'Высокие',
            'ankle' => 'По щиколотку',
            'knee' => 'До колена',
            'over-knee' => 'Выше колена',
        ],
        'fastening' => [
            'lace-up' => 'Шнуровка',
            'laces' => 'Шнуровка',
            'velcro' => 'Липучка',
            'zipper' => 'Молния',
            'buckle' => 'Пряжка',
            'slip-on' => 'Без застежки',
            'slip_on' => 'Без застежки',
            'elastic' => 'Резинка',
            'hook-and-loop' => 'Липучка',
        ],
    ];

    /**
     * Базовый URL сайта
     */
    protected static function getBaseUrl()
    {
        return Yii::$app->params['siteUrl'] ?? 'https://sneakerhead.by';
    }

    /**
     * Полное название организации
     */
    protected static function getOrganizationName()
    {
        return Yii::$app->params['organizationName'] ?? 'СНИКЕРХЭД';
    }

    /**
     * Генерация полной разметки для страницы товара
     * 
     * @param Product $product Товар
     * @param array $options Дополнительные опции для расширения
     * @return array Массив с JSON-LD структурами
     */
    public static function generateProductSchema(Product $product, array $options = [])
    {
        $cache = self::getCacheComponent();
        $cacheEnabled = ($options['cache'] ?? true) && $cache instanceof CacheInterface;
        $ttl = isset($options['cacheDuration']) ? (int)$options['cacheDuration'] : self::CACHE_TTL;

        $builder = static function () use ($product, $options) {
            return self::buildSchemas($product, $options);
        };

        if ($cacheEnabled && $ttl > 0) {
            $cacheKey = self::buildCacheKey('product-schema', $product, $options);
            return $cache->getOrSet($cacheKey, $builder, $ttl);
        }

        return $builder();
    }

    /**
     * Построение структуры Product + Offer
     * 
     * @param Product $product
     * @param array $options
     * @return array
     */
    protected static function buildSchemas(Product $product, array $options = [])
    {
        return [
            'product' => self::buildProductSchema($product, $options),
            'breadcrumbs' => self::buildBreadcrumbsSchema($product),
        ];
    }

    protected static function buildProductSchema(Product $product, array $options = [])
    {
        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->getDisplayTitle(),
            'description' => self::getProductDescription($product),
            'url' => Url::to(['catalog/product', 'slug' => $product->slug], true),
        ];

        // Изображения (множественные)
        $images = self::getProductImages($product);
        if (!empty($images)) {
            $schema['image'] = $images;
        }

        // SKU и артикулы
        $schema = array_merge($schema, self::getProductIdentifiers($product));

        // Бренд
        if ($product->brand) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
            
            // Логотип бренда если есть
            if ($product->brand->logo_url || $product->brand->logo) {
                $schema['brand']['logo'] = $product->brand->getLogoUrl();
            }
        }

        // Категория
        if ($product->category) {
            $schema['category'] = $product->category->name;
        }

        // Рейтинг и отзывы
        if (!empty($product->rating) && $product->rating > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string)$product->rating,
                'bestRating' => '5',
                'worstRating' => '1',
                'ratingCount' => (int)($product->reviews_count ?? 1),
            ];
        }

        // Дополнительные характеристики
        $additionalProperties = [];
        self::buildAdditionalProperties($product, $additionalProperties);
        if (!empty($additionalProperties)) {
            $schema['additionalProperty'] = $additionalProperties;
        }

        // Предложение (цена, наличие)
        $schema['offers'] = self::buildOfferSchema($product);

        // Расширяемые опции
        if (isset($options['additionalFields'])) {
            $schema = array_merge($schema, $options['additionalFields']);
        }

        return $schema;
    }

    /**
     * Получение описания товара
     */
    protected static function getProductDescription(Product $product)
    {
        if (!empty($product->description)) {
            // Убираем HTML теги и лишние пробелы
            $description = strip_tags($product->description);
            $description = preg_replace('/\s+/', ' ', $description);
            return trim($description);
        }

        // Автоматическое описание
        return sprintf(
            '%s %s - купить оригинальные кроссовки в Минске по цене %s. Официальная гарантия качества.',
            $product->brand->name ?? '',
            $product->name,
            Yii::$app->formatter->asCurrency($product->price, 'BYN')
        );
    }

    /**
     * Получение всех изображений товара
     * 
     * @param Product $product
     * @return array
     */
    protected static function getProductImages(Product $product)
    {
        $images = [];

        // Главное изображение
        $mainImage = $product->getMainImageUrl();
        if ($mainImage) {
            $images[] = $mainImage;
        }

        // Дополнительные изображения из галереи
        if (!empty($product->images)) {
            foreach ($product->images as $image) {
                $imageUrl = $image->getUrl();
                if ($imageUrl && !in_array($imageUrl, $images)) {
                    $images[] = $imageUrl;
                }
            }
        }

        return $images;
    }

    /**
     * Получение идентификаторов товара (SKU, MPN, GTIN)
     * 
     * @param Product $product
     * @return array
     */
    protected static function getProductIdentifiers(Product $product)
    {
        $identifiers = [];

        // SKU (уникальный идентификатор продавца)
        if (!empty($product->sku)) {
            $identifiers['sku'] = $product->sku;
        } elseif (!empty($product->style_code)) {
            $identifiers['sku'] = $product->style_code;
        } else {
            // Фоллбэк на ID
            $identifiers['sku'] = 'PROD-' . $product->id;
        }

        // MPN (артикул производителя)
        if (!empty($product->style_code)) {
            $identifiers['mpn'] = $product->style_code;
        } elseif (!empty($product->sku)) {
            $identifiers['mpn'] = $product->sku;
        }

        // GTIN (штрихкод) - если будет добавлен в будущем
        if (!empty($product->gtin)) {
            $identifiers['gtin'] = $product->gtin;
        } elseif (!empty($product->barcode)) {
            $identifiers['gtin'] = $product->barcode;
        }

        return $identifiers;
    }

    /**
     * Построение дополнительных свойств товара
     * 
     * @param Product $product
     * @return array
     */
    protected static function buildAdditionalProperties(Product $product, array &$properties)
    {
        self::appendProperty($properties, 'Материал', $product->material, 'material');
        self::appendProperty($properties, 'Материал верха', $product->upper_material);
        self::appendProperty($properties, 'Материал подошвы', $product->sole_material);
        self::appendProperty($properties, 'Пол', $product->gender, 'gender');
        self::appendProperty($properties, 'Сезон', $product->season, 'season');
        self::appendProperty($properties, 'Высота', $product->height, 'height');
        self::appendProperty($properties, 'Застежка', $product->fastening, 'fastening');
        self::appendProperty($properties, 'Страна производства', $product->country);
        self::appendProperty($properties, 'Серия', $product->series_name);
        self::appendProperty($properties, 'Год выпуска', $product->release_year);
        if (!empty($product->weight)) {
            self::appendProperty($properties, 'Вес', $product->weight . ' г');
        }
        self::appendProperty($properties, 'Цвет', $product->color_description);
        if (!empty($product->is_limited)) {
            self::appendProperty($properties, 'Лимитированная модель', 'Да');
        }
    }

    /**
     * Построение структуры Offer
     * 
     * @param Product $product
     * @return array
     */
    protected static function buildOfferSchema(Product $product)
    {
        $offer = [
            '@type' => 'Offer',
            'url' => Url::to(['catalog/product', 'slug' => $product->slug], true),
            'priceCurrency' => 'BYN',
            'price' => (string)$product->price,
            'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
            'availability' => self::getAvailabilityStatus($product),
            'seller' => [
                '@type' => 'Organization',
                'name' => self::getOrganizationName(),
            ],
        ];

        // Условие товара
        $offer['itemCondition'] = 'https://schema.org/NewCondition';

        // Срок доставки
        if (!empty($product->delivery_time_min) && !empty($product->delivery_time_max)) {
            $offer['deliveryLeadTime'] = [
                '@type' => 'QuantitativeValue',
                'minValue' => $product->delivery_time_min,
                'maxValue' => $product->delivery_time_max,
                'unitCode' => 'DAY',
            ];
        }

        // Старая цена (скидка)
        if ($product->hasDiscount() && !empty($product->old_price)) {
            $offer['priceSpecification'] = [
                '@type' => 'PriceSpecification',
                'price' => (string)$product->price,
                'priceCurrency' => 'BYN',
            ];
            // Можно добавить скидку в процентах
            $offer['discount'] = [
                '@type' => 'Offer',
                'price' => (string)$product->old_price,
                'priceCurrency' => 'BYN',
            ];
        }

        return $offer;
    }

    /**
     * Получение статуса наличия товара
     * 
     * @param Product $product
     * @return string
     */
    protected static function getAvailabilityStatus(Product $product)
    {
        if ($product->isInStock()) {
            return 'https://schema.org/InStock';
        }

        if ($product->stock_status === Product::STOCK_PREORDER) {
            return 'https://schema.org/PreOrder';
        }

        return 'https://schema.org/OutOfStock';
    }

    /**
     * Построение структуры BreadcrumbList
     * 
     * @param Product $product
     * @return array
     */
    protected static function buildBreadcrumbsSchema(Product $product)
    {
        $items = [];

        // Главная
        $items[] = [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Главная',
            'item' => Url::to(['/'], true),
        ];

        // Каталог
        $items[] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Каталог',
            'item' => Url::to(['/catalog'], true),
        ];

        // Категория
        if ($product->category) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $product->category->name,
                'item' => Url::to($product->category->getUrl(), true),
            ];
        }

        // Товар (последний элемент без item)
        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $product->name,
            'item' => Url::to(['catalog/product', 'slug' => $product->slug], true),
        ];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Рендеринг JSON-LD в HTML
     * 
     * @param array $schema Структура Schema.org
     * @param bool $prettyPrint Форматировать JSON
     * @return string HTML тег script с JSON-LD
     */
    public static function renderJsonLd(array $schema, bool $prettyPrint = true)
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($schema, $flags);

        return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
    }

    /**
     * Генерация и рендеринг всей разметки для товара
     * 
     * @param Product $product
     * @param array $options
     * @return string HTML со всеми script-тегами
     */
    public static function render(Product $product, array $options = [])
    {
        $schemas = self::generateProductSchema($product, $options);
        
        $html = '';
        
        // Product Schema
        if (!empty($schemas['product'])) {
            $html .= self::renderJsonLd($schemas['product']);
            $html .= "\n\n";
        }

        // Breadcrumbs Schema
        if (!empty($schemas['breadcrumbs'])) {
            $html .= self::renderJsonLd($schemas['breadcrumbs']);
        }

        return $html;
    }

    // ==========================================
    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ПЕРЕВОДОВ
    // ==========================================

    /**
     * Перевод материала
     */
    protected static function translateMaterial($material)
    {
        return self::translate('material', $material);
    }

    /**
     * Перевод пола
     */
    protected static function translateGender($gender)
    {
        return self::translate('gender', $gender);
    }

    /**
     * Перевод сезона
     */
    protected static function translateSeason($season)
    {
        return self::translate('season', $season);
    }

    /**
     * Перевод высоты
     */
    protected static function translateHeight($height)
    {
        return self::translate('height', $height);
    }

    /**
     * Перевод застежки
     */
    protected static function translateFastening($fastening)
    {
        return self::translate('fastening', $fastening);
    }

    protected static function getCacheComponent(): ?CacheInterface
    {
        return Yii::$app->cache ?? null;
    }

    protected static function buildCacheKey(string $prefix, Product $product, array $options): string
    {
        $timestamp = self::resolveTimestamp($product);
        $fingerprint = [
            'product' => $product->id,
            'updated_at' => $timestamp,
            'options' => self::sanitizeOptionsForCache($options),
        ];

        return $prefix . ':' . md5(json_encode($fingerprint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function sanitizeOptionsForCache(array $options): array
    {
        $normalized = array_intersect_key($options, array_flip(self::CACHE_OPTION_KEYS));

        if (isset($normalized['additionalFields'])) {
            $normalized['additionalFields'] = self::sanitizeAdditionalFields($normalized['additionalFields']);
        }

        return $normalized;
    }

    protected static function sanitizeAdditionalFields($fields)
    {
        if (is_array($fields)) {
            return $fields;
        }

        return [];
    }

    protected static function appendProperty(array &$properties, string $name, $value, string $translationGroup = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = $translationGroup ? self::translate($translationGroup, $value) : $value;

        $properties[] = [
            '@type' => 'PropertyValue',
            'name' => $name,
            'value' => $value,
        ];
    }

    protected static function translate(string $group, $value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $map = self::TRANSLATIONS[$group] ?? [];
        $key = mb_strtolower($value, 'UTF-8');

        return $map[$key] ?? $value;
    }

    protected static function resolveTimestamp(Product $product): int
    {
        $candidates = [
            $product->updated_at ?? null,
            $product->modified_at ?? null,
            $product->updatedAt ?? null,
            $product->created_at ?? null,
            $product->createdAt ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate instanceof \DateTimeInterface) {
                return $candidate->getTimestamp();
            }

            if (is_numeric($candidate)) {
                return (int) $candidate;
            }

            if (is_string($candidate)) {
                $timestamp = strtotime($candidate);
                if ($timestamp) {
                    return $timestamp;
                }
            }
        }

        return time();
    }

    /**
     * Генерация разметки для организации (используется в футере/контактах)
     * 
     * @param array $options
     * @return array
     */
    public static function generateOrganizationSchema(array $options = [])
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => self::getOrganizationName(),
            'url' => self::getBaseUrl(),
            'logo' => Url::to('@web/images/logo.png', true),
            'sameAs' => [
                'https://www.instagram.com/sneakerheadby/',
                'https://t.me/sneakerheadbyweb_bot',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'Customer Service',
                'telephone' => '+375-29-XXX-XX-XX',
                'availableLanguage' => ['Russian', 'Belarusian'],
            ],
        ];

        // Расширяемые опции
        if (!empty($options)) {
            $schema = array_merge($schema, $options);
        }

        return $schema;
    }

    /**
     * Генерация разметки для каталога (ItemList)
     * 
     * @param array $products Массив товаров
     * @param string $categoryName Название категории
     * @return array
     */
    public static function generateItemListSchema(array $products, string $categoryName = 'Каталог')
    {
        $items = [];

        foreach ($products as $index => $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Product',
                    'name' => $product->getDisplayTitle(),
                    'url' => Url::to(['catalog/product', 'slug' => $product->slug], true),
                    'image' => $product->getMainImageUrl(),
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => (string)$product->price,
                        'priceCurrency' => 'BYN',
                    ],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $categoryName,
            'numberOfItems' => count($items),
            'itemListElement' => $items,
        ];
    }
}
