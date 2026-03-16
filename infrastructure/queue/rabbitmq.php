<?php

/**
 * Конфигурация очередей RabbitMQ
 */
return [
    'class' => \yii\queue\amqp_interop\Queue::class,
    'host' => env('RABBITMQ_HOST', 'localhost'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'queueName' => 'sneakerhead-queue',
    'driver' => \yii\queue\amqp_interop\Queue::ENQUEUE_AMQP,
    
    // Настройки обмена
    'exchange' => [
        'name' => 'sneakerhead-exchange',
        'type' => \Interop\Amqp\AmqpTopic::TYPE_DIRECT,
    ],
    
    // Настройки очередей
    'queues' => [
        'orders' => [
            'name' => 'orders-queue',
            'durable' => true,
        ],
        'emails' => [
            'name' => 'emails-queue',
            'durable' => true,
        ],
        'notifications' => [
            'name' => 'notifications-queue',
            'durable' => true,
        ],
    ],
];
