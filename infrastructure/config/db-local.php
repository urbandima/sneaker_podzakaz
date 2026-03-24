<?php

/**
 * Конфигурация БД для ЛОКАЛЬНОЙ разработки
 * 
 * ИНСТРУКЦИЯ:
 * 1. Скопируйте этот файл: db-local-example.php -> db-local.php
 * 2. Укажите настройки вашей локальной MySQL
 * 3. Файл db-local.php защищен через .gitignore
 */

return [
    'class' => 'yii\db\Connection',
    
    // Локальная MySQL база данных
    'dsn' => 'mysql:host=localhost;dbname=sneakerhead;charset=utf8mb4',
    'username' => 'root',
    'password' => '',
    
    'charset' => 'utf8mb4',
    
    // Опции для стабильной работы
    'enableSchemaCache' => false,
    'schemaCacheDuration' => 0,
    
    // Для отладки SQL запросов (отключите в продакшне)
    'enableQueryCache' => false,
];
