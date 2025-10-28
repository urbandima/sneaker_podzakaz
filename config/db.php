<?php

// Конфигурация для MySQL
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=order_management',
    'username' => 'root',
    'password' => '', // Укажите пароль на хостинге
    'charset' => 'utf8mb4',
    
    // Для production на cPanel обычно:
    // 'dsn' => 'mysql:host=localhost;dbname=username_dbname',
    // 'username' => 'username_dbuser',
    // 'password' => 'ваш_пароль_из_cPanel',
];
