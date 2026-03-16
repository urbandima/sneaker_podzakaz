<?php

namespace app\backend\search;

/**
 * Сервис поиска товаров
 */
class SearchService
{
    /**
     * Поиск товаров
     */
    public function search(string $query, int $limit = 20): array
    {
        $client = \Yii::$app->elasticsearch;
        
        $params = [
            'index' => ProductIndex::INDEX_NAME,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'fields' => ['name^2', 'description', 'brand_name', 'category_name'],
                                    'type' => 'best_fields',
                                    'fuzziness' => 'AUTO',
                                ],
                            ],
                        ],
                        'filter' => [
                            ['term' => ['is_active' => true]],
                        ],
                    ],
                ],
                'size' => $limit,
            ],
        ];

        $response = $client->search($params);
        
        return $this->parseResults($response);
    }

    /**
     * Автодополнение
     */
    public function autocomplete(string $query, int $limit = 5): array
    {
        $client = \Yii::$app->elasticsearch;
        
        $params = [
            'index' => ProductIndex::INDEX_NAME,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    'name' => [
                                        'query' => $query,
                                        'operator' => 'and',
                                    ],
                                ],
                            ],
                        ],
                        'filter' => [
                            ['term' => ['is_active' => true]],
                        ],
                    ],
                ],
                'size' => $limit,
                '_source' => ['name', 'brand_name', 'price'],
            ],
        ];

        $response = $client->search($params);
        
        return $this->parseAutocomplete($response);
    }

    /**
     * Фильтрация товаров
     */
    public function filter(array $filters, int $page = 1, int $perPage = 24): array
    {
        $client = \Yii::$app->elasticsearch;
        
        $must = [];
        
        // Фильтр по категории
        if (!empty($filters['category_id'])) {
            $must[] = ['term' => ['category_id' => $filters['category_id']]];
        }
        
        // Фильтр по бренду
        if (!empty($filters['brand_id'])) {
            $must[] = ['term' => ['brand_id' => $filters['brand_id']]];
        }
        
        // Фильтр по цене
        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $range = [];
            if (!empty($filters['price_min'])) {
                $range['gte'] = $filters['price_min'];
            }
            if (!empty($filters['price_max'])) {
                $range['lte'] = $filters['price_max'];
            }
            $must[] = ['range' => ['price' => $range]];
        }

        $params = [
            'index' => ProductIndex::INDEX_NAME,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'filter' => [
                            ['term' => ['is_active' => true]],
                        ],
                    ],
                ],
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
            ],
        ];

        $response = $client->search($params);
        
        return $this->parseResults($response);
    }

    private function parseResults(array $response): array
    {
        $results = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $results[] = [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'data' => $hit['_source'],
            ];
        }
        return $results;
    }

    private function parseAutocomplete(array $response): array
    {
        $results = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $results[] = [
                'id' => $hit['_id'],
                'name' => $hit['_source']['name'],
                'brand' => $hit['_source']['brand_name'] ?? null,
                'price' => $hit['_source']['price'] ?? null,
            ];
        }
        return $results;
    }
}
