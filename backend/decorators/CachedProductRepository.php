<?php

namespace app\backend\decorators;

use app\backend\modules\catalog\repositories\ProductRepository;
use app\backend\modules\catalog\models\Product;
use app\backend\cache\ProductCache;

/**
 * Декоратор репозитория товаров с кэшем
 */
class CachedProductRepository
{
    private ProductRepository $repository;
    private ProductCache $cache;

    public function __construct(ProductRepository $repository, ProductCache $cache)
    {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Получить товар (с кэшем)
     */
    public function find(int $id): ?Product
    {
        // Проверяем кэш
        $product = $this->cache->get($id);
        
        if ($product) {
            return $product;
        }

        // Получаем из базы
        $product = $this->repository->find($id);
        
        if ($product) {
            $this->cache->set($product);
        }

        return $product;
    }

    /**
     * Получить список товаров (с кэшем)
     */
    public function findByCategory(string $categorySlug): array
    {
        // Проверяем кэш
        $products = $this->cache->getList($categorySlug);
        
        if ($products !== null) {
            return $products;
        }

        // Получаем из базы
        $products = $this->repository->findByCategory($categorySlug);
        
        // Сохраняем в кэш
        $this->cache->setList($categorySlug, $products);

        return $products;
    }

    /**
     * Сохранить товар (очистить кэш)
     */
    public function save(Product $product): bool
    {
        $result = $this->repository->save($product);
        
        if ($result) {
            $this->cache->delete($product->id);
        }

        return $result;
    }
}
