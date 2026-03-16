<?php

/**
 * ApiDocController — Генерация OpenAPI документации
 * 
 * НАЗНАЧЕНИЕ:
 * Автоматическая генерация Swagger/OpenAPI документации для REST API.
 * 
 * ENDPOINTS:
 * - /api/doc - Swagger UI
 * - /api/doc/json - OpenAPI JSON спецификация
 */
namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class DocController extends Controller
{
    public $layout = false;
    
    /**
     * Swagger UI страница
     */
    public function actionIndex()
    {
        return $this->render('@app/api/views/doc/swagger', [
            'specUrl' => '/api/doc/json',
        ]);
    }
    
    /**
     * OpenAPI JSON спецификация
     */
    public function actionJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return $this->generateOpenApiSpec();
    }
    
    /**
     * Генерация OpenAPI спецификации
     */
    private function generateOpenApiSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Sneaker Store API',
                'description' => 'API для магазина оригинальных кроссовок',
                'version' => '1.0.0',
                'contact' => [
                    'email' => 'support@sneaker-store.by',
                ],
            ],
            'servers' => [
                [
                    'url' => Yii::$app->request->hostInfo,
                    'description' => 'Production server',
                ],
            ],
            'paths' => [
                // Catalog API
                '/api/products' => [
                    'get' => [
                        'summary' => 'Получить список товаров',
                        'tags' => ['Catalog'],
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'description' => 'Номер страницы',
                                'schema' => ['type' => 'integer', 'default' => 1],
                            ],
                            [
                                'name' => 'per-page',
                                'in' => 'query',
                                'description' => 'Товаров на странице',
                                'schema' => ['type' => 'integer', 'default' => 24],
                            ],
                            [
                                'name' => 'brands[]',
                                'in' => 'query',
                                'description' => 'Фильтр по брендам',
                                'schema' => ['type' => 'array', 'items' => ['type' => 'integer']],
                            ],
                            [
                                'name' => 'categories[]',
                                'in' => 'query',
                                'description' => 'Фильтр по категориям',
                                'schema' => ['type' => 'array', 'items' => ['type' => 'integer']],
                            ],
                            [
                                'name' => 'sort',
                                'in' => 'query',
                                'description' => 'Сортировка',
                                'schema' => [
                                    'type' => 'string',
                                    'enum' => ['popular', 'price_asc', 'price_desc', 'new', 'rating'],
                                    'default' => 'popular',
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Список товаров',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            '$ref' => '#/components/schemas/ProductListResponse',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/api/products/{id}' => [
                    'get' => [
                        'summary' => 'Получить товар по ID',
                        'tags' => ['Catalog'],
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'description' => 'ID товара',
                                'schema' => ['type' => 'integer'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Товар',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Product'],
                                    ],
                                ],
                            ],
                            '404' => [
                                'description' => 'Товар не найден',
                            ],
                        ],
                    ],
                ],
                
                // Cart API
                '/cart/add' => [
                    'post' => [
                        'summary' => 'Добавить товар в корзину',
                        'tags' => ['Cart'],
                        'security' => [['csrf' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['product_id', 'quantity'],
                                        'properties' => [
                                            'product_id' => ['type' => 'integer', 'description' => 'ID товара'],
                                            'quantity' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 99],
                                            'size' => ['type' => 'string', 'description' => 'Размер'],
                                            'color' => ['type' => 'string', 'description' => 'Цвет'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Товар добавлен',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/CartResponse'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Ошибка валидации'],
                        ],
                    ],
                ],
                '/cart/count' => [
                    'get' => [
                        'summary' => 'Получить количество товаров в корзине',
                        'tags' => ['Cart'],
                        'responses' => [
                            '200' => [
                                'description' => 'Количество товаров',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'count' => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                
                // Checkout API
                '/checkout/create' => [
                    'post' => [
                        'summary' => 'Создать заказ',
                        'tags' => ['Checkout'],
                        'security' => [['csrf' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/OrderCreate'],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Заказ создан',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/Order'],
                                    ],
                                ],
                            ],
                            '400' => ['description' => 'Ошибка валидации'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'csrf' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-CSRF-Token',
                        'description' => 'CSRF токен для защиты от CSRF атак',
                    ],
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'JWT токен авторизации',
                    ],
                ],
                'schemas' => [
                    'Product' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'Nike Air Max 90'],
                            'slug' => ['type' => 'string', 'example' => 'nike-air-max-90'],
                            'sku' => ['type' => 'string', 'example' => 'NIKE-AM90-BLK'],
                            'price' => ['type' => 'number', 'format' => 'float', 'example' => 350.00],
                            'old_price' => ['type' => 'number', 'format' => 'float', 'nullable' => true],
                            'brand' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'slug' => ['type' => 'string'],
                                ],
                            ],
                            'category' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'slug' => ['type' => 'string'],
                                ],
                            ],
                            'sizes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => ['type' => 'integer'],
                                        'size' => ['type' => 'string'],
                                        'stock' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                            'images' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => ['type' => 'integer'],
                                        'url' => ['type' => 'string'],
                                        'is_main' => ['type' => 'boolean'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ProductListResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'items' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/Product'],
                            ],
                            '_meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'totalCount' => ['type' => 'integer'],
                                    'pageCount' => ['type' => 'integer'],
                                    'currentPage' => ['type' => 'integer'],
                                    'perPage' => ['type' => 'integer'],
                                ],
                            ],
                        ],
                    ],
                    'CartResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'count' => ['type' => 'integer'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'OrderCreate' => [
                        'type' => 'object',
                        'required' => ['customer_name', 'customer_phone'],
                        'properties' => [
                            'customer_name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 100],
                            'customer_phone' => ['type' => 'string', 'pattern' => '^\+?[0-9]{10,15}$'],
                            'customer_email' => ['type' => 'string', 'format' => 'email', 'nullable' => true],
                            'customer_address' => ['type' => 'string', 'nullable' => true],
                            'notes' => ['type' => 'string', 'nullable' => true],
                            'promo_code' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'Order' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'status' => ['type' => 'string', 'enum' => ['new', 'processing', 'paid', 'shipped', 'delivered', 'cancelled']],
                            'total' => ['type' => 'number', 'format' => 'float'],
                            'customer_name' => ['type' => 'string'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                            'items' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'product_name' => ['type' => 'string'],
                                        'quantity' => ['type' => 'integer'],
                                        'price' => ['type' => 'number'],
                                        'size' => ['type' => 'string', 'nullable' => true],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
