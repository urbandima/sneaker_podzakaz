<?php

namespace app\backend\search;

use app\backend\modules\catalog\models\Product;

/**
 * Индекс товаров для Elasticsearch
 */
class ProductIndex
{
    const INDEX_NAME = 'products';

    /**
     * Создать индекс
     */
    public static function createIndex(): void
    {
        $client = \Yii::$app->elasticsearch;
        
        $params = [
            'index' => self::INDEX_NAME,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'russian',
                        ],
                        'sku' => [
                            'type' => 'keyword',
                        ],
                        'description' => [
                            'type' => 'text',
                            'analyzer' => 'russian',
                        ],
                        'price' => [
                            'type' => 'float',
                        ],
                        'brand_id' => [
                            'type' => 'integer',
                        ],
                        'brand_name' => [
                            'type' => 'keyword',
                        ],
                        'category_id' => [
                            'type' => 'integer',
                        ],
                        'category_name' => [
                            'type' => 'keyword',
                        ],
                        'sizes' => [
                            'type' => 'keyword',
                        ],
                        'is_active' => [
                            'type' => 'boolean',
                        ],
                        'created_at' => [
                            'type' => 'date',
                        ],
                    ],
                ],
            ],
        ];

        $client->indices()->create($params);
    }

    /**
     * Индексировать товар
     */
    public static function index(Product $product): void
    {
        $client = \Yii::$app->elasticsearch;
        
        $params = [
            'index' => self::INDEX_NAME,
            'id' => $product->id,
            'body' => [
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $product->description,
                'price' => (float) $product->price,
                'brand_id' => $product->brand_id,
                'brand_name' => $product->brand?->name,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name,
                'sizes' => array_column($product->sizes, 'size'),
                'is_active' => $product->is_active,
                'created_at' => $product->created_at,
            ],
        ];

        $client->index($params);
    }

    /**
     * Удалить товар из индекса
     */
    public static function delete(int $productId): void
    {
        $client = \Yii::$app->elasticsearch;
        
        $params = [
            'index' => self::INDEX_NAME,
            'id' => $productId,
        ];

        $client->delete($params);
    }
}
