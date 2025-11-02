# Реализация умного фильтра - Практическое руководство

**Дата**: 01.11.2025, 23:52  
**Основано на**: Битрикс24, WildBerries, Ozon

---

## 🎯 КРИТИЧНЫЕ ФИЧИ (Этап 1)

### 1. SEF URL для комбинаций фильтров

**Создать**: `components/SmartFilter.php`

```php
<?php

namespace app\components;

use Yii;
use yii\base\Component;
use app\models\Brand;
use app\models\Category;

/**
 * Компонент для работы с SEF URL фильтров
 */
class SmartFilter extends Component
{
    /**
     * Генерация SEF URL из фильтров
     * 
     * @param array $filters ['brands' => [1,2], 'price_from' => 100]
     * @return string /catalog/filter/nike-adidas/price-100-500/
     */
    public static function generateSefUrl($filters)
    {
        $parts = [];
        
        // Бренды
        if (!empty($filters['brands'])) {
            $brandSlugs = Brand::find()
                ->select('slug')
                ->where(['id' => $filters['brands']])
                ->orderBy(['slug' => SORT_ASC])
                ->column();
            
            if ($brandSlugs) {
                $parts[] = 'brand-' . implode('-', $brandSlugs);
            }
        }
        
        // Категории
        if (!empty($filters['categories'])) {
            $categorySlugs = Category::find()
                ->select('slug')
                ->where(['id' => $filters['categories']])
                ->orderBy(['slug' => SORT_ASC])
                ->column();
            
            if ($categorySlugs) {
                $parts[] = 'category-' . implode('-', $categorySlugs);
            }
        }
        
        // Цена
        if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
            $from = $filters['price_from'] ?? 0;
            $to = $filters['price_to'] ?? 'max';
            $parts[] = "price-{$from}-{$to}";
        }
        
        // Размеры
        if (!empty($filters['sizes'])) {
            $sizes = is_array($filters['sizes']) 
                ? implode('-', $filters['sizes']) 
                : $filters['sizes'];
            $parts[] = 'size-' . $sizes;
        }
        
        if (empty($parts)) {
            return '/catalog/';
        }
        
        return '/catalog/filter/' . implode('/', $parts) . '/';
    }
    
    /**
     * Парсинг SEF URL в фильтры
     * 
     * @param string $sefString nike-adidas/price-100-500
     * @return array ['brands' => [1,2], 'price_from' => 100]
     */
    public static function parseSefUrl($sefString)
    {
        $filters = [];
        $parts = explode('/', trim($sefString, '/'));
        
        foreach ($parts as $part) {
            // brand-nike-adidas
            if (preg_match('/^brand-(.+)$/', $part, $m)) {
                $brandSlugs = explode('-', $m[1]);
                $brandIds = Brand::find()
                    ->select('id')
                    ->where(['slug' => $brandSlugs])
                    ->column();
                $filters['brands'] = $brandIds;
            }
            // category-krossovki
            elseif (preg_match('/^category-(.+)$/', $part, $m)) {
                $categorySlugs = explode('-', $m[1]);
                $categoryIds = Category::find()
                    ->select('id')
                    ->where(['slug' => $categorySlugs])
                    ->column();
                $filters['categories'] = $categoryIds;
            }
            // price-100-500
            elseif (preg_match('/^price-(\d+|min)-(\d+|max)$/', $part, $m)) {
                if ($m[1] !== 'min' && $m[1] > 0) {
                    $filters['price_from'] = (int)$m[1];
                }
                if ($m[2] !== 'max') {
                    $filters['price_to'] = (int)$m[2];
                }
            }
            // size-40-41-42
            elseif (preg_match('/^size-(.+)$/', $part, $m)) {
                $filters['sizes'] = explode('-', $m[1]);
            }
        }
        
        return $filters;
    }
    
    /**
     * Получить canonical URL для комбинации фильтров
     */
    public static function getCanonicalUrl($filters, $productsCount)
    {
        // Если товаров мало или фильтров нет - canonical на базовую страницу
        if (empty($filters) || $productsCount < 10) {
            return Yii::$app->request->hostInfo . '/catalog/';
        }
        
        // Если фильтр только один - canonical на страницу этого фильтра
        if (count($filters) == 1) {
            if (isset($filters['brands']) && count($filters['brands']) == 1) {
                $brand = Brand::findOne($filters['brands'][0]);
                return $brand ? $brand->getAbsoluteUrl() : '/catalog/';
            }
            if (isset($filters['categories']) && count($filters['categories']) == 1) {
                $category = Category::findOne($filters['categories'][0]);
                return $category ? $category->getAbsoluteUrl() : '/catalog/';
            }
        }
        
        // Для комбинаций - canonical на текущий SEF URL
        return Yii::$app->request->hostInfo . self::generateSefUrl($filters);
    }
    
    /**
     * Получить robots директиву в зависимости от количества товаров
     */
    public static function getRobotsDirective($productsCount)
    {
        if ($productsCount >= 10) {
            return 'index, follow'; // Много товаров - индексировать
        } elseif ($productsCount >= 3) {
            return 'noindex, follow'; // Среднее - не индексировать
        } else {
            return 'noindex, nofollow'; // Мало - совсем не индексировать
        }
    }
}
```

