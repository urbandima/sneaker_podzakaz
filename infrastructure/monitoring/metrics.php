<?php

/**
 * Конфигурация метрик Prometheus
 */
return [
    'enabled' => env('METRICS_ENABLED', false),
    
    // Префикс метрик
    'namespace' => 'sneakerhead',
    
    // Метрики приложения
    'metrics' => [
        // HTTP метрики
        'http_requests_total' => [
            'type' => 'counter',
            'help' => 'Total HTTP requests',
            'labels' => ['method', 'path', 'status'],
        ],
        'http_request_duration_seconds' => [
            'type' => 'histogram',
            'help' => 'HTTP request duration in seconds',
            'labels' => ['method', 'path'],
            'buckets' => [0.1, 0.25, 0.5, 1, 2.5, 5, 10],
        ],
        
        // Бизнес метрики
        'orders_total' => [
            'type' => 'counter',
            'help' => 'Total orders created',
            'labels' => ['status'],
        ],
        'order_value_total' => [
            'type' => 'counter',
            'help' => 'Total order value in BYN',
        ],
        'products_viewed_total' => [
            'type' => 'counter',
            'help' => 'Total product views',
            'labels' => ['category'],
        ],
        
        // Метрики производительности
        'database_query_duration_seconds' => [
            'type' => 'histogram',
            'help' => 'Database query duration in seconds',
            'labels' => ['query_type'],
            'buckets' => [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1],
        ],
        'cache_hit_ratio' => [
            'type' => 'gauge',
            'help' => 'Cache hit ratio',
        ],
    ],
    
    // Интервал сбора метрик
    'collect_interval' => 60,
];
