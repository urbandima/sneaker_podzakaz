<?php
/**
 * ИСПРАВЛЕНИЯ ДЛЯ PRODUCTION
 * Готовый код для копирования
 */

// ============================================
// 1. EAGER LOADING ДЛЯ ИЗОБРАЖЕНИЙ
// ============================================

// ФАЙЛ: controllers/CatalogController.php

public function actionIndex()
{
    $query = Product::find()
        ->with([
            'brand', 
            'category', 
            'images' => function($q) {
                $q->where(['is_main' => 1])->orWhere(['sort_order' => 0])->limit(1);
            },
            'colors', 
            'sizes'
        ])
        ->where(['is_active' => 1]);

    $query = $this->applyFilters($query);

    $pagination = new Pagination([
        'defaultPageSize' => 24,
        'totalCount' => $this->getCachedCount($query),  // Кэшированный COUNT
    ]);

    $products = $query
        ->offset($pagination->offset)
        ->limit($pagination->limit)
        ->all();

    $filters = $this->getFiltersData();

    return $this->render('index', [
        'products' => $products,
        'pagination' => $pagination,
        'filters' => $filters,
    ]);
}

// ============================================
// 2. КЭШИРОВАННЫЙ COUNT
// ============================================

protected function getCachedCount($query)
{
    $filterParams = Yii::$app->request->queryParams;
    $cacheKey = 'catalog_count_' . md5(serialize($filterParams));
    
    return Yii::$app->cache->getOrSet($cacheKey, function() use ($query) {
        return $query->count();
    }, 300); // 5 минут
}

// ============================================
// 3. КЭШИРОВАНИЕ ФИЛЬТРОВ
// ============================================

protected function getFiltersData($baseCondition = [])
{
    $request = Yii::$app->request;
    $filterParams = [
        'base' => $baseCondition,
        'brands' => $request->get('brands'),
        'categories' => $request->get('categories'),
        'price_from' => $request->get('price_from'),
        'price_to' => $request->get('price_to'),
    ];
    
    $cacheKey = 'filters_data_v2_' . md5(serialize($filterParams));
    
    return Yii::$app->cache->getOrSet($cacheKey, function() use ($baseCondition, $request) {
        $currentFilters = [
            'brands' => $request->get('brands') ? explode(',', $request->get('brands')) : [],
            'categories' => $request->get('categories') ? explode(',', $request->get('categories')) : [],
            'price_from' => $request->get('price_from'),
            'price_to' => $request->get('price_to'),
        ];
        
        // Бренды с количеством
        $brandsQuery = Brand::find()
            ->select(['brand.id', 'brand.name', 'brand.slug', 'COUNT(DISTINCT product.id) as count'])
            ->leftJoin('product', 'product.brand_id = brand.id AND product.is_active = 1')
            ->where(['brand.is_active' => 1]);
        
        if (!empty($currentFilters['categories'])) {
            $brandsQuery->andWhere(['product.category_id' => $currentFilters['categories']]);
        }
        if (!empty($currentFilters['price_from'])) {
            $brandsQuery->andWhere(['>=', 'product.price', $currentFilters['price_from']]);
        }
        if (!empty($currentFilters['price_to'])) {
            $brandsQuery->andWhere(['<=', 'product.price', $currentFilters['price_to']]);
        }
        
        $brands = $brandsQuery
            ->groupBy(['brand.id'])
            ->having(['>', 'count', 0])  // Только с товарами
            ->orderBy(['brand.name' => SORT_ASC])
            ->asArray()
            ->all();

        // Категории с количеством
        $categoriesQuery = Category::find()
            ->select(['category.id', 'category.name', 'category.slug', 'COUNT(DISTINCT product.id) as count'])
            ->leftJoin('product', 'product.category_id = category.id AND product.is_active = 1')
            ->where(['category.is_active' => 1, 'category.parent_id' => null]);
        
        if (!empty($currentFilters['brands'])) {
            $categoriesQuery->andWhere(['product.brand_id' => $currentFilters['brands']]);
        }
        if (!empty($currentFilters['price_from'])) {
            $categoriesQuery->andWhere(['>=', 'product.price', $currentFilters['price_from']]);
        }
        if (!empty($currentFilters['price_to'])) {
            $categoriesQuery->andWhere(['<=', 'product.price', $currentFilters['price_to']]);
        }
        
        $categories = $categoriesQuery
            ->groupBy(['category.id'])
            ->having(['>', 'count', 0])
            ->orderBy(['category.name' => SORT_ASC])
            ->asArray()
            ->all();

        // Диапазон цен
        $priceQuery = Product::find()
            ->select(['MIN(price) as min', 'MAX(price) as max'])
            ->where(['is_active' => 1]);
        
        if (!empty($currentFilters['brands'])) {
            $priceQuery->andWhere(['brand_id' => $currentFilters['brands']]);
        }
        if (!empty($currentFilters['categories'])) {
            $priceQuery->andWhere(['category_id' => $currentFilters['categories']]);
        }
        
        $priceRange = $priceQuery->asArray()->one();

        return [
            'brands' => $brands,
            'categories' => $categories,
            'priceRange' => [
                'min' => (float)($priceRange['min'] ?? 0),
                'max' => (float)($priceRange['max'] ?? 1000),
            ],
        ];
    }, 1800); // 30 минут
}

