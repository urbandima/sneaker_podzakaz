<?php

/**
 * CatalogSeoTrait — SEO методы для каталога
 * 
 * НАЗНАЧЕНИЕ:
 * Генерация SEO-данных для страниц каталога: мета-теги, Open Graph,
 * Twitter Cards, JSON-LD микроразметка, canonical URL.
 * 
 * ФУНКЦИИ:
 * - Регистрация мета-тегов (registerMetaTags)
 * - Генерация динамического описания (generateFilteredDescription)
 * - Генерация динамического заголовка (generateFilteredTitle)
 * - Получение первого изображения товара (getFirstProductImage)
 * - Генерация УТП для соцсетей (generateProductUTP)
 * - Регистрация Schema.org микроразметки (registerSchemaItemList, registerSchemaBreadcrumbs, registerSchemaWebSite)
 * - Проверка избранного (checkIsFavorite)
 * - Рендер страницы ошибки (renderError)
 * 
 * СВЯЗИ:
 * - Brand (модель бренда)
 * - Category (модель категории)
 * - SmartFilter (компонент умного фильтра)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * Подключить в CatalogController через "use CatalogSeoTrait;"
 * 
 * ОСОБЕННОСТИ:
 * - Canonical URL без trailing slash (SEO best practice)
 * - Динамические описания на основе активных фильтров
 * - Поддержка Open Graph и Twitter Cards
 * - JSON-LD для Google Shopping
 */
namespace app\backend\shared\traits;

use Yii;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Category;
use app\backend\shared\components\SmartFilter;

