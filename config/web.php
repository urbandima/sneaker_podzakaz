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
        'cache' => extension_loaded('redis') && !YII_ENV_DEV 
            ? [
                // Redis для production
                'class' => 'yii\redis\Cache',
                'redis' => 'redis', // Ссылка на компонент redis
            ]
            : [
                // FileCache для dev/fallback
                'class' => 'yii\caching\FileCache',
                'cachePath' => '@runtime/cache',
            ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => env('REDIS_HOST', 'localhost'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_DB', 0),
        ],
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
            'class' => 'yii\symfonymailer\Mailer',
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
        'currency' => [
            'class' => 'app\components\CurrencyService',
            'cnyToBynRate' => 0.45, // Курс CNY к BYN (обновляется автоматически через API)
            'cacheDuration' => 86400, // 24 часа
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
                
                // ИСПРАВЛЕНО: Явные API роуты для AJAX (Проблема #7)
                'catalog/add-favorite' => 'catalog/add-favorite',
                'catalog/remove-favorite' => 'catalog/remove-favorite',
                'catalog/favorites-count' => 'catalog/favorites-count',
                'catalog/search' => 'catalog/search',
                'catalog/filter' => 'catalog/filter',
                'catalog/quick-view/<id:\d+>' => 'catalog/quick-view',
                
                // Корзина API
                'cart/add' => 'cart/add',
                'cart/update' => 'cart/update',
                'cart/remove/<id:\d+>' => 'cart/remove',
                'cart/count' => 'cart/count',
                
                // Sitemap
                'sitemap.xml' => 'sitemap/index',
                
                // SEF фильтрация (умный фильтр) - ДОЛЖЕН быть после явных роутов
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
