<?php

namespace app\repositories;

use Yii;
use app\models\Product;
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
     * Получить похожие товары с многоуровневой стратегией поиска
     * 
     * Стратегия поиска (в порядке приоритета):
     * 1. Товары из related_products_json
     * 2. Товары той же серии (series_name)
     * 3. Товары того же бренда и категории
     * 4. Товары той же категории
     * 5. Любые активные товары
     * 
     * @param Product $product Текущий товар
     * @param int $limit Максимальное количество товаров
     * @return Product[]
     */
    public function findSimilarProducts(Product $product, int $limit = 12): array
    {
        $allRelatedProducts = [];
        $seenIds = [$product->id];
        
        // 1. Товары из related_products_json
        if (!empty($product->related_products_json)) {
            $relatedIds = json_decode($product->related_products_json, true);
            if (!empty($relatedIds) && is_array($relatedIds)) {
                $relatedProducts = Product::find()
                    ->where(['id' => $relatedIds, 'is_active' => 1])
                    ->limit(50)
                    ->all();
                $allRelatedProducts = array_merge($allRelatedProducts, $relatedProducts);
            }
        }
        
        // 2. По series_name
        if (!empty($product->series_name) && count($allRelatedProducts) < $limit) {
            $seriesProducts = Product::find()
                ->where(['series_name' => $product->series_name, 'is_active' => 1])
                ->andWhere(['!=', 'id', $product->id])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $seriesProducts);
        }
        
        // 3. Товары того же бренда и категории
        if (count($allRelatedProducts) < $limit && $product->brand_id) {
            $brandProducts = Product::find()
                ->where([
                    'brand_id' => $product->brand_id,
                    'category_id' => $product->category_id,
                    'is_active' => 1
                ])
                ->andWhere(['!=', 'id', $product->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $brandProducts);
        }
        
        // 4. Товары той же категории
        if (count($allRelatedProducts) < $limit && $product->category_id) {
            $categoryProducts = Product::find()
                ->where(['category_id' => $product->category_id, 'is_active' => 1])
                ->andWhere(['!=', 'id', $product->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $categoryProducts);
        }
        
        // 5. Любые активные товары (fallback)
        if (count($allRelatedProducts) < $limit) {
            $anyProducts = Product::find()
                ->where(['is_active' => 1])
                ->andWhere(['!=', 'id', $product->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
            $allRelatedProducts = array_merge($allRelatedProducts, $anyProducts);
        }
        
        // Удаляем дубликаты
        $uniqueProducts = [];
        foreach ($allRelatedProducts as $prod) {
            if (!in_array($prod->id, $seenIds)) {
                $uniqueProducts[] = $prod;
                $seenIds[] = $prod->id;
                
                if (count($uniqueProducts) >= $limit) {
                    break;
                }
            }
        }
        
        return $uniqueProducts;
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
}
