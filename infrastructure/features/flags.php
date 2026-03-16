<?php

/**
 * Конфигурация Feature Flags
 */
return [
    'enabled' => true,
    
    // Хранилище флагов (database, redis, config)
    'storage' => env('FEATURE_FLAGS_STORAGE', 'config'),
    
    // Feature Flags
    'flags' => [
        // Новые фичи
        'new_checkout_flow' => [
            'enabled' => env('FEATURE_NEW_CHECKOUT', false),
            'rollout' => 10, // % пользователей
            'description' => 'Новый процесс оформления заказа',
        ],
        
        'product_recommendations' => [
            'enabled' => env('FEATURE_RECOMMENDATIONS', true),
            'description' => 'Рекомендации товаров на основе ML',
        ],
        
        'dark_mode' => [
            'enabled' => env('FEATURE_DARK_MODE', false),
            'description' => 'Тёмная тема интерфейса',
        ],
        
        // Экспериментальные
        'experimental_search' => [
            'enabled' => env('FEATURE_EXPERIMENTAL_SEARCH', false),
            'description' => 'Экспериментальный поиск с AI',
        ],
        
        // A/B тесты
        'ab_checkout_button_color' => [
            'enabled' => true,
            'variants' => ['blue', 'green', 'orange'],
            'distribution' => [33, 33, 34],
            'description' => 'A/B тест цвета кнопки оформления',
        ],
        
        // Платные фичи
        'premium_support' => [
            'enabled' => true,
            'requires_subscription' => true,
            'description' => 'Премиум поддержка клиентов',
        ],
    ],
    
    // Группы пользователей для rollout
    'groups' => [
        'beta_testers' => [
            'emails' => ['test@example.com'],
            'user_ids' => [1, 2, 3],
        ],
        'vip_customers' => [
            'user_ids' => [],
        ],
    ],
];
