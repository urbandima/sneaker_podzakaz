<?php

/**
 * CatalogFiltersTrait — Методы фильтрации каталога
 * 
 * НАЗНАЧЕНИЕ:
 * Логика фильтрации товаров: применение фильтров, получение данных
 * для фильтров, кэширование результатов фильтрации.
 * 
 * ФУНКЦИИ:
 * - Применение фильтров к запросу (applyFilters)
 * - Получение данных для фильтров (getFiltersData)
 * - Кэшированный COUNT запрос (getCachedCount)
 * - Проверка обхода кэша (shouldBypassCatalogCache)
 * - Нормализация списка фильтров (normalizeFilterList)
 * 
 * СВЯЗИ:
 * - Product (модель товара)
 * - FilterBuilder (построитель фильтров)
 * - CacheManager (менеджер кэша)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * Подключить в CatalogController через "use CatalogFiltersTrait;"
 * 
 * ОСОБЕННОСТИ:
 * - Делегирует логику в FilterBuilder для единого источника истины
 * - Кэширование COUNT запросов для производительности
 * - Поддержка фильтрации по характеристикам
 */
namespace app\backend\shared\traits;

use Yii;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\services\Catalog\FilterBuilder;
use app\backend\shared\components\CacheManager;

trait CatalogFiltersTrait
{
    /**
     * Применить фильтры к запросу товаров
     * ИСПРАВЛЕНО: Принимает фильтры как параметр вместо чтения из $_GET
     * 
     * @param \yii\db\ActiveQuery $query
     * @param array|null $filters Фильтры для применения (если null - читает из request)
     * @return \yii\db\ActiveQuery
     */
    protected function applyFilters($query, ?array $filters = null)
    {
        $request = Yii::$app->request;

        // Если фильтры не переданы явно - собираем из request (backward compatibility)
        if ($filters === null) {
            $filters = $this->collectFiltersFromRequest($request);
        }

        // Фильтр по наличию
        if (!empty($filters['stock_status'])) {
            $query->andWhere(['stock_status' => $filters['stock_status']]);
        }

        // Поиск
        if (!empty($filters['search'])) {
            $query->andWhere(['like', 'name', $filters['search']]);
        }
        
        // Применяем через FilterBuilder
        FilterBuilder::applyFiltersToProductQuery($query, $filters);

        // Сортировка
        $this->applySorting($query, $filters['sort'] ?? 'popular');

        return $query;
    }

    /**
     * Собирает фильтры из request (для backward compatibility)
     * 
     * @param \yii\web\Request $request
     * @return array
     */
    private function collectFiltersFromRequest($request): array
    {
        $brands = $request->get('brands');
        $categories = $request->get('categories');
        $sizes = $request->get('sizes');
        $priceFrom = $request->get('price_from');
        $priceTo = $request->get('price_to');
        $conditions = $request->get('conditions');
        
        $filters = [
            'brands' => $brands ? (is_array($brands) ? $brands : explode(',', $brands)) : [],
            'categories' => $categories ? (is_array($categories) ? $categories : explode(',', $categories)) : [],
            'sizes' => $sizes ? (is_array($sizes) ? $sizes : explode(',', $sizes)) : [],
            'size_system' => $request->get('size_system', 'eu'),
            'price_from' => $priceFrom,
            'price_to' => $priceTo,
            'colors' => $request->get('colors'),
            'discount_any' => $request->get('discount_any'),
            'discount_range' => $request->get('discount_range'),
            'rating' => $request->get('rating'),
            'conditions' => $conditions ? (is_array($conditions) ? $conditions : explode(',', $conditions)) : [],
            'stock_status' => $request->get('stock_status'),
            'search' => $request->get('search'),
            'sort' => $request->get('sort', 'popular'),
        ];
        
        // Характеристики
        foreach ($request->queryParams as $key => $value) {
            if (strpos($key, 'char_') === 0 && !empty($value)) {
                $filters[$key] = is_array($value) ? $value : explode(',', $value);
            }
        }
        
        return $filters;
    }

