<?php

/**
 * Пример конфигурации базы данных
 * 
 * ИНСТРУКЦИЯ:
 * 1. Скопируйте этот файл в db.php
 * 2. Укажите данные вашей MySQL базы данных
 * 3. Файл db.php не попадет в Git (защищен через .gitignore)
 */

return [
    'class' => 'yii\db\Connection',
    
    // Для локальной разработки:
    'dsn' => 'mysql:host=localhost;dbname=order_management',
    'username' => 'root',
    'password' => '',
    
    // Для cPanel хостинга (раскомментировать и заполнить):
    // 'dsn' => 'mysql:host=localhost;dbname=username_dbname',
    // 'username' => 'username_dbuser',
    // 'password' => 'ваш_пароль_из_cPanel',
    
    'charset' => 'utf8mb4',
];
