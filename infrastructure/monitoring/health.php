<?php

/**
 * Конфигурация health checks
 */
return [
    'enabled' => true,
    
    // Проверки здоровья
    'checks' => [
        'database' => [
            'enabled' => true,
            'timeout' => 5,
            'query' => 'SELECT 1',
        ],
        'redis' => [
            'enabled' => true,
            'timeout' => 3,
        ],
        'elasticsearch' => [
            'enabled' => env('ELASTICSEARCH_ENABLED', false),
            'timeout' => 5,
        ],
        'queue' => [
            'enabled' => true,
            'max_jobs' => 1000, // Максимальное количество задач в очереди
        ],
        'storage' => [
            'enabled' => true,
            'min_free_space' => 1_000_000_000, // 1GB
        ],
    ],
    
    // Пороги для alerting
    'thresholds' => [
        'memory_usage' => 0.9, // 90%
        'cpu_load' => 0.8, // 80%
        'disk_usage' => 0.85, // 85%
    ],
];
