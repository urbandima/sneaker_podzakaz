<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$socialClientConfigs = [];
$socialAuth = $params['socialAuth'] ?? [];

if (!empty($socialAuth['googleClientId']) && !empty($socialAuth['googleClientSecret'])) {
    $socialClientConfigs['google'] = [
        'class' => yii\authclient\clients\Google::class,
        'clientId' => $socialAuth['googleClientId'],
        'clientSecret' => $socialAuth['googleClientSecret'],
        'scope' => 'email profile',
    ];
}

if (!empty($socialAuth['yandexClientId']) && !empty($socialAuth['yandexClientSecret'])) {
    $socialClientConfigs['yandex'] = [
        'class' => yii\authclient\clients\Yandex::class,
        'clientId' => $socialAuth['yandexClientId'],
        'clientSecret' => $socialAuth['yandexClientSecret'],
    ];
}

if (!empty($socialAuth['telegramClientId']) && !empty($socialAuth['telegramClientSecret'])) {
    $socialClientConfigs['telegram'] = [
        'class' => yii\authclient\clients\Telegram::class,
        'botName' => $socialAuth['telegramClientId'],
        'botToken' => $socialAuth['telegramClientSecret'],
    ];
}

$config = [
    'id' => 'order-management',
    'name' => 'Система управления заказами',
    'basePath' => dirname(__DIR__, 2),
    'controllerNamespace' => 'app\frontend\controllers',
    'viewPath' => dirname(__DIR__, 2) . '/frontend/views',
    'layoutPath' => dirname(__DIR__, 2) . '/frontend/views/layouts',
    'bootstrap' => ['log', 'sitemapAutoGenerator', 'securityHeaders'],
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
        '@css' => '@app/frontend/css',
        '@js' => '@app/frontend/js',
        '@images' => '@app/frontend/images',
        '@uploads' => '@app/frontend/uploads',
        '@assets' => '@app/frontend/assets',
    ],
    // Feature-based модули (архитектура 2026)
    'modules' => [
        'catalog' => [
            'class' => 'app\backend\modules\catalog\CatalogModule',
        ],
        'cart' => [
            'class' => 'app\backend\modules\cart\CartModule',
        ],
        'account' => [
            'class' => 'app\backend\modules\account\AccountModule',
        ],
        'checkout' => [
            'class' => 'app\backend\modules\checkout\CheckoutModule',
        ],
        'admin' => [
            'class' => 'app\backend\modules\admin\AdminModule',
        ],
        'coupon' => [
            'class' => 'app\backend\modules\coupon\CouponModule',
        ],
        'loyalty' => [
            'class' => 'app\backend\modules\loyalty\LoyaltyModule',
        ],
        'return' => [
            'class' => 'app\backend\modules\return\ReturnModule',
        ],
        'compare' => [
            'class' => 'app\backend\modules\compare\Module',
        ],
        'notification' => [
            'class' => 'app\backend\modules\notification\Module',
        ],
    ],
    'components' => [
        'request' => [
            // SECURITY: Cookie key MUST be set via .env - no hardcoded fallback in production
            'cookieValidationKey' => env('COOKIE_VALIDATION_KEY') ?: ((defined('YII_ENV_DEV') && YII_ENV_DEV) ? 'dev-only-key-change-in-production' : (function() {
                throw new \RuntimeException('COOKIE_VALIDATION_KEY must be set in .env for production!');
            })()),
            'baseUrl' => '',
            // SECURITY: Защита от CSRF
            'enableCsrfValidation' => true,
            'csrfCookie' => [
                'httpOnly' => true,
                'secure' => !(defined('YII_ENV_DEV') && YII_ENV_DEV),
                'sameSite' => \yii\web\Cookie::SAME_SITE_STRICT,
            ],
        ],
        'authClientCollection' => [
            'class' => yii\authclient\Collection::class,
            'clients' => $socialClientConfigs,
        ],
        'cache' => extension_loaded('redis') && YII_ENV === 'prod' && env('REDIS_HOST')
            ? [
                // Redis для production (только если установлен и настроен)
                'class' => 'yii\redis\Cache',
                'redis' => 'redis', // Ссылка на компонент redis
            ]
            : [
                // FileCache для dev/fallback
                'class' => 'yii\caching\FileCache',
                'cachePath' => '@runtime/cache',
            ],
        // Redis компонент для production и dev
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => env('REDIS_HOST', 'localhost'),
            'port' => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_DATABASE', 0),
            'password' => env('REDIS_PASSWORD') ?: null,
            'timeout' => 0.5,
        ],
        'elasticsearch' => [
            'class' => 'app\infrastructure\services\ElasticsearchService',
        ],
        'assetManager' => [
            'bundles' => (defined('YII_ENV_DEV') && YII_ENV_DEV) ? [] : [
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
            'identityClass' => (defined('YII_ENV_DEV') && YII_ENV_DEV) ? 'app\backend\modules\admin\models\TemporaryAdminIdentity' : 'app\backend\modules\admin\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/admin/login'],
            // ВРЕМЕННО: Для разработки - используем временную авторизацию
            'identityCookie' => [
                'name' => '_identity-admin',
                'httpOnly' => true,
                'secure' => !(defined('YII_ENV_DEV') && YII_ENV_DEV),
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@app/backend/shared/mail',
            'useFileTransport' => (bool) env('MAIL_USE_FILE_TRANSPORT', true),
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                // Sentry для production
                ...(!((defined('YII_ENV_DEV') && YII_ENV_DEV)) && env('SENTRY_DSN') ? [[
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error'],
                    'logVars' => [],
                ]] : []),
            ],
        ],
        'db' => $db,
        'sitemapAutoGenerator' => [
            'class' => 'app\backend\shared\components\SitemapAutoGenerator',
        ],
        'settings' => [
            'class' => 'app\backend\shared\components\Settings',
        ],
        'poizonApi' => [
            'class' => 'app\backend\shared\components\PoizonApiService',
            'apiUrl' => $params['poizonApiUrl'] ?? 'https://api.poizon-parser.com/v1',
            'apiKey' => $params['poizonApiKey'] ?? null,
        ],
        'currency' => [
            'class' => 'app\backend\shared\components\CurrencyService',
            'cnyToBynRate' => 0.45, // Курс CNY к BYN (обновляется автоматически через API)
            'cacheDuration' => 86400, // 24 часа
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'normalizer' => [
                'class' => 'yii\web\UrlNormalizer',
                'collapseSlashes' => true,
                'normalizeTrailingSlash' => true,
                'action' => yii\web\UrlNormalizer::ACTION_REDIRECT_TEMPORARY,
            ],
            'rules' => [
                '' => 'site/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                
                // Статические страницы (соответствие законодательству РБ)
                'payment-terms' => 'page/payment-terms',
                'delivery-terms' => 'page/delivery-terms', 
                'return-policy' => 'page/return-policy',
                'privacy' => 'page/privacy',
                'about' => 'page/about',
                'contacts' => 'page/contacts',
                
                // Публичный просмотр заказа
                'order/success/<token:[a-zA-Z0-9_-]+>' => 'order/success',
                'order/<token:[a-zA-Z0-9_-]+>' => 'order/view',
                'order/<token:[a-zA-Z0-9_-]+>/upload' => 'order/upload-payment',
                'order/<token:[a-zA-Z0-9_-]+>/download-payment' => 'order/download-payment',
                
                // Checkout/Order
                'checkout' => 'order/create',
                
                // Каталог товаров
                'catalog' => 'catalog/catalog/index',
                'catalog/brand/<slug:[a-z0-9-]+>' => 'catalog/catalog/brand',
                'catalog/category/<slug:[a-z0-9-]+>' => 'catalog/catalog/category',
                'catalog/product/<slug:[a-z0-9-]+>' => 'catalog/catalog/product',
                'catalog/favorites' => 'catalog/catalog/favorites',
                'catalog/history' => 'catalog/catalog/history',
                
                // Страница брендов
                'brands' => 'catalog/catalog/brands',
                
                // Страница скидок
                'sale' => 'page/sale',
                
                // ИСПРАВЛЕНО: Явные API роуты для AJAX (Проблема #7)
                'catalog/add-favorite' => 'favorite/add',
                'catalog/remove-favorite' => 'favorite/remove',
                'catalog/favorites-count' => 'favorite/count',
                'catalog/search' => 'catalog/catalog/search',
                'catalog/filter' => 'catalog/catalog/filter',
                'catalog/load-more' => 'catalog/catalog/load-more',
                'catalog/quick-view/<id:\d+>' => 'catalog/catalog/quick-view',
                
                // Корзина API
                'cart' => 'cart/cart/index',
                'cart/add' => 'cart/cart/add',
                'cart/update' => 'cart/cart/update',
                'cart/remove/<id:\d+>' => 'cart/cart/remove',
                'cart/count' => 'cart/cart/count',
                
                // Личный кабинет покупателя
                'account' => 'account/account/index',
                'account/login' => 'account/account/login',
                'account/auth' => 'account/account/auth',
                'account/register' => 'account/account/register',
                'account/logout' => 'account/account/logout',
                'account/profile' => 'account/account/profile',
                'account/orders' => 'account/account/orders',
                'account/order/<id:\d+>' => 'account/account/order-view',
                'account/settings' => 'account/account/settings',
                'account/forgot-password' => 'account/account/forgot-password',
                'account/find-orders' => 'account/account/find-orders',
                'account/wishlist' => 'account/account/wishlist',
                
                // Публичный просмотр покупателя (для админки)
                'customer/view' => 'admin/customer/view',
                'customer/update' => 'admin/customer/update',
                
                // Sitemap
                'sitemap.xml' => 'sitemap/index',
                
                // SEF фильтрация (умный фильтр) - ДОЛЖЕН быть после явных роутов
                'catalog/filter/<filters:[\w\-/]+>' => 'catalog/filter-sef',
                
                // Админ-панель
                'admin' => 'admin/dashboard/index',
                'admin/login' => 'admin/admin/login',
                'admin/logout' => 'admin/admin/logout',
                'admin/dashboard' => 'admin/dashboard/index',
                
                // Orders
                'admin/order' => 'admin/order/index',
                'admin/order/create' => 'admin/order/create',
                'admin/order/<id:\d+>' => 'admin/order/view',
                'admin/order/<id:\d+>/update' => 'admin/order/update',
                'admin/order/<id:\d+>/change-status' => 'admin/order/change-status',
                'admin/order/<id:\d+>/assign-logist' => 'admin/order/assign-logist',
                'admin/order/export' => 'admin/order/export',
                
                // Products
                'admin/catalog' => 'admin/product/index',
                'admin/product' => 'admin/product/index',
                'admin/product/create' => 'admin/product/create',
                'admin/product/<id:\d+>' => 'admin/product/view',
                'admin/product/<id:\d+>/edit' => 'admin/product/edit',
                'admin/product/<id:\d+>/toggle' => 'admin/product/toggle',
                'admin/product/<id:\d+>/delete' => 'admin/product/delete',
                'admin/product/<id:\d+>/sync' => 'admin/product/sync',
                'admin/product/bulk-update' => 'admin/product/bulk-update',
                'admin/product/bulk-delete' => 'admin/product/bulk-delete',
                'admin/product/export' => 'admin/product/export',
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
                'admin/user/logists' => 'admin/user/logists',
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
                'admin/settings' => 'admin/settings/index',
                'admin/settings/save' => 'admin/settings/save',
                
                // Tariffs (комиссии и тарифы)
                'admin/tariff' => 'admin/tariff/index',
                'admin/tariff/create' => 'admin/tariff/create',
                'admin/tariff/<id:\d+>/update' => 'admin/tariff/update',
                'admin/tariff/<id:\d+>/delete' => 'admin/tariff/delete',
                'admin/tariff/<id:\d+>/toggle' => 'admin/tariff/toggle',
                'admin/tariff/calculate' => 'admin/tariff/calculate',
                
                // Reviews (отзывы)
                'admin/review' => 'admin/review/index',
                'admin/review/<id:\d+>' => 'admin/review/view',
                'admin/review/<id:\d+>/publish' => 'admin/review/publish',
                'admin/review/<id:\d+>/unpublish' => 'admin/review/unpublish',
                'admin/review/<id:\d+>/respond' => 'admin/review/respond',
                'admin/review/<id:\d+>/delete' => 'admin/review/delete',
                'admin/review/<id:\d+>/toggle-featured' => 'admin/review/toggle-featured',
                
                // Analytics (аналитика)
                'admin/analytics' => 'admin/analytics/index',
                'admin/analytics/conversion' => 'admin/analytics/conversion',
                'admin/analytics/sales' => 'admin/analytics/sales',
                'admin/analytics/export' => 'admin/analytics/export',
                
                // Search
                'admin/search' => 'admin/search/global',
                'admin/search/orders' => 'admin/search/orders',
                'admin/profile' => 'admin/dashboard/profile',
                
                // Characteristics
                'admin/characteristic' => 'admin/characteristic/index',
                'admin/characteristic/guide' => 'admin/characteristic/guide',
                
                // Customers (покупатели)
                'admin/customer' => 'admin/customer/index',
                'admin/customer/<id:\d+>' => 'admin/customer/view',
                'admin/customer/<id:\d+>/update' => 'admin/customer/update',
                'admin/customer/<id:\d+>/delete' => 'admin/customer/delete',
                'admin/customer/<id:\d+>/toggle-status' => 'admin/customer/toggle-status',
                'admin/customer/<id:\d+>/reset-password' => 'admin/customer/reset-password',
                'admin/customer/<id:\d+>/link-orders' => 'admin/customer/link-orders',
                'admin/customer/export' => 'admin/customer/export',
                
                // Import
                'admin/import' => 'admin/import/index',
                'admin/import/upload' => 'admin/import/upload',
                'admin/import/source' => 'admin/import/source',
                'admin/import/source/<id:\d+>' => 'admin/import/source',
                'admin/import/run/<sourceId:\d+>' => 'admin/import/run',
                'admin/import/run-all' => 'admin/import/runAll',
                'admin/import/logs' => 'admin/import/logs',
                'admin/import/stats' => 'admin/import/stats',
                'admin/import/settings' => 'admin/import/settings',
                
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
        'securityHeaders' => [
            'class' => 'app\backend\shared\middleware\SecurityHeadersMiddleware',
            'enableCsp' => !((defined('YII_ENV_DEV') && YII_ENV_DEV)), // CSP только в проде (в dev мешает отладке)
            'enableHsts' => (defined('YII_ENV_PROD') && YII_ENV_PROD),
        ],
    ],
    'params' => $params,
];

if ((defined('YII_ENV_DEV') && YII_ENV_DEV)) {
    // configuration adjustments for 'dev' environment
    // ВРЕМЕННО ОТКЛЮЧАЕМ DEBUG ДЛЯ БЕЗОПАСНОСТИ АДМИН-ПАНЕЛИ
    // $config['bootstrap'][] = 'debug';
    // $config['modules']['debug'] = [
    //     'class' => 'yii\debug\Module',
    // ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
