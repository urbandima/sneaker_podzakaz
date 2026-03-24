<?php

namespace app\infrastructure\services;

use Yii;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use app\backend\modules\catalog\models\Product;

/**
 * ElasticsearchService - Сервис для работы с Elasticsearch
 * 
 * НАЗНАЧЕНИЕ:
 * - Индексация товаров в Elasticsearch
 * - Полнотекстовый поиск с поддержкой морфологии
 * - Фасетный поиск (фильтры по брендам, категориям, ценам)
 * - Автодополнение и подсказки
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * $esService = new ElasticsearchService();
 * $results = $esService->search('nike air max', ['brand' => 'Nike']);
 */
class ElasticsearchService
{
    /** @var Client */
    private $client;
    
    /** @var string Название индекса */
    private $indexName = 'products';
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $hosts = [
            env('ELASTICSEARCH_HOST', 'localhost:9200')
        ];
        
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }
    
    /**
     * Создать индекс для товаров
     * 
     * @return bool
     */
    public function createIndex(): bool
    {
        try {
            // Проверяем существование индекса
            if ($this->client->indices()->exists(['index' => $this->indexName])->asBool()) {
                Yii::info("Индекс {$this->indexName} уже существует", 'elasticsearch');
                return true;
            }
            
            // Создаём индекс с маппингом
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                        'analysis' => [
                            'analyzer' => [
                                'russian_analyzer' => [
                                    'type' => 'custom',
                                    'tokenizer' => 'standard',
                                    'filter' => ['lowercase', 'russian_stop', 'russian_stemmer']
                                ]
                            ],
                            'filter' => [
                                'russian_stop' => [
                                    'type' => 'stop',
                                    'stopwords' => '_russian_'
                                ],
                                'russian_stemmer' => [
                                    'type' => 'stemmer',
                                    'language' => 'russian'
                                ]
                            ]
                        ]
                    ],
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'russian_analyzer',
                                'fields' => [
                                    'keyword' => ['type' => 'keyword'],
                                    'suggest' => ['type' => 'completion']
                                ]
                            ],
                            'slug' => ['type' => 'keyword'],
                            'description' => [
                                'type' => 'text',
                                'analyzer' => 'russian_analyzer'
                            ],
                            'brand_name' => [
                                'type' => 'text',
                                'analyzer' => 'russian_analyzer',
                                'fields' => [
                                    'keyword' => ['type' => 'keyword']
                                ]
                            ],
                            'brand_id' => ['type' => 'integer'],
                            'category_name' => [
                                'type' => 'text',
                                'analyzer' => 'russian_analyzer',
                                'fields' => [
                                    'keyword' => ['type' => 'keyword']
                                ]
                            ],
                            'category_id' => ['type' => 'integer'],
                            'price' => ['type' => 'float'],
                            'old_price' => ['type' => 'float'],
                            'discount_percent' => ['type' => 'integer'],
                            'stock_status' => ['type' => 'keyword'],
                            'is_active' => ['type' => 'boolean'],
                            'rating' => ['type' => 'float'],
                            'reviews_count' => ['type' => 'integer'],
                            'main_image_url' => ['type' => 'keyword'],
                            'colors' => ['type' => 'keyword'],
                            'sizes' => ['type' => 'keyword'],
                            'created_at' => ['type' => 'date'],
                            'updated_at' => ['type' => 'date']
                        ]
                    ]
                ]
            ];
            
            $response = $this->client->indices()->create($params);
            
            Yii::info("Индекс {$this->indexName} создан успешно", 'elasticsearch');
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка создания индекса: " . $e->getMessage(), 'elasticsearch');
            return false;
        }
    }
    
    /**
     * Индексировать товар
     * 
     * @param Product $product
     * @return bool
     */
    public function indexProduct(Product $product): bool
    {
        try {
            $params = [
                'index' => $this->indexName,
                'id' => $product->id,
                'body' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->description,
                    'brand_name' => $product->brand_name ?? '',
                    'brand_id' => $product->brand_id,
                    'category_name' => $product->category->name ?? '',
                    'category_id' => $product->category_id,
                    'price' => (float) $product->price,
                    'old_price' => $product->old_price ? (float) $product->old_price : null,
                    'discount_percent' => $product->getDiscountPercent(),
                    'stock_status' => $product->stock_status,
                    'is_active' => (bool) $product->is_active,
                    'rating' => $product->rating ? (float) $product->rating : 0,
                    'reviews_count' => $product->reviews_count ?? 0,
                    'main_image_url' => $product->getMainImageUrl(),
                    'colors' => $this->getProductColors($product),
                    'sizes' => $this->getProductSizes($product),
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ]
            ];
            
            $this->client->index($params);
            
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка индексации товара #{$product->id}: " . $e->getMessage(), 'elasticsearch');
            return false;
        }
    }
    
    /**
     * Индексировать все товары
     * 
     * @return array ['success' => int, 'failed' => int]
     */
    public function indexAllProducts(): array
    {
        $success = 0;
        $failed = 0;
        
        $products = Product::find()
            ->with(['brand', 'category', 'colors', 'sizes'])
            ->batch(100);
        
        foreach ($products as $batch) {
            foreach ($batch as $product) {
                if ($this->indexProduct($product)) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }
        
        Yii::info("Индексация завершена: успешно {$success}, ошибок {$failed}", 'elasticsearch');
        
        return ['success' => $success, 'failed' => $failed];
    }
    
    /**
     * Поиск товаров
     * 
     * @param string $query Поисковый запрос
     * @param array $filters Фильтры ['brand_id' => 1, 'category_id' => 2, 'price_min' => 1000, 'price_max' => 5000]
     * @param int $from Смещение
     * @param int $size Количество результатов
     * @return array ['hits' => array, 'total' => int, 'aggregations' => array]
     */
    public function search(string $query, array $filters = [], int $from = 0, int $size = 20): array
    {
        try {
            $must = [];
            $filter = [];
            
            // Полнотекстовый поиск
            if (!empty($query)) {
                $must[] = [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['name^3', 'brand_name^2', 'description'],
                        'type' => 'best_fields',
                        'fuzziness' => 'AUTO'
                    ]
                ];
            }
            
            // Фильтр по активности
            $filter[] = ['term' => ['is_active' => true]];
            
            // Фильтр по бренду
            if (!empty($filters['brand_id'])) {
                $filter[] = ['term' => ['brand_id' => $filters['brand_id']]];
            }
            
            // Фильтр по категории
            if (!empty($filters['category_id'])) {
                $filter[] = ['term' => ['category_id' => $filters['category_id']]];
            }
            
            // Фильтр по цене
            if (isset($filters['price_min']) || isset($filters['price_max'])) {
                $priceFilter = ['range' => ['price' => []]];
                if (isset($filters['price_min'])) {
                    $priceFilter['range']['price']['gte'] = (float) $filters['price_min'];
                }
                if (isset($filters['price_max'])) {
                    $priceFilter['range']['price']['lte'] = (float) $filters['price_max'];
                }
                $filter[] = $priceFilter;
            }
            
            // Фильтр по наличию
            if (!empty($filters['in_stock'])) {
                $filter[] = ['term' => ['stock_status' => 'in_stock']];
            }
            
            // Формируем запрос
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'from' => $from,
                    'size' => $size,
                    'query' => [
                        'bool' => [
                            'must' => $must,
                            'filter' => $filter
                        ]
                    ],
                    'sort' => [
                        '_score' => ['order' => 'desc'],
                        'created_at' => ['order' => 'desc']
                    ],
                    // Фасетный поиск (агрегации)
                    'aggs' => [
                        'brands' => [
                            'terms' => ['field' => 'brand_name.keyword', 'size' => 50]
                        ],
                        'categories' => [
                            'terms' => ['field' => 'category_name.keyword', 'size' => 50]
                        ],
                        'price_ranges' => [
                            'range' => [
                                'field' => 'price',
                                'ranges' => [
                                    ['to' => 5000],
                                    ['from' => 5000, 'to' => 10000],
                                    ['from' => 10000, 'to' => 20000],
                                    ['from' => 20000]
                                ]
                            ]
                        ],
                        'price_stats' => [
                            'stats' => ['field' => 'price']
                        ]
                    ]
                ]
            ];
            
            $response = $this->client->search($params);
            
            // Извлекаем результаты
            $hits = [];
            foreach ($response['hits']['hits'] as $hit) {
                $hits[] = array_merge($hit['_source'], ['score' => $hit['_score']]);
            }
            
            return [
                'hits' => $hits,
                'total' => $response['hits']['total']['value'],
                'aggregations' => $response['aggregations'] ?? []
            ];
            
        } catch (\Exception $e) {
            Yii::error("Ошибка поиска в Elasticsearch: " . $e->getMessage(), 'elasticsearch');
            return ['hits' => [], 'total' => 0, 'aggregations' => []];
        }
    }
    
    /**
     * Автодополнение (suggest)
     * 
     * @param string $query
     * @param int $size
     * @return array
     */
    public function suggest(string $query, int $size = 10): array
    {
        try {
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'suggest' => [
                        'product-suggest' => [
                            'prefix' => $query,
                            'completion' => [
                                'field' => 'name.suggest',
                                'size' => $size,
                                'skip_duplicates' => true
                            ]
                        ]
                    ]
                ]
            ];
            
            $response = $this->client->search($params);
            
            $suggestions = [];
            foreach ($response['suggest']['product-suggest'][0]['options'] as $option) {
                $suggestions[] = $option['text'];
            }
            
            return $suggestions;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка автодополнения: " . $e->getMessage(), 'elasticsearch');
            return [];
        }
    }
    
    /**
     * Удалить товар из индекса
     * 
     * @param int $productId
     * @return bool
     */
    public function deleteProduct(int $productId): bool
    {
        try {
            $params = [
                'index' => $this->indexName,
                'id' => $productId
            ];
            
            $this->client->delete($params);
            
            return true;
            
        } catch (\Exception $e) {
            Yii::error("Ошибка удаления товара #{$productId}: " . $e->getMessage(), 'elasticsearch');
            return false;
        }
    }
    
    /**
     * Получить цвета товара
     * 
     * @param Product $product
     * @return array
     */
    private function getProductColors(Product $product): array
    {
        if (!$product->isRelationPopulated('colors')) {
            return [];
        }
        
        $colors = [];
        foreach ($product->colors as $color) {
            $colors[] = $color->name;
        }
        
        return $colors;
    }
    
    /**
     * Получить размеры товара
     * 
     * @param Product $product
     * @return array
     */
    private function getProductSizes(Product $product): array
    {
        if (!$product->isRelationPopulated('sizes')) {
            return [];
        }
        
        $sizes = [];
        foreach ($product->sizes as $size) {
            if ($size->is_available) {
                $sizes[] = $size->size_value;
            }
        }
        
        return $sizes;
    }
}