    /**
     * Применить сортировку к запросу
     * 
     * @param \yii\db\ActiveQuery $query
     * @param string $sortBy
     * @return \yii\db\ActiveQuery
     */
    protected function applySorting($query, string $sortBy)
    {
        switch ($sortBy) {
            case 'price_asc':
                $query->addSelect([
                    'min_price' => '(SELECT MIN(price_byn) FROM product_size WHERE product_size.product_id = product.id AND product_size.is_available = true AND product_size.price_byn > 0)'
                ]);
                $query->andWhere([
                    'product.id' => new \yii\db\Expression(
                        'SELECT DISTINCT product_id FROM product_size WHERE is_available = true AND price_byn > 0'
                    )
                ]);
                $query->orderBy(['min_price' => SORT_ASC]);
                break;
            case 'price_desc':
                $query->addSelect([
                    'max_price' => '(SELECT MAX(price_byn) FROM product_size WHERE product_size.product_id = product.id AND product_size.is_available = true AND product_size.price_byn > 0)'
                ]);
                $query->andWhere([
                    'product.id' => new \yii\db\Expression(
                        'SELECT DISTINCT product_id FROM product_size WHERE is_available = true AND price_byn > 0'
                    )
                ]);
                $query->orderBy(['max_price' => SORT_DESC]);
                break;
            case 'new':
                $query->orderBy(['created_at' => SORT_DESC]);
                break;
            case 'rating':
                $query->orderBy(['rating' => SORT_DESC]);
                break;
            case 'discount':
                $query->addSelect(['discount_percent' => 'CASE WHEN old_price > 0 THEN ROUND((old_price - price) / old_price * 100) ELSE 0 END']);
                $query->orderBy(['discount_percent' => SORT_DESC]);
                break;
            case 'popular':
            default:
                $query->orderBy(['views_count' => SORT_DESC]);
                break;
        }
        
        return $query;
    }

    /**
     * Получение данных для фильтров с учетом текущих фильтров (УМНЫЙ ФИЛЬТР + КЭШ)
     * 
     * @param array $currentFiltersOrBaseCondition
     * @return array
     */
    protected function getFiltersData($currentFiltersOrBaseCondition = [])
    {
        // Определяем, что передано - currentFilters или baseCondition
        $isCurrentFilters = isset($currentFiltersOrBaseCondition['brands']) || 
                           isset($currentFiltersOrBaseCondition['categories']) ||
                           isset($currentFiltersOrBaseCondition['sizes']) ||
                           isset($currentFiltersOrBaseCondition['price_from']);
        
        if ($isCurrentFilters) {
            $currentFilters = $currentFiltersOrBaseCondition;
            $baseCondition = [];
        } else {
            $baseCondition = $currentFiltersOrBaseCondition;
            $request = Yii::$app->request;
            $currentFilters = [
                'brands' => $request->get('brands') ? (is_array($request->get('brands')) ? $request->get('brands') : explode(',', $request->get('brands'))) : [],
                'categories' => $request->get('categories') ? (is_array($request->get('categories')) ? $request->get('categories') : explode(',', $request->get('categories'))) : [],
                'sizes' => $request->get('sizes') ? (is_array($request->get('sizes')) ? $request->get('sizes') : explode(',', $request->get('sizes'))) : [],
                'size_system' => $request->get('size_system', 'eu'),
                'price_from' => $request->get('price_from'),
                'price_to' => $request->get('price_to'),
                'colors' => $request->get('colors') ? (is_array($request->get('colors')) ? $request->get('colors') : explode(',', $request->get('colors'))) : [],
            ];
            
            // Характеристики (формат: char_{id} => [value_ids])
            foreach ($request->queryParams as $key => $value) {
                if (strpos($key, 'char_') === 0 && !empty($value)) {
                    $currentFilters[$key] = is_array($value) ? $value : explode(',', $value);
                }
            }
        }
        
        // Используем FilterBuilder для построения всех фильтров
        return FilterBuilder::buildFilters($currentFilters, $baseCondition);
    }

    /**
     * Кэшированный COUNT для пагинации (оптимизация)
     * 
     * @param \yii\db\ActiveQuery $query
     * @return int
     */
    protected function getCachedCount($query)
    {
        $filterParams = Yii::$app->request->queryParams;
        
        return CacheManager::getCatalogCount($filterParams, function() use ($query) {
            return $query->count();
        });
    }

    /**
     * Определяет, нужно ли обходить кэш каталога
     * 
     * @return bool
     */
    protected function shouldBypassCatalogCache(): bool
    {
        $request = Yii::$app->request;

        // Принудительное отключение через query/header (для QA/DEV)
        if ($request->get('disable_cache') === '1' || $request->headers->get('X-Bypass-Cache') === '1') {
            return true;
        }

        // Поисковые запросы редко повторяются, нет смысла кэшировать
        if ($request->get('search')) {
            return true;
        }

        return false;
    }

    /**
     * Приводит параметр фильтра (строка/список) к массиву значений.
     * 
     * @param mixed $value
     * @return array
     */
    private function normalizeFilterList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value)
            ? $value
            : explode(',', (string)$value);

        return array_values(array_filter(
            array_map(static fn($item) => trim((string)$item), $values),
            static fn($item) => $item !== ''
        ));
    }

    /**
     * Инвалидация кэша фильтров
     */
    public static function invalidateFiltersCache()
    {
        CacheManager::invalidateFilters();
    }
    
    /**
     * Инвалидация всего кэша каталога
     */
    public static function invalidateCatalogCache()
    {
        CacheManager::invalidateCatalog();
    }
}
