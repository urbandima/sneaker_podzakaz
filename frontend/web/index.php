<?php

// PHP built-in server: serve static files directly (css/js/images etc.)
if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($staticFile)) {
        return false;
    }
}

// Public frontend: force production mode before bootstrap reads .env so that
// YII_DEBUG=true / YII_ENV=dev in .env cannot leak stack traces to browsers.
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV')   or define('YII_ENV', 'prod');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../infrastructure/config/bootstrap.php';

// Совместимость с Yii2 env-константами (bootstrap может не определить их)
if (!defined('YII_ENV_DEV'))  define('YII_ENV_DEV',  YII_ENV === 'dev');
if (!defined('YII_ENV_PROD')) define('YII_ENV_PROD', YII_ENV === 'prod');
if (!defined('YII_ENV_TEST')) define('YII_ENV_TEST', YII_ENV === 'test');

require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../infrastructure/config/web.php';

(new yii\web\Application($config))->run();
