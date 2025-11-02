#!/usr/bin/env php
<?php
/**
 * Скрипт для проверки подключения к БД
 * Использует те же настройки, что и основное приложение
 */

echo "\n";
echo "=====================================\n";
echo "ПРОВЕРКА ПОДКЛЮЧЕНИЯ К БД\n";
echo "=====================================\n\n";

// Загружаем конфигурацию БД
$dbConfig = require __DIR__ . '/config/db.php';

echo "Параметры подключения:\n";
echo "  DSN: {$dbConfig['dsn']}\n";
echo "  User: {$dbConfig['username']}\n";
echo "  Password: " . str_repeat('*', strlen($dbConfig['password'])) . "\n\n";

// Пытаемся подключиться
try {
    echo "[1/4] Создание PDO подключения...\n";
    
    $pdo = new PDO(
        $dbConfig['dsn'],
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]
    );
    
    echo "✓ Подключение установлено успешно!\n\n";
    
    // Проверяем версию MySQL
    echo "[2/4] Получение информации о сервере...\n";
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as dbname, CURRENT_USER() as user");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  MySQL версия: {$info['version']}\n";
    echo "  База данных: {$info['dbname']}\n";
    echo "  Пользователь: {$info['user']}\n\n";
    
    // Проверяем список таблиц
    echo "[3/4] Получение списка таблиц...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "  Найдено таблиц: " . count($tables) . "\n";
        echo "  Список таблиц:\n";
        foreach ($tables as $table) {
            echo "    • {$table}\n";
        }
    } else {
        echo "  ⚠ Таблиц не найдено (база пустая)\n";
    }
    
    echo "\n[4/4] Проверка прав доступа...\n";
    
    // Пытаемся выполнить SELECT на одной из таблиц
    if (in_array('user', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `user`");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  ✓ SELECT: OK (записей в таблице user: {$count['cnt']})\n";
    } else {
        echo "  ⚠ Таблица 'user' не найдена, пропускаем проверку\n";
    }
    
    echo "\n";
    echo "=====================================\n";
    echo "✓ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ УСПЕШНО!\n";
    echo "=====================================\n\n";
    
    echo "Теперь вы можете:\n";
    echo "  • Запустить сайт: php yii serve\n";
    echo "  • Открыть в браузере: http://localhost:8080\n\n";
    
} catch (PDOException $e) {
    echo "\n";
    echo "=====================================\n";
    echo "✗ ОШИБКА ПОДКЛЮЧЕНИЯ\n";
    echo "=====================================\n\n";
    
    echo "Сообщение ошибки:\n";
    echo "  {$e->getMessage()}\n\n";
    
    echo "Возможные причины:\n";
    
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "  • SSH туннель не запущен\n";
        echo "    Решение: ./start-ssh-tunnel.sh\n\n";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "  • Неверные учетные данные БД\n";
        echo "    Проверьте config/db.php\n\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "  • База данных не существует\n";
        echo "    Проверьте имя БД в config/db.php\n\n";
    } else {
        echo "  • Проверьте, запущен ли SSH туннель: ./check-ssh-tunnel.sh\n";
        echo "  • Проверьте параметры подключения в config/db.php\n\n";
    }
    
    exit(1);
}
