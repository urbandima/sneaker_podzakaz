<?php

namespace app\backend\cache;

use yii\caching\CacheInterface;
use app\backend\modules\catalog\models\Product;

/**
 * Кэш товаров
 */
class ProductCache
{
    private CacheInterface $cache;
    private int $duration = 3600; // 1 час

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Получить товар из кэша
     */
    public function get(int $id): ?Product
    {
        $key = $this->getKey($id);
        return $this->cache->get($key) ?: null;
    }

    /**
     * Сохранить товар в кэш
     */
    public function set(Product $product): void
    {
        $key = $this->getKey($product->id);
        $this->cache->set($key, $product, $this->duration);
    }

    /**
     * Удалить товар из кэша
     */
    public function delete(int $id): void
    {
        $key = $this->getKey($id);
        $this->cache->delete($key);
    }

    /**
     * Получить список товаров из кэша
     */
    public function getList(string $categorySlug): ?array
    {
        $key = $this->getListKey($categorySlug);
        return $this->cache->get($key) ?: null;
    }

    /**
     * Сохранить список товаров в кэш
     */
    public function setList(string $categorySlug, array $products): void
    {
        $key = $this->getListKey($categorySlug);
        $this->cache->set($key, $products, $this->duration);
    }

    /**
     * Очистить весь кэш товаров
     */
    public function flush(): void
    {
        $this->cache->flush();
    }

    private function getKey(int $id): string
    {
        return "product:{$id}";
    }

    private function getListKey(string $categorySlug): string
    {
        return "products:list:{$categorySlug}";
    }
}