**Обновить**: `config/web.php`

```php
'rules' => [
    // ... существующие правила
    
    // SEF URLs для фильтров
    'catalog/filter/<filters:[\w\-/]+>' => 'catalog/filter-sef',
    
    // ... остальные правила
],
```

**Обновить**: `CatalogController.php`

```php
use app\components\SmartFilter;

/**
 * SEF фильтрация
 */
public function actionFilterSef($filters = '')
{
    // Парсим SEF URL
    $parsedFilters = SmartFilter::parseSefUrl($filters);
    
    // Применяем фильтры
    $query = Product::find()
        ->with(['brand', 'category'])
        ->where(['is_active' => 1]);
    
    $query = $this->applyParsedFilters($query, $parsedFilters);
    
    $totalCount = $query->count();
    
    // SEO
    $canonicalUrl = SmartFilter::getCanonicalUrl($parsedFilters, $totalCount);
    $robotsDirective = SmartFilter::getRobotsDirective($totalCount);
    
    $this->view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl]);
    $this->view->registerMetaTag(['name' => 'robots', 'content' => $robotsDirective]);
    
    // Динамический H1
    $h1 = $this->generateDynamicH1($parsedFilters, $totalCount);
    
    // Пагинация и рендер
    $pagination = new Pagination(['totalCount' => $totalCount, 'pageSize' => 24]);
    $products = $query->offset($pagination->offset)->limit($pagination->limit)->all();
    
    // Schema.org ItemList
    $this->registerSchemaItemList($products, $totalCount);
    
    return $this->render('index', [
        'products' => $products,
        'pagination' => $pagination,
        'filters' => $this->getAvailableFilters($parsedFilters),
        'activeFilters' => $this->formatActiveFilters($parsedFilters),
        'h1' => $h1,
    ]);
}

/**
 * Применение распарсенных фильтров
 */
protected function applyParsedFilters($query, $filters)
{
    if (!empty($filters['brands'])) {
        $query->andWhere(['brand_id' => $filters['brands']]);
    }
    
    if (!empty($filters['categories'])) {
        $query->andWhere(['category_id' => $filters['categories']]);
    }
    
    if (isset($filters['price_from'])) {
        $query->andWhere(['>=', 'price', $filters['price_from']]);
    }
    
    if (isset($filters['price_to'])) {
        $query->andWhere(['<=', 'price', $filters['price_to']]);
    }
    
    if (!empty($filters['sizes'])) {
        $query->joinWith('sizes')
            ->andWhere(['product_size.size' => $filters['sizes']]);
    }
    
    return $query;
}

/**
 * Генерация динамического H1
 */
protected function generateDynamicH1($filters, $count)
{
    $parts = [];
    
    if (!empty($filters['brands'])) {
        $brands = Brand::find()->where(['id' => $filters['brands']])->all();
        $brandNames = array_map(fn($b) => $b->name, $brands);
        $parts[] = implode(', ', $brandNames);
    }
    
    if (!empty($filters['categories'])) {
        $categories = Category::find()->where(['id' => $filters['categories']])->all();
        $categoryNames = array_map(fn($c) => $c->name, $categories);
        $parts[] = implode(', ', $categoryNames);
    }
    
    if (empty($parts)) {
        return "Каталог товаров ($count)";
    }
    
    return implode(' - ', $parts) . " ($count товаров)";
}

/**
 * Форматирование активных фильтров для отображения
 */
protected function formatActiveFilters($filters)
{
    $active = [];
    
    if (!empty($filters['brands'])) {
        foreach ($filters['brands'] as $brandId) {
            $brand = Brand::findOne($brandId);
            if ($brand) {
                $removeFilters = $filters;
                $removeFilters['brands'] = array_diff($removeFilters['brands'], [$brandId]);
                
                $active[] = [
                    'type' => 'brand',
                    'label' => 'Бренд: ' . $brand->name,
                    'removeUrl' => SmartFilter::generateSefUrl($removeFilters)
                ];
            }
        }
    }
    
    if (!empty($filters['price_from']) || !empty($filters['price_to'])) {
        $from = $filters['price_from'] ?? 0;
        $to = $filters['price_to'] ?? '∞';
        
        $removeFilters = $filters;
        unset($removeFilters['price_from'], $removeFilters['price_to']);
        
        $active[] = [
            'type' => 'price',
            'label' => "Цена: {$from} - {$to} BYN",
            'removeUrl' => SmartFilter::generateSefUrl($removeFilters)
        ];
    }
    
    return $active;
}
```

