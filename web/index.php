<?php

// Определить окружение на основе переменных среды или домена
$isProduction = (
    getenv('YII_ENV') === 'prod' || 
    getenv('RENDER') !== false ||
    (isset($_SERVER['HTTP_HOST']) && (
        strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false ||
        strpos($_SERVER['HTTP_HOST'], 'sneaker-head.by') !== false
    ))
);

defined('YII_DEBUG') or define('YII_DEBUG', !$isProduction);
defined('YII_ENV') or define('YII_ENV', $isProduction ? 'prod' : 'dev');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
