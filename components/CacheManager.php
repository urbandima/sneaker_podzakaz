<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;

/**
 * CacheManager - Централизованное управление кэшированием
 * 
 * Функции:
 * - Redis/File кэширование с автоопределением
 * - Tagged caching для массовой инвалидации
 * - Специализированные методы для фильтров, продуктов, счётчиков
 * - Статистика использования кэша
 * - Batch операции для производительности
 * 
 * Использование:
 * ```php
 * $filters = CacheManager::getFiltersData($params, function() {
 *     return $this->calculateFilters();
 * });
 * ```
 */
class CacheManager extends Component
{
    /**
     * Префиксы ключей для разных типов кэша
     */
    const PREFIX_FILTERS = 'filters:';
    const PREFIX_PRODUCTS = 'products:';
    const PREFIX_CATALOG = 'catalog:';
    const PREFIX_COUNT = 'count:';
    const PREFIX_SEARCH = 'search:';
    const PREFIX_API = 'api:';
    
    /**
     * Теги для группой инвалидации
     */
    const TAG_FILTERS = 'filters';
    const TAG_PRODUCTS = 'products';
    const TAG_CATALOG = 'catalog';
    const TAG_BRANDS = 'brands';
    const TAG_CATEGORIES = 'categories';
    
    /**
     * Время жизни кэша (секунды)
     */
    const TTL_SHORT = 300;      // 5 минут (счётчики, поиск)
    const TTL_MEDIUM = 1800;    // 30 минут (фильтры, каталог)
    const TTL_LONG = 3600;      // 1 час (справочники, статика)
    const TTL_VERY_LONG = 86400; // 24 часа (редко меняющиеся данные)
    
    /**
     * @var \yii\caching\CacheInterface
     */
    protected static $cache;
    
    /**
     * Инициализация компонента кэша
     */
    protected static function getCache()
    {
        if (self::$cache === null) {
            self::$cache = Yii::$app->cache;
        }
        return self::$cache;
    }
    
    /**
     * Проверка поддержки Redis
     * 
     * @return bool
     */
    public static function isRedisAvailable()
    {
        return self::getCache() instanceof \yii\redis\Cache;
    }
    
    /**
     * ============================================
     * ОСНОВНЫЕ МЕТОДЫ
     * ============================================
     */
    
    /**
     * Получить из кэша или вычислить
     * 
     * @param string $key Ключ кэша
     * @param callable $callback Функция для вычисления значения
     * @param int $duration Время жизни (секунды)
     * @param array $tags Теги для группой инвалидации
     * @return mixed
     */
    public static function getOrSet($key, $callback, $duration = self::TTL_MEDIUM, $tags = [])
    {
        $cache = self::getCache();
        
        if (empty($tags)) {
            return $cache->getOrSet($key, $callback, $duration);
        }
        
        // С тегами
        return $cache->getOrSet(
            $key,
            $callback,
            $duration,
            new TagDependency(['tags' => $tags])
        );
    }
    
    /**
     * Получить значение из кэша
     * 
     * @param string $key
     * @return mixed|false
     */
    public static function get($key)
    {
        return self::getCache()->get($key);
    }
    
    /**
     * Установить значение в кэш
     * 
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @param array $tags
     * @return bool
     */
    public static function set($key, $value, $duration = self::TTL_MEDIUM, $tags = [])
    {
        $cache = self::getCache();
        
        if (empty($tags)) {
            return $cache->set($key, $value, $duration);
        }
        
        return $cache->set(
            $key,
            $value,
            $duration,
            new TagDependency(['tags' => $tags])
        );
    }
    
    /**
     * Удалить из кэша
     * 
     * @param string $key
     * @return bool
     */
    public static function delete($key)
    {
        return self::getCache()->delete($key);
    }
    
    /**
     * ============================================
     * СПЕЦИАЛИЗИРОВАННЫЕ МЕТОДЫ
     * ============================================
     */
    
