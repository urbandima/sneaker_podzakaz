<?php

namespace app\backend\cache;

use yii\caching\CacheInterface;
use app\backend\modules\catalog\models\Category;

/**
 * Кэш категорий
 */
class CategoryCache
{
    private CacheInterface $cache;
    private int $duration = 86400; // 24 часа

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Получить дерево категорий
     */
    public function getTree(): ?array
    {
        return $this->cache->get('category:tree') ?: null;
    }

    /**
     * Сохранить дерево категорий
     */
    public function setTree(array $tree): void
    {
        $this->cache->set('category:tree', $tree, $this->duration);
    }

    /**
     * Получить категорию по slug
     */
    public function getBySlug(string $slug): ?Category
    {
        $key = "category:slug:{$slug}";
        return $this->cache->get($key) ?: null;
    }

    /**
     * Сохранить категорию
     */
    public function setBySlug(Category $category): void
    {
        $key = "category:slug:{$category->slug}";
        $this->cache->set($key, $category, $this->duration);
    }

    /**
     * Очистить кэш категорий
     */
    public function flush(): void
    {
        $this->cache->delete('category:tree');
    }
}
