<?php

namespace app\backend\search\Indexers;

use app\backend\modules\catalog\models\Product;
use app\backend\search\ProductIndex;

/**
 * Индексатор товаров
 */
class ProductIndexer
{
    /**
     * Индексировать все товары
     */
    public function indexAll(): int
    {
        $products = Product::find()
            ->active()
            ->with(['brand', 'category', 'sizes'])
            ->each(100);

        $count = 0;
        foreach ($products as $product) {
            ProductIndex::index($product);
            $count++;
        }

        return $count;
    }

    /**
     * Индексировать один товар
     */
    public function indexOne(Product $product): void
    {
        $product->refresh();
        ProductIndex::index($product);
    }

    /**
     * Переиндексировать товары
     */
    public function reindex(): int
    {
        // Удаляем старый индекс
        try {
            \Yii::$app->elasticsearch->indices()->delete([
                'index' => ProductIndex::INDEX_NAME,
            ]);
        } catch (\Exception $e) {
            // Индекс не существует
        }

        // Создаём новый индекс
        ProductIndex::createIndex();

        // Индексируем все товары
        return $this->indexAll();
    }
}
