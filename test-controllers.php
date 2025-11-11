<?php
/**
 * Скрипт для проверки правильности контроллеров
 */

// Проверка существования файлов
$controllers = [
    'admin/BaseAdminController.php',
    'admin/OrderController.php',
    'admin/ProductController.php',
    'admin/DashboardController.php',
    'admin/UserController.php',
    'admin/SizeGridController.php',
    'admin/PoizonController.php',
    'admin/StatisticsController.php',
    'admin/CharacteristicController.php',
];

echo "=== Проверка файлов контроллеров ===\n\n";

foreach ($controllers as $controller) {
    $path = __DIR__ . '/controllers/' . $controller;
    $exists = file_exists($path);
    $status = $exists ? '✓ OK' : '✗ НЕТ';
    echo "$status - $controller\n";
    
    if ($exists) {
        // Проверка namespace
        $content = file_get_contents($path);
        if (strpos($content, 'namespace app\controllers\admin;') !== false) {
            echo "  → Namespace: ✓ app\\controllers\\admin\n";
        } else {
            echo "  → Namespace: ✗ НЕПРАВИЛЬНЫЙ\n";
        }
        
        // Проверка класса
        $className = str_replace('.php', '', basename($controller));
        if (strpos($content, "class $className") !== false) {
            echo "  → Class: ✓ $className\n";
        } else {
            echo "  → Class: ✗ НЕ НАЙДЕН\n";
        }
    }
    echo "\n";
}

echo "\n=== Проверка старого контроллера ===\n\n";
$oldController = __DIR__ . '/controllers/AdminController.php';
if (file_exists($oldController)) {
    echo "✗ ПРОБЛЕМА: Старый AdminController.php все еще существует!\n";
    echo "  Это вызывает конфликт с новыми контроллерами.\n";
    echo "  Переименуйте его в AdminController.php.backup\n";
} else {
    echo "✓ OK: Старый AdminController.php удален/переименован\n";
}

$backupController = __DIR__ . '/controllers/AdminController.php.backup';
if (file_exists($backupController)) {
    echo "✓ OK: Backup существует: AdminController.php.backup\n";
}

echo "\n=== Рекомендации ===\n\n";
echo "1. Очистить кеш: rm -rf runtime/cache/*\n";
echo "2. Перезапустить веб-сервер\n";
echo "3. Проверить роуты: /admin/order, /admin/product\n";
echo "\n";