    /**
     * Кэширование данных фильтров каталога
     * 
     * @param array $params Параметры фильтров
     * @param callable $callback
     * @return array
     */
    public static function getFiltersData($params, $callback)
    {
        $key = self::PREFIX_FILTERS . md5(serialize($params));
        
        return self::getOrSet(
            $key,
            $callback,
            self::TTL_MEDIUM,
            [self::TAG_FILTERS, self::TAG_CATALOG]
        );
    }
    
    /**
     * Кэширование количества товаров для пагинации
     * 
     * @param array $params Параметры запроса
     * @param callable $callback
     * @return int
     */
    public static function getCatalogCount($params, $callback)
    {
        $key = self::PREFIX_COUNT . md5(serialize($params));
        
        return self::getOrSet(
            $key,
            $callback,
            self::TTL_SHORT,
            [self::TAG_CATALOG, self::TAG_PRODUCTS]
        );
    }
    
    /**
     * Кэширование результатов поиска
     * 
     * @param string $query Поисковый запрос
     * @param callable $callback
     * @return array
     */
    public static function getSearchResults($query, $callback)
    {
        $key = self::PREFIX_SEARCH . md5(strtolower($query));
        
        return self::getOrSet(
            $key,
            $callback,
            self::TTL_SHORT,
            [self::TAG_PRODUCTS, self::TAG_CATALOG]
        );
    }
    
    /**
     * Кэширование товаров каталога
     * 
     * @param array $params Параметры (фильтры, сортировка, пагинация)
     * @param callable $callback
     * @return array
     */
    public static function getCatalogProducts($params, $callback)
    {
        $key = self::PREFIX_CATALOG . md5(serialize($params));
        
        return self::getOrSet(
            $key,
            $callback,
            self::TTL_MEDIUM,
            [self::TAG_CATALOG, self::TAG_PRODUCTS]
        );
    }
    
    /**
     * Кэширование данных одного товара
     * 
     * @param int $productId
     * @param callable $callback
     * @return array
     */
    public static function getProduct($productId, $callback)
    {
        $key = self::PREFIX_PRODUCTS . $productId;
        
        return self::getOrSet(
            $key,
            $callback,
            self::TTL_LONG,
            [self::TAG_PRODUCTS]
        );
    }
    
    /**
     * ============================================
     * ИНВАЛИДАЦИЯ КЭША
     * ============================================
     */
    
    /**
     * Инвалидация фильтров (при изменении товаров/брендов/категорий)
     */
    public static function invalidateFilters()
    {
        TagDependency::invalidate(self::getCache(), self::TAG_FILTERS);
        
        Yii::info('Cache invalidated: filters', 'cache');
    }
    
    /**
     * Инвалидация каталога (при изменении товаров)
     */
    public static function invalidateCatalog()
    {
        TagDependency::invalidate(self::getCache(), [
            self::TAG_CATALOG,
            self::TAG_FILTERS,
        ]);
        
        Yii::info('Cache invalidated: catalog + filters', 'cache');
    }
    
    /**
     * Инвалидация товаров (при изменении конкретного товара)
     * 
     * @param int|null $productId Если указан - только этот товар, иначе все
     */
    public static function invalidateProducts($productId = null)
    {
        if ($productId !== null) {
            // Удаляем конкретный товар
            self::delete(self::PREFIX_PRODUCTS . $productId);
        } else {
            // Удаляем все товары
            TagDependency::invalidate(self::getCache(), self::TAG_PRODUCTS);
        }
        
        // Также инвалидируем каталог
        self::invalidateCatalog();
        
        Yii::info('Cache invalidated: products' . ($productId ? " (ID: $productId)" : ' (all)'), 'cache');
    }
    
    /**
     * Инвалидация брендов
     */
    public static function invalidateBrands()
    {
        TagDependency::invalidate(self::getCache(), [
            self::TAG_BRANDS,
            self::TAG_FILTERS,
            self::TAG_CATALOG,
        ]);
        
        Yii::info('Cache invalidated: brands', 'cache');
    }
    
    /**
     * Инвалидация категорий
     */
    public static function invalidateCategories()
    {
        TagDependency::invalidate(self::getCache(), [
            self::TAG_CATEGORIES,
            self::TAG_FILTERS,
            self::TAG_CATALOG,
        ]);
        
        Yii::info('Cache invalidated: categories', 'cache');
    }
    
