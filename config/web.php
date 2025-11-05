<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'order-management',
    'name' => 'Система управления заказами',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Minsk',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => env('COOKIE_VALIDATION_KEY', '55daa9b88efcaee7aa2537a89365b6cfd36c32e988b0cd14070795aa19a3a081'),
            'baseUrl' => '',
        ],
        // ВРЕМЕННО: FileCache (после установки Redis заменить)
        // Для перехода на Redis выполните:
        // 1. composer require yiisoft/yii2-redis
        // 2. brew install redis && brew services start redis
        // 3. Раскомментировать секцию Redis ниже
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache',
        ],
        
        // БУДУЩЕЕ: Redis cache (раскомментировать после установки)
        // 'redis' => [
        //     'class' => 'yii\redis\Connection',
        //     'hostname' => 'localhost',
        //     'port' => 6379,
        //     'database' => 0,
        // ],
        // 'cache' => [
        //     'class' => 'yii\redis\Cache',
        //     'redis' => [
        //         'hostname' => 'localhost',
        //         'port' => 6379,
        //         'database' => 1, // Отдельная БД для кеша
        //     ],
        //     'keyPrefix' => 'sneakerhead:', // Префикс для избежания коллизий
        // ],
        'assetManager' => [
            'bundles' => YII_ENV_DEV ? [] : [
                'yii\web\JqueryAsset' => [
                    'js' => ['jquery.min.js']
                ],
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => ['css/bootstrap.min.css'],
                ],
                'yii\bootstrap5\BootstrapPluginAsset' => [
                    'js' => ['js/bootstrap.bundle.min.js']
                ],
            ],
            'appendTimestamp' => true,
            'linkAssets' => true,
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => (bool) env('MAIL_USE_FILE_TRANSPORT', true),
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'settings' => [
            'class' => 'app\\components\\Settings',
        ],
        'poizonApi' => [
            'class' => 'app\components\PoizonApiService',
            'apiUrl' => $params['poizonApiUrl'] ?? 'https://api.poizon-parser.com/v1',
            'apiKey' => $params['poizonApiKey'] ?? null,
            'timeout' => 30,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                
                // Публичный просмотр заказа
                'order/<token:[a-zA-Z0-9_-]+>' => 'order/view',
                'order/<token:[a-zA-Z0-9_-]+>/upload' => 'order/upload-payment',
                'order/<token:[a-zA-Z0-9_-]+>/download-payment' => 'order/download-payment',
                
                // Каталог товаров
                'catalog' => 'catalog/index',
                'catalog/brand/<slug:[a-z0-9-]+>' => 'catalog/brand',
                'catalog/category/<slug:[a-z0-9-]+>' => 'catalog/category',
                'catalog/product/<slug:[a-z0-9-]+>' => 'catalog/product',
                'catalog/favorites' => 'catalog/favorites',
                
                // Sitemap
                'sitemap.xml' => 'sitemap/index',
                
                // SEF фильтрация (умный фильтр)
                'catalog/filter/<filters:[\w\-/]+>' => 'catalog/filter-sef',
                
                // Админ-панель
                'admin' => 'admin/index',
                'admin/orders' => 'admin/orders',
                'admin/order/create' => 'admin/create-order',
                'admin/order/<id:\d+>' => 'admin/view-order',
                'admin/order/<id:\d+>/update' => 'admin/update-order',
                'admin/users' => 'admin/users',
                'admin/settings' => 'admin/settings',
                'admin/statistics' => 'admin/statistics',
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
