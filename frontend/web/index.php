<?php

// PHP built-in server: serve static files directly (css/js/images etc.)
if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($staticFile)) {
        return false;
    }
}

// Точка входа для новой архитектуры 2026 (frontend/backend/infrastructure)

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../infrastructure/config/bootstrap.php';

// Определить окружение на основе переменных среды или домена
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

// Определяем константы окружения для совместимости с Yii2
if (!defined('YII_ENV_DEV')) {
    define('YII_ENV_DEV', YII_ENV === 'dev');
}
if (!defined('YII_ENV_PROD')) {
    define('YII_ENV_PROD', YII_ENV === 'prod');
}
if (!defined('YII_ENV_TEST')) {
    define('YII_ENV_TEST', YII_ENV === 'test');
}

require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../infrastructure/config/web.php';

(new yii\web\Application($config))->run();
