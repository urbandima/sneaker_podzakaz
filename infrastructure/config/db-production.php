<?php

/**
 * Конфигурация БД для ПРОДАКШН сервера
 * Файл защищен через .gitignore
 */

return [
    'class' => 'yii\db\Connection',
    
    // Настройки БД с хостинга vh124.hoster.by
    'dsn' => 'mysql:host=localhost;dbname=sneakerh_username_order_management',
    'username' => 'sneakerh_username_order_user',
    'password' => 'kefir1kefir',
    
    'charset' => 'utf8mb4',
    
    // Опции для продакшн сервера
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600, // 1 час
    'schemaCache' => 'cache',
    
    // Пул соединений
    'attributes' => [
        PDO::ATTR_TIMEOUT => 5,
    ],
];
