<?php

// Load bootstrap (env helper)
require __DIR__ . '/bootstrap.php';

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'order-management-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
        '@webroot' => '@app/web',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'settings' => [
            'class' => 'app\components\Settings',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
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
            'class' => 'yii\web\UrlManager',
            'baseUrl' => 'http://localhost:8080',
            'scriptUrl' => 'http://localhost:8080',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
