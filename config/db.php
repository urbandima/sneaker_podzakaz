<?php

/**
 * Конфигурация подключения к БД через переменные окружения.
 *
 * Поддерживаемые переменные .env:
 *  - DB_DSN (предпочтительно, полный DSN)
 *  - DB_HOST, DB_PORT, DB_NAME (если DSN не задан)
 *  - DB_USERNAME, DB_PASSWORD
 *  - DB_SCHEMA_CACHE (true|false)
 *  - DB_SCHEMA_CACHE_DURATION (секунды)
 */

$dsn = env('DB_DSN');

if (!$dsn) {
    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', 3306);
    $database = env('DB_NAME', 'order_management');
    $charset = env('DB_CHARSET', 'utf8mb4');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);
}

$config = [
    'class' => 'yii\db\Connection',
    'dsn' => $dsn,
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'enableSchemaCache' => (bool) env('DB_SCHEMA_CACHE', YII_ENV === 'prod'),
    'schemaCacheDuration' => (int) env('DB_SCHEMA_CACHE_DURATION', 3600),
    'attributes' => array_filter([
        \PDO::ATTR_TIMEOUT => env('DB_TIMEOUT') ? (int) env('DB_TIMEOUT') : null,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ]),
];

return $config;
