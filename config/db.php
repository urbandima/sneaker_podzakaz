<?php

/**
 * Автоматическое определение окружения и подключение к БД
 * 
 * РЕЖИМЫ РАБОТЫ:
 * 1. ЛОКАЛЬНАЯ РАЗРАБОТКА С ТУННЕЛЕМ (рекомендуется):
 *    - Запустите: ./start-ssh-tunnel.sh
 *    - Подключение: 127.0.0.1:3306 → удаленная БД через SSH
 *    - Не требует локальный MySQL
 * 
 * 2. ЛОКАЛЬНАЯ РАЗРАБОТКА С ЛОКАЛЬНОЙ БД:
 *    - Установите MySQL локально
 *    - Создайте БД: order_management
 *    - Раскомментируйте блок LOCAL_DB ниже
 * 
 * 3. ПРОДАКШН:
 *    - Автоматически определяется по домену sneaker-head.by
 * 
 * Файл защищен через .gitignore
 */

// Определяем окружение
$isLocal = (
    !isset($_SERVER['HTTP_HOST']) || 
    $_SERVER['HTTP_HOST'] == 'localhost' || 
    $_SERVER['HTTP_HOST'] == '127.0.0.1' ||
    strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1:') === 0
);

// ============================================
// РЕЖИМ 1: УДАЛЕННАЯ БД ЧЕРЕЗ SSH ТУННЕЛЬ
// ============================================
// Используется по умолчанию для локальной разработки
// Запустите: ./start-ssh-tunnel.sh

$config = [
    'class' => 'yii\db\Connection',
    
    // DSN (подключение к БД)
    'dsn' => $isLocal
        ? 'mysql:host=127.0.0.1;dbname=sneakerh_username_order_management'  // Через SSH туннель
        : 'mysql:host=localhost;dbname=sneakerh_username_order_management', // Продакшн
    
    // Пользователь БД
    'username' => $isLocal
        ? 'sneakerh_username_order_user'  // Продакшн credentials через туннель
        : 'sneakerh_username_order_user',  // Продакшн
    
    // Пароль БД
    'password' => $isLocal
        ? 'kefir1kefir'  // Продакшн пароль через туннель
        : 'kefir1kefir',  // Продакшн
    
    'charset' => 'utf8mb4',
    
    // Отключаем кеш схемы для локальной разработки
    'enableSchemaCache' => !$isLocal,
    'schemaCacheDuration' => $isLocal ? 0 : 3600,
    
    // Увеличиваем таймауты для удаленного подключения
    'attributes' => [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ],
];

// ============================================
// РЕЖИМ 2: ЛОКАЛЬНАЯ БД (АКТИВИРОВАНА)
// ============================================
// Используется для локальной разработки
if ($isLocal) {
    $config['dsn'] = 'mysql:host=127.0.0.1;dbname=order_management';
    $config['username'] = 'root';
    $config['password'] = '';  // Стандартный пароль для brew mysql
}

return $config;