    /**
     * Полная очистка кэша
     */
    public static function flush()
    {
        self::getCache()->flush();
        
        Yii::info('Cache flushed completely', 'cache');
    }
    
    /**
     * ============================================
     * BATCH ОПЕРАЦИИ
     * ============================================
     */
    
    /**
     * Получить множество значений за один запрос (для Redis)
     * 
     * @param array $keys
     * @return array
     */
    public static function multiGet($keys)
    {
        $cache = self::getCache();
        
        if (method_exists($cache, 'multiGet')) {
            return $cache->multiGet($keys);
        }
        
        // Fallback для других типов кэша
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $cache->get($key);
        }
        return $result;
    }
    
    /**
     * Установить множество значений за один запрос (для Redis)
     * 
     * @param array $items Ассоциативный массив key => value
     * @param int $duration
     * @return array
     */
    public static function multiSet($items, $duration = self::TTL_MEDIUM)
    {
        $cache = self::getCache();
        
        if (method_exists($cache, 'multiSet')) {
            return $cache->multiSet($items, $duration);
        }
        
        // Fallback
        $result = [];
        foreach ($items as $key => $value) {
            $result[$key] = $cache->set($key, $value, $duration);
        }
        return $result;
    }
    
    /**
     * ============================================
     * СТАТИСТИКА И МОНИТОРИНГ
     * ============================================
     */
    
    /**
     * Получить статистику кэша (для Redis)
     * 
     * @return array
     */
    public static function getStats()
    {
        $cache = self::getCache();
        
        $stats = [
            'type' => get_class($cache),
            'redis_available' => self::isRedisAvailable(),
        ];
        
        // Redis статистика
        if (self::isRedisAvailable() && method_exists($cache->redis, 'info')) {
            try {
                $info = $cache->redis->info();
                $stats['redis'] = [
                    'used_memory' => $info['used_memory_human'] ?? 'N/A',
                    'connected_clients' => $info['connected_clients'] ?? 'N/A',
                    'total_keys' => $info['db0']['keys'] ?? 0,
                    'hits' => $info['keyspace_hits'] ?? 0,
                    'misses' => $info['keyspace_misses'] ?? 0,
                    'hit_rate' => self::calculateHitRate($info),
                ];
            } catch (\Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    /**
     * Рассчитать hit rate для Redis
     * 
     * @param array $info
     * @return string
     */
    protected static function calculateHitRate($info)
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        return round(($hits / $total) * 100, 2) . '%';
    }
    
    /**
     * Получить размер кэша (приблизительно)
     * 
     * @return array
     */
    public static function getCacheSize()
    {
        $cache = self::getCache();
        
        if ($cache instanceof \yii\caching\FileCache) {
            $cachePath = $cache->cachePath;
            $size = 0;
            $count = 0;
            
            if (is_dir($cachePath)) {
                $files = glob($cachePath . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $size += filesize($file);
                        $count++;
                    }
                }
            }
            
            return [
                'size' => self::formatBytes($size),
                'count' => $count,
            ];
        }
        
        return [
            'size' => 'N/A',
            'count' => 'N/A',
        ];
    }
    
    /**
     * Форматировать байты в читаемый вид
     * 
     * @param int $bytes
     * @return string
     */
    protected static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * ============================================
     * WARMUP (прогрев кэша)
     * ============================================
     */
    
    /**
     * Прогрев кэша фильтров
     * Используется после инвалидации или при деплое
     */
    public static function warmupFilters()
    {
        // Базовые фильтры без условий
        $callback = function() {
            $controller = new \app\controllers\CatalogController('catalog', Yii::$app);
            return $controller->getFiltersData([]);
        };
        
        self::getFiltersData([], $callback);
        
        Yii::info('Cache warmed up: filters', 'cache');
    }
    
    /**
     * Прогрев популярных страниц каталога
     */
    public static function warmupCatalog()
    {
        // Можно добавить логику прогрева популярных комбинаций фильтров
        Yii::info('Cache warmed up: catalog', 'cache');
    }
}
