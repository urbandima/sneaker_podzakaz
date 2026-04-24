<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

// Загрузка алиасов для совместимости
require __DIR__ . '/../../frontend/config/aliases.php';

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
    'bootstrap' => ['log', 'sitemapAutoGenerator', 'securityHeaders', 'redirectMiddleware'],
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
        '@helpers' => '@app/backend/shared/helpers',
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
        'returns' => [
            'class' => 'app\backend\modules\returns\ReturnModule',
        ],
        'compare' => [
            'class' => 'app\backend\modules\compare\Module',
        ],
        'notification' => [
            'class' => 'app\backend\modules\notification\NotificationModule',
        ],
        'api' => [
            'class' => 'app\api\ApiModule',
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
            // Настройка для работы через proxy (browser preview)
            'trustedHosts' => [
                '127.0.0.1' => true,
                'localhost' => true,
            ],
            'ipHeaders' => ['X-Forwarded-For'],
            'secureHeaders' => [
                'X-Forwarded-For',
                'X-Forwarded-Host',
                'X-Forwarded-Proto',
            ],
        ],
        'authClientCollection' => [
            'class' => yii\authclient\Collection::class,
            'clients' => $socialClientConfigs,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => env('REDIS_HOST', 'localhost'),
            'port' => (int) env('REDIS_PORT', 6379),
            'database' => (int) env('REDIS_DB', 0),
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
            'linkAssets' => (defined('YII_ENV_DEV') && YII_ENV_DEV) ? false : true,
        ],
        'user' => [
            'identityClass' => 'app\backend\modules\admin\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/admin/login'],
            // SECURITY FIX: Add session timeout — auto-logout after 24h inactivity
            'authTimeout' => (int) env('SESSION_LIFETIME', 1440) * 60, // minutes -> seconds
            'absoluteAuthTimeout' => 86400 * 7, // 7 days max session
            'identityCookie' => [
                'name' => '_identity-admin',
                'httpOnly' => true,
                'secure' => !(defined('YII_ENV') && YII_ENV === 'dev'),
                'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
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
        'europochtaTracking' => [
            'class' => 'app\backend\modules\checkout\services\EuropochtaTrackingService',
        ],
        'belpochtaTracking' => [
            'class' => 'app\backend\modules\checkout\services\BelpochtaTrackingService',
        ],
        'cdekTracking' => [
            'class' => 'app\backend\modules\checkout\services\CdekTrackingService',
        ],
        'dobropost' => [
            'class'         => 'app\backend\modules\checkout\services\DobroPostService',
            'apiUrl'        => env('DP_API_URL', 'https://api.dobropost.com'),
            'email'         => env('DP_API_EMAIL', ''),
            'password'      => env('DP_API_PASSWORD', ''),
            'defaultTariff' => (int) env('DP_DEFAULT_TARIFF', 26),
        ],
        'automation' => [
            'class' => 'app\backend\modules\automation\services\AutomationEngine',
        ],
        'moysklad' => [
            'class' => 'app\backend\shared\services\MoySkladService',
        ],
        'sms' => [
            'class'      => 'app\backend\modules\notification\services\SmsService',
            'provider'   => env('SMS_PROVIDER', 'rocketsms'),
            'senderName' => env('SMS_SENDER', null),
            'apiKeys'    => [
                'rocketsms_username' => env('ROCKETSMS_USERNAME', ''),
                'rocketsms_password' => env('ROCKETSMS_PASSWORD', ''),
                'rocketsms_sender'   => env('ROCKETSMS_SENDER', ''),
                'smsc_login'         => env('SMSC_LOGIN', ''),
                'smsc_password'      => env('SMSC_PASSWORD', ''),
                'smsru_api_id'       => env('SMSRU_API_ID', ''),
                'twilio_sid'         => env('TWILIO_SID', ''),
                'twilio_token'       => env('TWILIO_TOKEN', ''),
                'twilio_from'        => env('TWILIO_FROM', ''),
            ],
        ],
        'currency' => [
            'class' => 'app\backend\shared\components\CurrencyService',
            'cnyToBynRate' => 0.45, // Курс CNY к BYN (обновляется автоматически через API)
            'cacheDuration' => 86400, // 24 часа
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'currencyCode' => 'BYN',
            'locale' => 'ru-RU',
            'timeZone' => 'Europe/Minsk',
            'defaultTimeZone' => 'Europe/Minsk',
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
                'offer-agreement' => 'site/offer-agreement',
                'payment-instruction' => 'site/payment-instruction',

                // Алиасы коротких URL → канонические страницы
                'delivery' => 'page/delivery-terms',
                'return' => 'page/return-policy',
                'payment' => 'page/payment-terms',
                'terms' => 'page/privacy',
                'faq' => 'page/contacts',
                'size-guide' => 'catalog/catalog/index',
                'guarantee' => 'page/return-policy',
                'tracking' => 'account/account/find-orders',
                'loyalty' => 'page/about',

                // Алиасы для брендов (короткий URL → каталог бренда)
                'brands/<slug:[a-z0-9-]+>' => 'catalog/catalog/brand',
                
                // Checkout — страница оформления (GET) и создание заказа (AJAX POST)
                'checkout' => 'order/index',
                'order/create' => 'order/create',
                'order/save-passport' => 'order/save-passport',

                // Публичный просмотр заказа (wildcard должен быть ПОСЛЕ явных order/* правил)
                'order/success/<token:[a-zA-Z0-9_-]+>' => 'order/success',
                'order/<token:[a-zA-Z0-9_-]+>/upload' => 'order/upload-payment',
                'order/<token:[a-zA-Z0-9_-]+>/download-payment' => 'order/download-payment',
                'order/<token:[a-zA-Z0-9_-]+>' => 'order/view',
                
                // Каталог товаров
                'catalog' => 'catalog/catalog/index',
                // SEO инструменты в админке
                'admin/seo' => 'admin/seo/index',
                'admin/seo/redirects' => 'admin/seo/redirects',
                'admin/seo/redirect-edit' => 'admin/seo/redirect-edit',
                'admin/seo/redirect-delete/<id:\d+>' => 'admin/seo/redirect-delete',
                'admin/seo/sitemap' => 'admin/seo/sitemap',
                'admin/seo/robots' => 'admin/seo/robots',
                'admin/seo/bulk-meta' => 'admin/seo/bulk-meta',
                'admin/seo/alt-texts' => 'admin/seo/alt-texts',
                'admin/seo/update-product-meta' => 'admin/seo/update-product-meta',
                'admin/seo/update-image-alt' => 'admin/seo/update-image-alt',
                
                // АЛИАСЫ: Совместимость со старыми URL
                'admin/delivery' => 'admin/shipping/index',
                'admin/delivery/<action:[a-z-]+>' => 'admin/shipping/<action>',
                'admin/marketing-campaign' => 'admin/marketing/campaigns',
                'admin/marketing-campaign/<action:[a-z-]+>' => 'admin/marketing/<action>',
                'admin/settings/integrations/<tab:[a-z-]+>' => 'admin/settings/integrations',
                // W3-W9: missing route aliases
                'admin/order/return' => 'admin/return/index',
                'admin/order/returns' => 'admin/return/index',
                'admin/shipment' => 'admin/shipping/dispatch',
                'admin/shipment/<action:[a-z-]+>' => 'admin/shipping/<action>',
                'admin/finance/expense' => 'admin/finance/expenses',
                'admin/finance/pl' => 'admin/finance/pnl',
                'admin/campaign' => 'admin/marketing/campaigns',
                'admin/campaign/<action:[a-z-]+>' => 'admin/marketing/<action>',
                'admin/amocrm' => 'admin/plugin/amocrm',
                'admin/settings/delivery' => 'admin/settings/shipping',
                
                // Webhook endpoints
                'api/webhook/dobropost' => 'api/webhook/dobropost',

                // Catalog API endpoints (вынесено из CatalogController)
                'api/catalog/filter' => 'catalog/catalog-api/filter',
                'api/catalog/load-more' => 'catalog/catalog-api/load-more',
                'api/catalog/quick-view/<id:\d+>' => 'catalog/catalog-api/quick-view',
                'api/catalog/get-brands' => 'catalog/catalog-api/get-brands',
                'api/catalog/products-by-ids' => 'catalog/catalog-api/products-by-ids',
                'catalog/brand/<slug:[a-z0-9-]+>' => 'catalog/catalog/brand',
                'catalog/category/<slug:[a-z0-9-]+>' => 'catalog/catalog/category',
                'catalog/category' => 'catalog/catalog/category', // Поддержка query параметра ?slug=
                'catalog/product/<slug:[a-z0-9-]+>' => 'catalog/catalog/product',
                'catalog/product' => 'catalog/catalog/product', // Поддержка query параметра ?slug=
                'catalog/favorites' => 'catalog/catalog/favorites',
                'catalog/history' => 'catalog/catalog/history',
                
                // Страница брендов
                'brands' => 'catalog/catalog/brands',
                
                // Страница скидок (+ алиас /sales для 301)
                'sale' => 'page/sale',
                'sales' => 'page/sale',
                
                // ИСПРАВЛЕНО: Явные API роуты для AJAX (Проблема #7)
                'catalog/add-favorite' => 'favorite/add',
                'catalog/remove-favorite' => 'favorite/remove',
                'catalog/favorites-count' => 'favorite/count',
                'favorite/merge-guest' => 'favorite/merge-guest',
                'search' => 'catalog/search/index',
                'catalog/search' => 'catalog/catalog/search',
                'catalog/filter' => 'catalog/catalog/filter',
                'catalog/load-more' => 'catalog/catalog/load-more',
                'catalog/quick-view/<id:\d+>' => 'catalog/catalog/quick-view',
                'catalog/quick-order' => 'catalog/catalog/quick-order',
                'catalog/submit-review' => 'catalog/catalog/submit-review',
                'catalog/submit-question' => 'catalog/catalog/submit-question',

                // Корзина API
                'cart' => 'cart/cart/index',
                'cart/add' => 'cart/cart/add',
                'cart/update' => 'cart/cart/update',
                'cart/remove/<id:\d+>' => 'cart/cart/remove',
                'cart/clear' => 'cart/cart/clear',
                'cart/count' => 'cart/cart/count',
                'cart/has-product' => 'cart/cart/has-product',
                'cart/drawer-items' => 'cart/cart/drawer-items',

                // Сравнение товаров
                'compare' => 'compare/compare/index',
                'compare/add' => 'compare/compare/add',
                'compare/remove' => 'compare/compare/remove',
                'compare/clear' => 'compare/compare/clear',
                'compare/count' => 'compare/compare/count',
                
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
                'account/favorites' => 'account/account/wishlist',

                // Программа лояльности и возвраты в личном кабинете
                'account/loyalty' => 'account/account/loyalty',
                'account/loyalty/balance' => 'account/loyalty/balance',
                'account/returns' => 'account/return/index',
                'account/returns/create' => 'account/return/create',
                'account/returns/<id:\d+>' => 'account/return/view',
                'account/tracking' => 'account/account/orders',
                'account/save-passport' => 'account/account/save-passport',

                // Публичный просмотр покупателя (для админки)
                'customer/view' => 'admin/customer/view',
                'customer/update' => 'admin/customer/update',
                
                // Feedback to director
                'feedback' => 'feedback/index',
                'feedback/submit' => 'feedback/submit',

                // Sitemap
                'sitemap.xml' => 'sitemap/index',
                
                // SEF фильтрация (умный фильтр) - ДОЛЖЕН быть после явных роутов
                // actionFilterSef был удалён; SEF-урл переадресуется на обычный фильтр
                'catalog/filter/<filters:[\w\-/]+>' => 'catalog/catalog/filter',
                
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
                'admin/order/<id:\d+>/update-items' => 'admin/order/update-items',
                'admin/order/export' => 'admin/order/export',
                'admin/order/clean-bad-import' => 'admin/order/clean-bad-import',
                // DobroPost
                'admin/order/<id:\d+>/send-to-dp' => 'admin/order/send-to-dp',
                'admin/order/<id:\d+>/dp-status' => 'admin/order/dp-status',
                'admin/order/<id:\d+>/retry-dp' => 'admin/order/retry-dp',
                
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
                'admin/settings/integrations' => 'admin/settings/integrations', // redirects → /admin/plugin
                'admin/plugin' => 'admin/plugin/index',
                // MoySklad sync
                'admin/plugin/moysklad' => 'admin/moysklad/index',
                'admin/plugin/moysklad/test-connection'  => 'admin/moysklad/test-connection',
                'admin/plugin/moysklad/save-credentials' => 'admin/moysklad/save-credentials',
                'admin/plugin/moysklad/save-mapping'     => 'admin/moysklad/save-mapping',
                'admin/plugin/moysklad/push-all'         => 'admin/moysklad/push-all',
                'admin/plugin/moysklad/pull'             => 'admin/moysklad/pull',
                'admin/plugin/moysklad/push-order'       => 'admin/moysklad/push-order',
                'admin/plugin/moysklad/sync-info'        => 'admin/moysklad/sync-info',
                'admin/plugin/moysklad/webhooks'         => 'admin/moysklad/webhooks',
                'admin/plugin/moysklad/register-webhook' => 'admin/moysklad/register-webhook',
                'admin/plugin/moysklad/delete-webhook'   => 'admin/moysklad/delete-webhook',
                'admin/plugin/moysklad/periodic-sync'    => 'admin/moysklad/periodic-sync',
                'admin/plugin/moysklad/webhook-status'   => 'admin/moysklad/webhook-status',
                'admin/plugin/moysklad/save-status-mapping' => 'admin/moysklad/save-status-mapping',
                'admin/plugin/moysklad/save-settings'    => 'admin/moysklad/save-settings',
                'admin/plugin/moysklad/sync-log'         => 'admin/moysklad/sync-log',
                'admin/moysklad/webhook'                 => 'admin/moysklad/webhook',
                'admin/moysklad/ms-images'               => 'admin/moysklad/ms-images',
                'admin/moysklad/ms-image'                => 'admin/moysklad/ms-image',
                // AmoCRM Widget API (CSRF disabled in controller)
                'api/amocrm/create-order' => 'api/amocrm-order/create-order',
                'api/amocrm/products'     => 'api/amocrm-order/products',
                // AmoCRM plugin pages
                'admin/plugin/amocrm'            => 'admin/plugin/amocrm',
                'admin/plugin/amocrm-widget'     => 'admin/plugin/amocrm-widget',
                'admin/plugin/amocrm-widget/key' => 'admin/plugin/amocrm-widget-key',
                // Lamoda Parser plugin pages
                'admin/plugin/lamoda-parser'          => 'admin/plugin/lamoda-parser',
                'admin/plugin/lamoda-parser/run'      => 'admin/plugin/lamoda-run',
                'admin/plugin/lamoda-parser/status'   => 'admin/plugin/lamoda-status',
                'admin/plugin/lamoda-parser/schedule' => 'admin/plugin/lamoda-save-schedule',
                'admin/plugin/telegram' => 'admin/plugin/telegram',
                'admin/plugin/currency' => 'admin/plugin/currency',
                'admin/plugin/dobropost' => 'admin/plugin/dobropost',
                'admin/plugin/import-dobropost' => 'admin/plugin/import-dobropost',
                'admin/plugin/test-tracking' => 'admin/plugin/test-tracking',
                'admin/plugin/europochta' => 'admin/plugin/europochta',
                'admin/plugin/europochta/save' => 'admin/plugin/save-europochta',
                'admin/plugin/belpochta' => 'admin/plugin/belpochta',
                'admin/plugin/belpochta/save' => 'admin/plugin/save-belpochta',
                'admin/plugin/cdek' => 'admin/plugin/cdek',
                'admin/plugin/cdek/save' => 'admin/plugin/save-cdek',
                'admin/plugin/rocketsms' => 'admin/plugin/rocketsms',
                'admin/plugin/rocketsms/save' => 'admin/plugin/save-rocketsms',
                'admin/plugin/rocketsms/test' => 'admin/plugin/test-rocketsms',
                // Activity log
                'admin/activity-log' => 'admin/activity-log/index',
                // Finance
                'admin/finance'                  => 'admin/finance/payments',
                'admin/finance/payments'         => 'admin/finance/payments',
                'admin/finance/create-payment'   => 'admin/finance/create-payment',
                'admin/finance/confirm-payment'  => 'admin/finance/confirm-payment',
                'admin/finance/expenses'         => 'admin/finance/expenses',
                'admin/finance/create-expense'   => 'admin/finance/create-expense',
                'admin/finance/pnl'              => 'admin/finance/pnl',
                'admin/finance/margin'           => 'admin/finance/margin',
                // Procurement
                'admin/procurement'                       => 'admin/procurement/index',
                'admin/procurement/suppliers'             => 'admin/procurement/suppliers',
                'admin/procurement/supplier-save'         => 'admin/procurement/supplier-save',
                'admin/procurement/supplier-delete'       => 'admin/procurement/supplier-delete',
                'admin/procurement/create'                => 'admin/procurement/create',
                'admin/procurement/view/<id:\d+>'         => 'admin/procurement/view',
                'admin/procurement/update-status'         => 'admin/procurement/update-status',
                'admin/procurement/receiving'             => 'admin/procurement/receiving',
                'admin/procurement/receive-items'                  => 'admin/procurement/receive-items',
                'admin/procurement/returns'                        => 'admin/procurement/returns',
                'admin/procurement/create-return/<purchaseOrderId:\d+>' => 'admin/procurement/create-return',
                'admin/procurement/view-return/<id:\d+>'           => 'admin/procurement/view-return',
                'admin/procurement/update-return-status'           => 'admin/procurement/update-return-status',
                // Automation triggers
                'admin/settings/triggers' => 'admin/automation/index',
                'admin/settings/triggers/create' => 'admin/automation/create',
                'admin/settings/triggers/log' => 'admin/automation/log',
                'admin/settings/triggers/<id:\d+>/edit' => 'admin/automation/update',
                'admin/settings/triggers/<id:\d+>/delete' => 'admin/automation/delete',
                'admin/settings/triggers/<id:\d+>/toggle' => 'admin/automation/toggle',
                'admin/settings/save' => 'admin/settings/save',
                'admin/settings/save-shipping' => 'admin/settings/save-shipping',
                'admin/settings/statuses' => 'admin/settings/statuses',
                'admin/settings/save-statuses' => 'admin/settings/save-statuses',
                
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

                // Feedback from customers
                'admin/feedback' => 'admin/feedback/index',
                'admin/feedback/reply' => 'admin/feedback/reply',
                'admin/feedback/delete/<id:\d+>' => 'admin/feedback/delete',
                
                // Search
                'admin/search' => 'admin/search/global',
                'admin/search/orders' => 'admin/search/orders',
                'admin/profile' => 'admin/dashboard/profile',
                
                // Characteristics
                'admin/characteristic' => 'admin/characteristic/index',
                'admin/characteristic/guide' => 'admin/characteristic/guide',
                
                // Customers (покупатели) — /admin/client is an alias
                'admin/client' => 'admin/customer/index',
                'admin/customer' => 'admin/customer/index',
                'admin/customer/<id:\d+>' => 'admin/customer/view',
                'admin/customer/<id:\d+>/update' => 'admin/customer/update',
                'admin/customer/<id:\d+>/delete' => 'admin/customer/delete',
                'admin/customer/<id:\d+>/toggle-status' => 'admin/customer/toggle-status',
                'admin/customer/<id:\d+>/reset-password' => 'admin/customer/reset-password',
                'admin/customer/<id:\d+>/link-orders' => 'admin/customer/link-orders',
                'admin/customer/export' => 'admin/customer/export',
                'admin/customer/quick-view' => 'admin/customer/quick-view',
                'admin/customer/adjust-points' => 'admin/customer/adjust-points',
                'admin/customer/add-tag' => 'admin/customer/add-tag',
                'admin/customer/remove-tag' => 'admin/customer/remove-tag',
                'admin/customer/add-note' => 'admin/customer/add-note',
                
                // Import
                'admin/import' => 'admin/import/index',
                'admin/import/upload' => 'admin/import/upload',
                'admin/import/source' => 'admin/import/source',
                'admin/import/source/<id:\d+>' => 'admin/import/source',
                'admin/import/run/<sourceId:\d+>' => 'admin/import/run',
                'admin/import/run-all' => 'admin/import/run-all',
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
            'class' => 'app\infrastructure\middleware\SecurityHeadersMiddleware',
        ],
        'redirectMiddleware' => [
            'class' => 'app\backend\modules\seo\components\RedirectMiddleware',
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
