<?php

/**
 * Конфигурация очередей Redis
 */
return [
    'class' => \yii\queue\redis\Queue::class,
    'redis' => 'redis',
    'channel' => 'queue',
    'as log' => \yii\queue\LogBehavior::class,
    
    // Настройки драйвера
    'driver' => [
        'class' => \yii\queue\redis\Driver::class,
    ],
    
    // Каналы очередей по приоритету
    'channels' => [
        'high' => 'queue:high',      // Высокий приоритет (платежи, заказы)
        'default' => 'queue:default', // Обычный приоритет (email, уведомления)
        'low' => 'queue:low',        // Низкий приоритет (генерация отчетов)
    ],
    
    // Настройки обработки
    'ttr' => 300, // Time to reserve (5 минут)
    'attempts' => 3, // Количество попыток
];