trait CatalogSeoTrait
{
    /**
     * Регистрация мета-тегов
     * 
     * @param array $tags
     */
    protected function registerMetaTags($tags)
    {
        foreach ($tags as $name => $content) {
            if (strpos($name, 'og:') === 0 || strpos($name, 'product:') === 0 || strpos($name, 'twitter:') === 0) {
                $this->view->registerMetaTag(['property' => $name, 'content' => $content], $name);
            } else {
                $this->view->registerMetaTag(['name' => $name, 'content' => $content], $name);
            }
        }
        
        // Canonical URL - всегда без trailing slash (SEO best practice)
        $canonicalUrl = Yii::$app->request->absoluteUrl;
        $parsedUrl = parse_url($canonicalUrl);
        $path = $parsedUrl['path'] ?? '/';
        
        // Убираем trailing slash из пути, сохраняя query параметры
        if ($path !== '/' && substr($path, -1) === '/') {
            $cleanPath = rtrim($path, '/');
            $canonicalUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $cleanPath;
            
            if (!empty($parsedUrl['query'])) {
                $canonicalUrl .= '?' . $parsedUrl['query'];
            }
        }
        
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl]);
    }

    /**
     * Регистрация JSON-LD схемы
     * 
     * @param array $schema
     * @param string $key
     */
    protected function registerJsonLd($schema, $key)
    {
        $jsonLd = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        if (!isset($this->view->params['jsonLdSchemas'])) {
            $this->view->params['jsonLdSchemas'] = [];
        }
        $this->view->params['jsonLdSchemas'][$key] = $jsonLd;
    }

    /**
     * Регистрация Schema.org ItemList с расширенными данными Product
     * 
     * @param array $products Массив товаров
     * @param int $totalCount Общее количество товаров
     * @param array $filters Активные фильтры для SEO-данных
     */
    protected function registerSchemaItemList($products, $totalCount, $filters = [])
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'numberOfItems' => $totalCount,
            'itemListElement' => []
        ];
        
        if (!empty($filters)) {
            $schema['description'] = $this->generateFilteredDescription($filters, '');
        }
        
        foreach ($products as $index => $product) {
            $productSchema = [
                '@type' => 'Product',
                'name' => $product->name,
                'url' => Yii::$app->request->hostInfo . $product->getUrl(),
                'image' => Yii::$app->request->hostInfo . $product->getMainImageUrl(),
                'sku' => $product->id,
            ];
            
            if (!empty($product->description)) {
                $productSchema['description'] = mb_substr(strip_tags($product->description), 0, 200);
            }
            
            if ($product->brand) {
                $productSchema['brand'] = [
                    '@type' => 'Brand',
                    'name' => $product->brand_name ?? $product->brand->name
                ];
            }
            
            // Offers с расширенными данными
            $availability = 'https://schema.org/OutOfStock';
            if ($product->stock_status === 'in_stock') {
                $availability = 'https://schema.org/InStock';
            } elseif ($product->stock_status === 'pre_order') {
                $availability = 'https://schema.org/PreOrder';
            }
            
            $productSchema['offers'] = [
                '@type' => 'Offer',
                'price' => (string)$product->price,
                'priceCurrency' => 'BYN',
                'availability' => $availability,
                'url' => Yii::$app->request->hostInfo . $product->getUrl(),
                'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
            ];
            
            // Добавляем рейтинг если есть
            if (!empty($product->rating) && $product->rating > 0) {
                $productSchema['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (string)$product->rating,
                    'reviewCount' => (int)($product->reviews_count ?? 1),
                    'bestRating' => '5',
                    'worstRating' => '1'
                ];
            }
            
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $productSchema
            ];
        }
        
        $this->registerJsonLd($schema, 'schema-itemlist');
    }

    /**
     * Регистрация Schema.org BreadcrumbList с учетом фильтров
     * 
     * @param array $breadcrumbs Массив хлебных крошек [['name' => 'Название', 'url' => '/url']]
     * @param array $filters Активные фильтры
     */
    protected function registerSchemaBreadcrumbs($breadcrumbs = [], $filters = [])
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        // Всегда начинаем с главной страницы
        $position = 1;
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Главная',
            'item' => Yii::$app->request->hostInfo
        ];
        
        // Добавляем каталог
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Каталог',
            'item' => Yii::$app->request->hostInfo . '/catalog'
        ];
        
        // Добавляем переданные хлебные крошки (бренд, категория)
        foreach ($breadcrumbs as $crumb) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'],
                'item' => Yii::$app->request->hostInfo . $crumb['url']
            ];
        }
        
        // Добавляем информацию о фильтрах как часть навигации
        if (!empty($filters)) {
            $filterLabels = [];
            
            if (!empty($filters['brands'])) {
                $brands = Brand::find()
                    ->where(['id' => $filters['brands']])
                    ->select('name')
                    ->column();
                if (!empty($brands)) {
                    $filterLabels[] = implode(', ', $brands);
                }
            }
            
            if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
                $priceLabel = 'Цена: ';
                if (!empty($filters['price_from'])) $priceLabel .= 'от ' . $filters['price_from'] . ' ';
                if (!empty($filters['price_to'])) $priceLabel .= 'до ' . $filters['price_to'];
                $filterLabels[] = trim($priceLabel) . ' BYN';
            }
            
            if (!empty($filterLabels)) {
                $schema['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => implode(' • ', $filterLabels),
                    'item' => Yii::$app->request->absoluteUrl
                ];
            }
        }
        
        $this->registerJsonLd($schema, 'schema-breadcrumbs');
    }

    /**
     * Регистрация Schema.org WebSite
     */
    protected function registerSchemaWebSite()
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'СНИКЕРХЭД',
            'url' => Yii::$app->request->hostInfo,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => Yii::$app->request->hostInfo . '/catalog?search={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        $this->registerJsonLd($schema, 'schema-website');
    }

    /**
     * Регистрация rel prev/next для пагинации
     * 
     * @param int $currentPage
     * @param int $totalPages
     * @param array $filters
     */
    protected function registerPaginationLinks($currentPage, $totalPages, $filters)
    {
        $baseUrl = SmartFilter::generateSefUrl($filters);
        
        if ($currentPage > 1) {
            $prevUrl = $baseUrl . '?page=' . ($currentPage - 1);
            $this->view->registerLinkTag([
                'rel' => 'prev',
                'href' => Yii::$app->request->hostInfo . $prevUrl
            ]);
        }
        
        if ($currentPage < $totalPages) {
            $nextUrl = $baseUrl . '?page=' . ($currentPage + 1);
            $this->view->registerLinkTag([
                'rel' => 'next',
                'href' => Yii::$app->request->hostInfo . $nextUrl
            ]);
        }
    }

    /**
     * Генерация динамического описания на основе фильтров
     * 
     * @param array $filters
     * @param string $baseDescription
     * @return string
     */
    protected function generateFilteredDescription($filters, $baseDescription = '')
    {
        $parts = [];
        
        if (!empty($filters['brands'])) {
            $brands = Brand::find()
                ->where(['id' => $filters['brands']])
                ->select('name')
                ->column();
            if (!empty($brands)) {
                $parts[] = implode(', ', $brands);
            }
        }
        
        if (!empty($filters['categories']) && empty($filters['brands'])) {
            $categories = Category::find()
                ->where(['id' => $filters['categories']])
                ->select('name')
                ->column();
            if (!empty($categories)) {
                $parts[] = implode(', ', $categories);
            }
        }
        
        if (!empty($filters['price_from']) && !empty($filters['price_to'])) {
            $parts[] = "от {$filters['price_from']} до {$filters['price_to']} BYN";
        } elseif (!empty($filters['price_from'])) {
            $parts[] = "от {$filters['price_from']} BYN";
        } elseif (!empty($filters['price_to'])) {
            $parts[] = "до {$filters['price_to']} BYN";
        }
        
        if (!empty($parts)) {
            return implode('. ', $parts) . '. ' . $baseDescription;
        }
        
        return $baseDescription ?: 'Оригинальные товары из США и Европы с доставкой по Беларуси';
    }

    /**
     * Генерация динамического заголовка
     * 
     * @param array $filters
     * @param string $baseTitle
     * @return string
     */
    protected function generateFilteredTitle($filters, $baseTitle = 'Каталог')
    {
        $parts = [$baseTitle];
        
        if (!empty($filters['brands'])) {
            $brands = Brand::find()
                ->where(['id' => array_slice($filters['brands'], 0, 2)])
                ->select('name')
                ->column();
            if (!empty($brands)) {
                $parts[] = implode(', ', $brands);
            }
        }
        
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            if (!empty($filters['price_from']) && !empty($filters['price_to'])) {
                $parts[] = "{$filters['price_from']}-{$filters['price_to']} BYN";
            } elseif (!empty($filters['price_from'])) {
                $parts[] = "от {$filters['price_from']} BYN";
            } elseif (!empty($filters['price_to'])) {
                $parts[] = "до {$filters['price_to']} BYN";
            }
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Получение URL изображения первого товара из выборки
     * 
     * @param \yii\db\ActiveQuery $query
     * @return string|null
     */
    protected function getFirstProductImage($query)
    {
        $productQuery = clone $query;
        $product = $productQuery->select(['id', 'main_image_url'])->limit(1)->one();
        
        if ($product && $product->main_image_url) {
            $imageUrl = $product->main_image_url;
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = Yii::$app->request->hostInfo . '/' . ltrim($imageUrl, '/');
            }
            return $imageUrl;
        }
        
        return null;
    }
    
    /**
     * Генерация УТП (уникального торгового предложения) для товара
     * 
     * @param mixed $product
     * @return string
     */
    protected function generateProductUTP($product)
    {
        $utp = [];
        
        $utp[] = '✓ 100% оригинал';
        
        if ($product->brand_name) {
            $utp[] = $product->brand_name . ' ' . $product->name;
        }
        
        if ($product->old_price && $product->old_price > $product->price) {
            $discount = round((($product->old_price - $product->price) / $product->old_price) * 100);
            $utp[] = "Скидка {$discount}%! Цена: {$product->price} BYN (было {$product->old_price} BYN)";
        } else {
            $utp[] = "Цена: {$product->price} BYN";
        }
        
        if ($product->stock_status === 'in_stock') {
            $utp[] = '✓ В наличии';
        } elseif ($product->stock_status === 'pre_order') {
            $utp[] = '✓ Под заказ 7-14 дней';
        }
        
        $utp[] = '✓ Доставка по Беларуси';
        $utp[] = '✓ Гарантия подлинности';
        
        if (!empty($product->rating) && $product->rating >= 4) {
            $stars = str_repeat('⭐', min(5, (int)$product->rating));
            $utp[] = "Рейтинг: {$stars}";
        }
        
        return implode(' • ', $utp);
    }
}
