<?php

namespace app\infrastructure\services;

use Yii;
use yii\base\Component;

/**
 * ProductionCacheService - кэширование для продакшена
 */
class ProductionCacheService extends Component
{
    private $cache;
    private $redis;
    
    public function init()
    {
        parent::init();
        $this->cache = Yii::$app->cache;
        $this->redis = Yii::$app->redis;
    }
    
    /**
     * Кэширование каталога товаров
     */
    public function cacheCatalog($products, $duration = 3600)
    {
        $key = 'catalog:products:' . md5(serialize($products));
        return $this->cache->set($key, $products, $duration);
    }
    
    /**
     * Кэширование фильтров
     */
    public function cacheFilters($filters, $duration = 7200)
    {
        $key = 'catalog:filters:' . md5(json_encode($filters));
        return $this->cache->set($key, $filters, $duration);
    }
    
    /**
     * Кэширование корзины
     */
    public function cacheCart($userId, $cart, $duration = 1800)
    {
        $key = "cart:user:{$userId}";
        return $this->redis->setex($key, $duration, serialize($cart));
    }
    
    /**
     * Очистка кэша каталога
     */
    public function clearCatalogCache()
    {
        $keys = $this->redis->keys('catalog:*');
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
        return true;
    }
    
    /**
     * Статистика кэша
     */
    public function getCacheStats()
    {
        return [
            'redis_memory' => $this->redis->info('memory'),
            'redis_keys' => $this->redis->dbsize(),
            'cache_hit_rate' => $this->getCacheHitRate()
        ];
    }
    
    private function getCacheHitRate()
    {
        $info = $this->redis->info('stats');
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}
