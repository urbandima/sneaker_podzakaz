<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'order-management',
    'name' => 'Система управления заказами',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'sitemapAutoGenerator'],
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
        'sitemapAutoGenerator' => [
            'class' => 'app\components\SitemapAutoGenerator',
        ],
        'settings' => [
            'class' => 'app\components\Settings',
        ],
        'poizonApi' => [
            'class' => 'app\components\PoizonApiService',
            'apiUrl' => $params['poizonApiUrl'] ?? 'https://api.poizon-parser.com/v1',
            'apiKey' => $params['poizonApiKey'] ?? null,
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
                'order/success/<token:[a-zA-Z0-9_-]+>' => 'order/success',
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
                'catalog/load-more' => 'catalog/load-more',
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
                
                // Админ-панель (новая архитектура)
                'admin' => 'admin/dashboard/index',
                
                // Orders
                'admin/order' => 'admin/order/index',
                'admin/order/create' => 'admin/order/create',
                'admin/order/<id:\d+>' => 'admin/order/view',
                'admin/order/<id:\d+>/update' => 'admin/order/update',
                'admin/order/<id:\d+>/change-status' => 'admin/order/change-status',
                'admin/order/<id:\d+>/assign-logist' => 'admin/order/assign-logist',
                'admin/order/export' => 'admin/order/export',
                
                // Products
                'admin/product' => 'admin/product/index',
                'admin/product/<id:\d+>' => 'admin/product/view',
                'admin/product/<id:\d+>/edit' => 'admin/product/edit',
                'admin/product/<id:\d+>/toggle' => 'admin/product/toggle',
                'admin/product/<id:\d+>/delete' => 'admin/product/delete',
                'admin/product/<id:\d+>/sync' => 'admin/product/sync',
                'admin/product/<productId:\d+>/add-size' => 'admin/product/add-size',
                'admin/product/<productId:\d+>/add-sizes-grid/<gridId:\d+>' => 'admin/product/add-sizes-from-grid',
                'admin/product/size/<id:\d+>/edit' => 'admin/product/edit-size',
                'admin/product/size/<id:\d+>/delete' => 'admin/product/delete-size',
                'admin/product/<productId:\d+>/add-image' => 'admin/product/add-image',
                'admin/product/image/<id:\d+>/delete' => 'admin/product/delete-image',
                'admin/product/image/<id:\d+>/set-main' => 'admin/product/set-main-image',
                
                // Users
                'admin/user' => 'admin/user/index',
                'admin/user/create' => 'admin/user/create',
                'admin/user/<id:\d+>/delete' => 'admin/user/delete',
                
                // Size Grids
                'admin/size-grid' => 'admin/size-grid/index',
                'admin/size-grid/create' => 'admin/size-grid/create',
                'admin/size-grid/<id:\d+>/edit' => 'admin/size-grid/edit',
                'admin/size-grid/<id:\d+>/delete' => 'admin/size-grid/delete',
                'admin/size-grid/guide' => 'admin/size-grid/guide',
                'admin/size-grid/<gridId:\d+>/add-item' => 'admin/size-grid/add-item',
                'admin/size-grid/item/<id:\d+>/delete' => 'admin/size-grid/delete-item',
                
                // Poizon
                'admin/poizon' => 'admin/poizon/index',
                'admin/poizon/run' => 'admin/poizon/run',
                'admin/poizon/<id:\d+>' => 'admin/poizon/view',
                'admin/poizon/logs' => 'admin/poizon/view-log',
                'admin/poizon/<id:\d+>/delete' => 'admin/poizon/delete',
                
                // Statistics & Settings
                'admin/statistics' => 'admin/statistics/index',
                'admin/settings' => 'admin/dashboard/settings',
                'admin/profile' => 'admin/dashboard/profile',
                
                // Characteristics
                'admin/characteristic' => 'admin/characteristic/index',
                'admin/characteristic/guide' => 'admin/characteristic/guide',
                
                // Backward compatibility (старые роуты перенаправляем на новые)
                'admin/orders' => 'admin/order/index',
                'admin/create-order' => 'admin/order/create',
                'admin/view-order/<id:\d+>' => 'admin/order/view',
                'admin/update-order/<id:\d+>' => 'admin/order/update',
                'admin/export-orders' => 'admin/order/export',
                'admin/change-status/<id:\d+>' => 'admin/order/change-status',
                'admin/assign-logist/<id:\d+>' => 'admin/order/assign-logist',
                'admin/users' => 'admin/user/index',
                'admin/create-user' => 'admin/user/create',
                'admin/products' => 'admin/product/index',
                'admin/view-product/<id:\d+>' => 'admin/product/view',
                'admin/edit-product/<id:\d+>' => 'admin/product/edit',
                'admin/add-image' => 'admin/product/add-image',
                'admin/add-size' => 'admin/product/add-size',
                'admin/poizon-import' => 'admin/poizon/index',
                'admin/poizon-run' => 'admin/poizon/run',
                'admin/poizon-errors' => 'admin/poizon/errors',
                'admin/size-grids' => 'admin/size-grid/index',
                'admin/create-size-grid' => 'admin/size-grid/create',
                'admin/size-guide' => 'admin/size-grid/guide',
                'admin/add-size-grid-item' => 'admin/size-grid/add-item',
                'admin/characteristics-guide' => 'admin/characteristic/guide',
                'admin/product/characteristics-guide' => 'admin/characteristic/guide',
                
                // Общее правило для остальных admin действий
                'admin/<controller:\w+>/<action:\w+>/<id:\d+>' => 'admin/<controller>/<action>',
                'admin/<controller:\w+>/<action:\w+>' => 'admin/<controller>/<action>',
                'admin/<controller:\w+>' => 'admin/<controller>/index',

                // REST API
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['api/characteristic'],
                    'pluralize' => true,
                    'tokens' => [
                        '{id}' => '<id:\d+>',
                        '{valueId}' => '<valueId:\d+>',
                    ],
                    'extraPatterns' => [
                        'GET {id}/values' => 'values',
                        'POST {id}/values' => 'create-value',
                        'PUT {id}/values/{valueId}' => 'update-value',
                        'PATCH {id}/values/{valueId}' => 'update-value',
                        'DELETE {id}/values/{valueId}' => 'delete-value',
                    ],
                ],
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
