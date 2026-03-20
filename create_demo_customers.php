<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

// Создание правильных хешей паролей
$demoPassword = 'demo123';
$vipPassword = 'vip123';
$adminPassword = 'admin123';

$demoHash = password_hash($demoPassword, PASSWORD_DEFAULT);
$vipHash = password_hash($vipPassword, PASSWORD_DEFAULT);
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

echo "Demo hash: $demoHash\n";
echo "VIP hash: $vipHash\n";
echo "Admin hash: $adminHash\n";

// SQL с правильными хешами
$sql = "-- Создание демо-пользователей для тестирования

-- Обычный пользователь
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'demo@sneakerhead.by',
    '+375291234567',
    '$demoHash',
    'demo_auth_key_" . time() . "',
    'Демо',
    'Пользователь',
    10,
    " . time() . ",
    " . time() . "
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = " . time() . ";

-- VIP пользователь
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'vip@sneakerhead.by',
    '+375337654321',
    '$vipHash',
    'vip_auth_key_" . (time() + 1) . "',
    'VIP',
    'Клиент',
    10,
    " . time() . ",
    " . time() . "
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = " . time() . ";

-- Администратор
INSERT INTO customer (email, phone, password_hash, auth_key, first_name, last_name, status, created_at, updated_at) 
VALUES (
    'admin@sneakerhead.by',
    '+375449876543',
    '$adminHash',
    'admin_auth_key_" . (time() + 2) . "',
    'Админ',
    'Системы',
    10,
    " . time() . ",
    " . time() . "
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    auth_key = VALUES(auth_key),
    updated_at = " . time() . ";";

file_put_contents(__DIR__ . '/create_demo_customers_final.sql', $sql);
echo "SQL файл создан: create_demo_customers_final.sql\n";
echo "Выполните: mysql -u root -p sneakerhead < create_demo_customers_final.sql\n";
