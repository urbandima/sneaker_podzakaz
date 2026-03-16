<?php

/**
 * Конфигурация структурированных логов
 */
return [
    'enabled' => true,
    
    // Формат логов
    'format' => 'json', // json или text
    
    // Уровень логирования
    'level' => env('LOG_LEVEL', 'info'),
    
    // Поля по умолчанию
    'default_context' => [
        'app' => 'sneakerhead',
        'env' => env('YII_ENV', 'dev'),
        'version' => env('APP_VERSION', '1.0.0'),
    ],
    
    // Каналы логирования
    'channels' => [
        'app' => [
            'path' => '@runtime/logs/app.log',
            'level' => 'info',
            'max_files' => 30,
        ],
        'orders' => [
            'path' => '@runtime/logs/orders.log',
            'level' => 'info',
            'max_files' => 90,
        ],
        'payments' => [
            'path' => '@runtime/logs/payments.log',
            'level' => 'info',
            'max_files' => 90,
        ],
        'security' => [
            'path' => '@runtime/logs/security.log',
            'level' => 'warning',
            'max_files' => 365,
        ],
        'api' => [
            'path' => '@runtime/logs/api.log',
            'level' => 'info',
            'max_files' => 30,
        ],
    ],
    
    // Чувствительные поля для маскировки
    'sensitive_fields' => [
        'password',
        'password_hash',
        'token',
        'api_key',
        'credit_card',
        'cvv',
    ],
];