---

### 2. Теги активных фильтров

**Добавить в**: `views/catalog/index.php`

```php
<!-- Активные фильтры (теги) -->
<?php if (!empty($activeFilters)): ?>
    <div class="active-filters-tags">
        <div class="tags-container">
            <?php foreach ($activeFilters as $filter): ?>
                <div class="filter-tag">
                    <span><?= Html::encode($filter['label']) ?></span>
                    <a href="<?= $filter['removeUrl'] ?>" 
                       class="remove-filter"
                       data-ajax="true">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <a href="/catalog/" class="clear-all-filters">
            <i class="bi bi-x-circle"></i>
            Сбросить все
        </a>
    </div>
<?php endif; ?>

<style>
.active-filters-tags {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    margin-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 0.875rem;
}

.remove-filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    background: #000;
    color: #fff;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.2s;
}

.remove-filter:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.clear-all-filters {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #dc2626;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
}

.clear-all-filters:hover {
    color: #b91c1c;
}

@media (max-width: 768px) {
    .active-filters-tags {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .clear-all-filters {
        text-align: center;
        justify-content: center;
    }
}
</style>
```

---

### 3. Schema.org ItemList

**Добавить в**: `CatalogController.php`

```php
/**
 * Регистрация Schema.org ItemList
 */
protected function registerSchemaItemList($products, $totalCount)
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'numberOfItems' => $totalCount,
        'itemListElement' => []
    ];
    
    foreach ($products as $index => $product) {
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Product',
                'name' => $product->name,
                'url' => Yii::$app->request->hostInfo . $product->getUrl(),
                'image' => Yii::$app->request->hostInfo . $product->getMainImageUrl(),
                'brand' => [
                    '@type' => 'Brand',
                    'name' => $product->brand->name
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $product->price,
                    'priceCurrency' => 'BYN',
                    'availability' => $product->stock_status === 'in_stock' 
                        ? 'https://schema.org/InStock' 
                        : 'https://schema.org/OutOfStock',
                    'url' => Yii::$app->request->hostInfo . $product->getUrl()
                ]
            ]
        ];
    }
    
    $this->view->registerMetaTag([
        'name' => 'application/ld+json',
        'content' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    ], 'schema-itemlist');
}
```

---

### 4. Динамическое сужение фильтров

