<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/bootstrap.php';

// Определить окружение на основе переменных среды или домена, если не задано в .env
if (!defined('YII_ENV')) {
    $isProduction = (
        getenv('YII_ENV') === 'prod' || 
        getenv('RENDER') !== false ||
        (isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false ||
            strpos($_SERVER['HTTP_HOST'], 'sneaker-head.by') !== false
        ))
    );
    define('YII_ENV', $isProduction ? 'prod' : 'dev');
}

if (!defined('YII_DEBUG')) {
    define('YII_DEBUG', YII_ENV !== 'prod');
}

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
