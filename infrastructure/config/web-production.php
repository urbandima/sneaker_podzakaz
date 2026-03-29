<?php

/**
 * Production конфигурация СНИКЕРХЭД
 * Оптимизировано для продакшена
 */

$params = require __DIR__ . '/params.php';

return [
    'id' => 'sneakerhead-prod',
    'name' => 'СНИКЕРХЭД - Интернет-магазин кроссовок',
    'basePath' => dirname(__DIR__, 2),
    'controllerNamespace' => 'app\frontend\controllers',
    'viewPath' => dirname(__DIR__, 2) . '/frontend/views',
    'layoutPath' => dirname(__DIR__, 2) . '/frontend/views/layouts',
    'bootstrap' => ['log', 'sitemapAutoGenerator', 'securityHeaders', 'redirectMiddleware', 'productionCache'],
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Minsk',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@backend' => '@app/backend',
        '@frontend' => '@app/frontend',
        '@infrastructure' => '@app/infrastructure',
        '@webroot' => '@app/frontend/web',
        '@web' => '/',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => $params['cookieValidationKey'],
            'enableCsrfValidation' => true,
            'enableCookieValidation' => true,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
            'redis' => [
                'hostname' => $params['redis']['host'] ?? '127.0.0.1',
                'port' => $params['redis']['port'] ?? 6379,
                'database' => 0,
                'password' => $params['redis']['password'] ?? null,
            ]
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => $params['redis']['host'] ?? '127.0.0.1',
            'port' => $params['redis']['port'] ?? 6379,
            'database' => 0,
            'password' => $params['redis']['password'] ?? null,
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => $params['db']['dsn'],
            'username' => $params['db']['username'],
            'password' => $params['db']['password'],
            'charset' => 'utf8mb4',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 86400,
            'schemaCache' => 'cache',
            'enableQueryCache' => true,
            'queryCacheDuration' => 3600,
            'queryCache' => 'cache',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'transport' => [
                'dsn' => $params['mailer']['dsn'] ?? 'smtp://user:pass@localhost:587',
            ],
        ],
        'assetManager' => [
            'basePath' => '@webroot/assets',
            'baseUrl' => '@web/assets',
            'linkAssets' => false,
            'appendTimestamp' => true,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                // API routes
                ['class' => 'yii\rest\UrlRule', 'controller' => 'api/v1/product'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'api/v1/cart'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'api/v1/order'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'api/v1/catalog'],
                
                // Frontend routes
                '' => 'site/index',
                'catalog' => 'catalog/catalog/index',
                'catalog/<slug>' => 'catalog/catalog/view',
                'cart' => 'cart/cart/index',
                'checkout' => 'order/index',
                'account' => 'account/default/index',
                'admin' => 'admin/default/index',
                'admin/<controller>/<action>' => 'admin/<controller>/<action>',
            ],
        ],
        'user' => [
            'identityClass' => 'app\backend\modules\catalog\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/admin/login'],
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/app.log',
                    'maxFileSize' => 1024 * 1024 * 10, // 10MB
                    'maxLogFiles' => 10,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['yii\elasticsearch\*'],
                    'levels' => ['info', 'warning', 'error'],
                    'logFile' => '@runtime/logs/elasticsearch.log',
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['app\infrastructure\services\ProductionCacheService'],
                    'levels' => ['info'],
                    'logFile' => '@runtime/logs/cache.log',
                ],
            ],
        ],
        'securityHeaders' => [
            'class' => 'app\infrastructure\middleware\SecurityHeadersMiddleware',
        ],
        'redirectMiddleware' => [
            'class' => 'app\backend\modules\seo\components\RedirectMiddleware',
        ],
        'productionCache' => [
            'class' => 'app\infrastructure\services\ProductionCacheService',
        ],
        'imageOptimizer' => [
            'class' => 'app\infrastructure\services\ImageOptimizationService',
            'webpQuality' => 80,
            'jpegQuality' => 85,
            'maxWidth' => 1200,
            'maxHeight' => 1200,
        ],
        'apiSecurity' => [
            'class' => 'app\infrastructure\middleware\ApiSecurityMiddleware',
            'rateLimit' => 100,
            'burstLimit' => 20,
        ],
        'sitemapAutoGenerator' => [
            'class' => 'app\backend\modules\seo\components\SitemapGenerator',
        ],
    ],
    'params' => $params,
];
