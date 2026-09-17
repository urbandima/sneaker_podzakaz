<?php
/**
 * Global Codeception bootstrap — mirrors frontend/web/index.php's boot sequence
 * (autoload -> env -> Yii.php) so the Yii2 module can build the app from tests/config.php.
 */

defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../infrastructure/config/bootstrap.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
