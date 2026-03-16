<?php
/**
 * PHPUnit Bootstrap для СНИКЕРХЭД
 * Инициализация тестового окружения
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Загружаем тестовую конфигурацию
$config = require __DIR__ . '/config.php';

// Создаем приложение для тестов
new yii\web\Application($config);
