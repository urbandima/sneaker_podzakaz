<?php

/**
 * ProductRepository — Репозиторий для работы с товарами
 * 
 * НАЗНАЧЕНИЕ:
 * Централизация всех запросов к БД для модели Product.
 * Инкапсуляция логики выборки, кэширование, оптимизация запросов.
 * 
 * МЕТОДЫ:
 * - createQuery(): базовый запрос для активных товаров
 * - findById(): найти товар по ID
 * - findBySlug(): найти товар по slug
 * - findByBrand(): товары по бренду
 * - findByCategory(): товары по категории
 * - findNewArrivals(): новые поступления
 * - findBestsellers(): бестселлеры
 * - search(): поиск по названию
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * ```php
 * $repository = new ProductRepository();
 * $product = $repository->findBySlug('nike-air-max-90');
 * $products = $repository->findByBrand(5, 20);
 * ```
 * 
 * ОСОБЕННОСТИ:
 * - Eager loading связей для оптимизации
 * - Кэширование результатов
 * - Пагинация
 * - Фильтрация по статусу
 */
namespace app\backend\modules\catalog\repositories;

use Yii;
use app\backend\modules\catalog\models\Product;
use yii\db\ActiveQuery;

/**
 * Репозиторий для работы с товарами
 * Централизует все запросы к БД для Product
 */
class ProductRepository
{
    /**
     * Базовый запрос для активных товаров с eager loading
     */
    public function createQuery(bool $withRelations = true): ActiveQuery
    {
        $query = Product::find()->where(['is_active' => 1]);
        
        if ($withRelations) {
            $query->with(['brand', 'category', 'images', 'sizes', 'colors']);
        }
        
        return $query;
    }

    /**
     * Найти товар по ID
     */
    public function findById(int $id, bool $withRelations = true): ?Product
    {
        $query = Product::find()->where(['id' => $id, 'is_active' => 1]);
        
        if ($withRelations) {
            $query->with(['brand', 'category', 'images', 'sizes', 'colors']);
        }
        
        return $query->one();
    }

    /**
     * Найти товар по slug
     */
    public function findBySlug(string $slug, bool $withRelations = true): ?Product
    {
        $query = Product::find()->where(['slug' => $slug, 'is_active' => 1]);
        
        if ($withRelations) {
            $query->with(['brand', 'category', 'images', 'sizes', 'colors']);
        }
        
        return $query->one();
    }

    /**
     * Найти товары по массиву ID
     */
    public function findByIds(array $ids, bool $withRelations = true): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $query = Product::find()
            ->where(['id' => $ids, 'is_active' => 1]);
        
        if ($withRelations) {
            $query->with(['brand', 'category', 'images', 'sizes', 'colors']);
        }
        
        return $query->all();
    }

    /**
     * Получить популярные товары
     */
    public function findPopular(int $limit = 8): array
    {
        return Product::find()
            ->where(['is_active' => 1])
            ->orderBy(['views_count' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить новинки
     */
    public function findNew(int $limit = 8): array
    {
        return Product::find()
            ->where(['is_active' => 1])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить товары по бренду
     */
    public function findByBrand(int $brandId, ?int $limit = null): array
    {
        $query = Product::find()
            ->where(['brand_id' => $brandId, 'is_active' => 1])
            ->orderBy(['created_at' => SORT_DESC]);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->all();
    }

    /**
     * Получить товары по категории
     */
    public function findByCategory(int $categoryId, ?int $limit = null): array
    {
        $query = Product::find()
            ->where(['category_id' => $categoryId, 'is_active' => 1])
            ->orderBy(['created_at' => SORT_DESC]);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->all();
    }

    /**
     * Получить избранные/хиты
     */
    public function findFeatured(int $limit = 8): array
    {
        return Product::find()
            ->where(['is_active' => 1, 'is_featured' => 1])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить товары со скидкой
     */
    public function findWithDiscount(?int $limit = null): array
    {
        $query = Product::find()
            ->where(['is_active' => 1])
            ->andWhere(['not', ['old_price' => null]])
            ->orderBy(['created_at' => SORT_DESC]);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->all();
    }

    /**
     * Поиск товаров по названию
     */
    public function searchByName(string $query, int $limit = 20): array
    {
        return Product::find()
            ->where(['is_active' => 1])
            ->andWhere(['like', 'name', $query])
            ->orWhere(['like', 'model_name', $query])
            ->limit($limit)
            ->all();
    }

    /**
     * Получить общее количество активных товаров
     */
    public function countActive(): int
    {
        return Product::find()
            ->where(['is_active' => 1])
            ->count();
    }

    /**
     * Получить количество товаров в категории
     */
    public function countByCategory(int $categoryId): int
    {
        return Product::find()
            ->where(['category_id' => $categoryId, 'is_active' => 1])
            ->count();
    }

    /**
     * Получить количество товаров бренда
     */
    public function countByBrand(int $brandId): int
    {
        return Product::find()
            ->where(['brand_id' => $brandId, 'is_active' => 1])
            ->count();
    }
    
    /**
     * Найти похожие товары
     * 
     * @param Product $product Товар для поиска похожих
     * @param int $limit Количество товаров
     * @return array Массив похожих товаров
     */
    public function findSimilarProducts(Product $product, int $limit = 12): array
    {
        // Многоуровневая стратегия поиска похожих товаров
        
        // 1. Сначала ищем товары того же бренда (исключая текущий)
        $brandProducts = Product::find()
            ->where(['brand_id' => $product->brand_id, 'is_active' => 1])
            ->andWhere(['!=', 'id', $product->id])
            ->limit($limit)
            ->all();
            
        if (count($brandProducts) >= $limit) {
            return array_slice($brandProducts, 0, $limit);
        }
        
        // 2. Если не хватает, добавляем товары из той же категории
        $remainingLimit = $limit - count($brandProducts);
        $categoryIds = [];
        
        // Получаем все категории товара (включая родительские)
        if ($product->category) {
            $categoryIds[] = $product->category_id;
            if ($product->category->parent_id) {
                $categoryIds[] = $product->category->parent_id;
            }
        }
        
        $categoryProducts = [];
        if (!empty($categoryIds)) {
            $categoryProducts = Product::find()
                ->where(['category_id' => $categoryIds, 'is_active' => 1])
                ->andWhere(['!=', 'id', $product->id])
                ->andWhere(['not in', 'brand_id', array_column($brandProducts, 'brand_id')])
                ->limit($remainingLimit)
                ->all();
        }
        
        // 3. Если все еще не хватает, добавляем популярные товары в том же ценовом диапазоне
        $remainingLimit = $limit - count($brandProducts) - count($categoryProducts);
        $popularProducts = [];
        
        if ($remainingLimit > 0) {
            $priceRange = 0.3; // 30% диапазон от цены товара
            $minPrice = $product->price * (1 - $priceRange);
            $maxPrice = $product->price * (1 + $priceRange);
            
            $excludedIds = array_merge(
                [$product->id],
                array_column($brandProducts, 'id'),
                array_column($categoryProducts, 'id')
            );
            
            $popularProducts = Product::find()
                ->where(['is_active' => 1])
                ->andWhere(['between', 'price', $minPrice, $maxPrice])
                ->andWhere(['not in', 'id', $excludedIds])
                ->orderBy(['views' => SORT_DESC, 'created_at' => SORT_DESC])
                ->limit($remainingLimit)
                ->all();
        }
        
        // Объединяем все результаты
        $similarProducts = array_merge($brandProducts, $categoryProducts, $popularProducts);
        
        return array_slice($similarProducts, 0, $limit);
    }
}
