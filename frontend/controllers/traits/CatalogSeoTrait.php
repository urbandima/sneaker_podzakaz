<?php

namespace app\controllers\traits;

use Yii;
use app\models\Brand;
use app\models\Category;
use app\components\SmartFilter;

/**
 * Trait CatalogSeoTrait
 * SEO методы для каталога (meta-теги, JSON-LD, Open Graph)
 */
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
        
        // Canonical URL
        $canonicalUrl = $this->buildCanonicalUrl();
        $this->view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');
    }

    /**
     * Построение canonical URL
     * 
     * @return string
     */
    protected function buildCanonicalUrl(): string
    {
        $canonicalUrl = Yii::$app->request->absoluteUrl;
        $parsedUrl = parse_url($canonicalUrl);
        $path = $parsedUrl['path'] ?? '/';
        
        // Убираем trailing slash
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        $canonical = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
        $canonical .= $path;
        
        if (!empty($parsedUrl['query'])) {
            $canonical .= '?' . $parsedUrl['query'];
        }
        
        return $canonical;
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
     * Регистрация Schema.org ItemList
     * 
     * @param array $products
     * @param int $totalCount
     * @param array $filters
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
            
            $productSchema['offers'] = [
                '@type' => 'Offer',
                'price' => $product->price,
                'priceCurrency' => 'BYN',
                'availability' => $product->isInStock() 
                    ? 'https://schema.org/InStock' 
                    : 'https://schema.org/OutOfStock',
            ];
            
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $productSchema
            ];
        }
        
        $this->registerJsonLd($schema, 'schema-itemlist');
    }

    /**
     * Регистрация Schema.org BreadcrumbList
     * 
     * @param array $breadcrumbs
     * @param array $filters
     */
    protected function registerSchemaBreadcrumbs($breadcrumbs, $filters = [])
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        $position = 1;
        foreach ($breadcrumbs as $crumb) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['label'],
                'item' => $crumb['url'] ?? Yii::$app->request->absoluteUrl
            ];
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
     * Получение URL изображения первого товара
     * 
     * @param \yii\db\ActiveQuery $query
     * @return string|null
     */
    protected function getFirstProductImage($query)
    {
        $productQuery = clone $query;
        $product = $productQuery->select(['id', 'main_image_url'])->limit(1)->one();
        
        if ($product && $product->main_image_url) {
            return Yii::$app->request->hostInfo . $product->main_image_url;
        }
        
        return null;
    }
}