// ============================================
// 4. ИНВАЛИДАЦИЯ КЭША
// ============================================

// ФАЙЛ: models/Product.php

public function afterSave($insert, $changedAttributes)
{
    parent::afterSave($insert, $changedAttributes);
    
    // Инвалидировать кэш фильтров
    $this->invalidateFiltersCache();
    
    // Инвалидировать кэш похожих товаров
    if (isset($changedAttributes['category_id'])) {
        $this->invalidateSimilarProductsCache();
    }
}

public function afterDelete()
{
    parent::afterDelete();
    $this->invalidateFiltersCache();
    $this->invalidateSimilarProductsCache();
}

protected function invalidateFiltersCache()
{
    $cache = Yii::$app->cache;
    
    // Удаляем паттерн фильтров
    if ($cache instanceof \yii\caching\FileCache) {
        $cachePath = $cache->cachePath;
        $files = glob($cachePath . '/filters_data_*');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

protected function invalidateSimilarProductsCache()
{
    $cache = Yii::$app->cache;
    $cache->delete('similar_products_' . $this->id);
    
    // Удалить для всех товаров той же категории
    if ($cache instanceof \yii\caching\FileCache) {
        $cachePath = $cache->cachePath;
        $files = glob($cachePath . '/similar_products_*');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

// ============================================
// 5. РЕФАКТОРИНГ ФИЛЬТРОВ
// ============================================

protected function applyFilters($query)
{
    $request = Yii::$app->request;
    
    // Конфигурация фильтров
    $filterConfig = [
        'brands' => ['field' => 'brand_id', 'type' => 'array'],
        'categories' => ['field' => 'category_id', 'type' => 'array'],
        'material' => ['field' => 'material', 'type' => 'array'],
        'season' => ['field' => 'season', 'type' => 'array'],
        'gender' => ['field' => 'gender', 'type' => 'array'],
        'price_from' => ['field' => 'price', 'operator' => '>=', 'type' => 'number'],
        'price_to' => ['field' => 'price', 'operator' => '<=', 'type' => 'number'],
        'rating' => ['field' => 'rating', 'operator' => '>=', 'type' => 'number'],
    ];
    
    foreach ($filterConfig as $param => $config) {
        $value = $request->get($param);
        if (!$value) continue;
        
        if ($config['type'] === 'array') {
            $values = is_array($value) ? $value : explode(',', $value);
            $query->andWhere([$config['field'] => $values]);
        } elseif ($config['type'] === 'number') {
            $operator = $config['operator'] ?? '=';
            $query->andWhere([$operator, $config['field'], $value]);
        }
    }
    
    // Специальные фильтры
    if ($request->get('discount_any')) {
        $query->andWhere(['>', 'old_price', 0]);
    }
    
    if ($search = $request->get('search')) {
        $query->andWhere(['like', 'name', $search]);
    }
    
    if ($request->get('in_stock')) {
        $query->andWhere(['stock_status' => 'in_stock']);
    }
    
    // Сортировка
    switch ($request->get('sort', 'popular')) {
        case 'price_asc':
            $query->orderBy(['price' => SORT_ASC]);
            break;
        case 'price_desc':
            $query->orderBy(['price' => SORT_DESC]);
            break;
        case 'new':
            $query->orderBy(['created_at' => SORT_DESC]);
            break;
        case 'rating':
            $query->orderBy(['rating' => SORT_DESC]);
            break;
        default: // popular
            $query->orderBy(['views_count' => SORT_DESC, 'created_at' => SORT_DESC]);
    }
    
    return $query;
}

// ============================================
// 6. ПОХОЖИЕ ТОВАРЫ С КЭШЕМ
// ============================================

protected function getSimilarProducts($product, $limit = 8)
{
    $cacheKey = 'similar_products_' . $product->id;
    
    return Yii::$app->cache->getOrSet($cacheKey, function() use ($product, $limit) {
        return Product::find()
            ->with(['brand', 'images' => function($q) {
                $q->where(['is_main' => 1])->limit(1);
            }])
            ->where(['category_id' => $product->category_id])
            ->andWhere(['!=', 'id', $product->id])
            ->andWhere(['is_active' => 1])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->all();
    }, 3600); // 1 час
}

// ============================================
// 7. ИНКРЕМЕНТ ПРОСМОТРОВ
// ============================================

public function actionProduct($slug)
{
    $product = Product::find()
        ->with(['brand', 'category', 'images', 'sizes', 'colors'])
        ->where(['slug' => $slug, 'is_active' => 1])
        ->one();

    if (!$product) {
        throw new NotFoundHttpException('Товар не найден');
    }

    // Асинхронный инкремент просмотров
    Yii::$app->db->createCommand()
        ->update('product', [
            'views_count' => new \yii\db\Expression('views_count + 1')
        ], ['id' => $product->id])
        ->execute();

    // Похожие товары
    $similarProducts = $this->getSimilarProducts($product);

    // SEO
    $this->view->title = $product->getMetaTitle();
    // ...

    return $this->render('product', [
        'product' => $product,
        'similarProducts' => $similarProducts,
        'isFavorite' => $this->checkIsFavorite($product->id),
    ]);
}

// ============================================
// 8. МИГРАЦИЯ ИНДЕКСОВ
// ============================================

// ФАЙЛ: migrations/m250102_120000_add_catalog_indexes.php

<?php

use yii\db\Migration;

class m250102_120000_add_catalog_indexes extends Migration
{
    public function safeUp()
    {
        // Составной индекс для фильтрации
        $this->createIndex(
            'idx-product-filter',
            '{{%product}}',
            ['is_active', 'brand_id', 'category_id', 'price']
        );
        
        // Индекс для сортировки по дате
        $this->createIndex(
            'idx-product-created',
            '{{%product}}',
            ['created_at']
        );
        
        // Индекс для сортировки по просмотрам
        $this->createIndex(
            'idx-product-views',
            '{{%product}}',
            ['views_count']
        );
        
        // Индекс для поиска
        $this->createIndex(
            'idx-product-name',
            '{{%product}}',
            ['name']
        );
        
        // Индексы для фильтров
        $this->createIndex('idx-product-material', '{{%product}}', ['material']);
        $this->createIndex('idx-product-season', '{{%product}}', ['season']);
        $this->createIndex('idx-product-gender', '{{%product}}', ['gender']);
        $this->createIndex('idx-product-rating', '{{%product}}', ['rating']);
        $this->createIndex('idx-product-stock', '{{%product}}', ['stock_status']);
        
        echo "✓ Созданы индексы для оптимизации каталога\n";
    }

    public function safeDown()
    {
        $this->dropIndex('idx-product-filter', '{{%product}}');
        $this->dropIndex('idx-product-created', '{{%product}}');
        $this->dropIndex('idx-product-views', '{{%product}}');
        $this->dropIndex('idx-product-name', '{{%product}}');
        $this->dropIndex('idx-product-material', '{{%product}}');
        $this->dropIndex('idx-product-season', '{{%product}}');
        $this->dropIndex('idx-product-gender', '{{%product}}');
        $this->dropIndex('idx-product-rating', '{{%product}}');
        $this->dropIndex('idx-product-stock', '{{%product}}');
    }
}