**Обновить**: `CatalogController::getFiltersData()`

```php
/**
 * Получение доступных фильтров (с учетом текущих выборов)
 */
protected function getAvailableFilters($currentFilters = [])
{
    // Базовый запрос
    $baseQuery = Product::find()->where(['is_active' => 1]);
    
    // Кэш ключ с учетом фильтров
    $cacheKey = 'available_filters_' . md5(serialize($currentFilters));
    
    return Yii::$app->cache->getOrSet($cacheKey, function() use ($baseQuery, $currentFilters) {
        
        // Доступные бренды (без учета фильтра по брендам)
        $brandQuery = clone $baseQuery;
        $tempFilters = $currentFilters;
        unset($tempFilters['brands']);
        $brandQuery = $this->applyParsedFilters($brandQuery, $tempFilters);
        
        $availableBrands = Brand::find()
            ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(product.id) as count'])
            ->innerJoin('product', 'product.brand_id = brand.id')
            ->where($brandQuery->where)
            ->groupBy('brand.id')
            ->having(['>', 'count', 0])
            ->orderBy(['brand.name' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Доступные категории (без учета фильтра по категориям)
        $categoryQuery = clone $baseQuery;
        $tempFilters = $currentFilters;
        unset($tempFilters['categories']);
        $categoryQuery = $this->applyParsedFilters($categoryQuery, $tempFilters);
        
        $availableCategories = Category::find()
            ->select(['category.id', 'category.name', 'category.slug', 'COUNT(product.id) as count'])
            ->innerJoin('product', 'product.category_id = category.id')
            ->where($categoryQuery->where)
            ->groupBy('category.id')
            ->having(['>', 'count', 0])
            ->orderBy(['category.name' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Диапазон цен (с учетом всех фильтров)
        $priceQuery = clone $baseQuery;
        $priceQuery = $this->applyParsedFilters($priceQuery, $currentFilters);
        
        $priceRange = $priceQuery
            ->select(['MIN(price) as min', 'MAX(price) as max'])
            ->asArray()
            ->one();
        
        return [
            'brands' => $availableBrands,
            'categories' => $availableCategories,
            'priceRange' => [
                'min' => (float)($priceRange['min'] ?? 0),
                'max' => (float)($priceRange['max'] ?? 1000),
            ],
        ];
    }, 1800); // Кэш на 30 минут
}
```

**Обновить**: `views/catalog/index.php` (sidebar)

```php
<!-- Фильтры с количеством товаров -->
<div class="filter-section">
    <h3>Бренд</h3>
    <?php foreach ($filters['brands'] as $brand): ?>
        <label class="filter-checkbox <?= $brand['count'] == 0 ? 'disabled' : '' ?>">
            <input type="checkbox" 
                   name="brands[]" 
                   value="<?= $brand['id'] ?>"
                   <?= in_array($brand['id'], $activeFilters['brands'] ?? []) ? 'checked' : '' ?>
                   <?= $brand['count'] == 0 ? 'disabled' : '' ?>>
            <span class="checkbox-label">
                <?= Html::encode($brand['name']) ?>
                <span class="count">(<?= $brand['count'] ?>)</span>
            </span>
        </label>
    <?php endforeach; ?>
</div>

<style>
.filter-checkbox.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.filter-checkbox .count {
    color: #666;
    font-size: 0.875rem;
}
</style>
```

---

### 5. rel="prev"/"next" для пагинации

**Добавить в**: `CatalogController.php`

```php
/**
 * Регистрация rel prev/next
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

// Вызвать в actionFilterSef():
$this->registerPaginationLinks(
    $pagination->page + 1,
    $pagination->pageCount,
    $parsedFilters
);
```

---

## 🎯 ИТОГ

После внедрения этих 5 критичных фичей:

**Было**: 6.5/10  
**Станет**: 8.5/10  

**Следующий шаг**: Внедрить Этап 2 (OR логика, Sticky фильтры) → 9/10

---

**Дата**: 01.11.2025, 23:52  
**Автор**: Senior Full-Stack Team
