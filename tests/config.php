<?php
/**
 * Конфигурация тестового окружения СНИКЕРХЭД
 */

$params = require __DIR__ . '/../infrastructure/config/params.php';

return [
    'id' => 'sneakerhead-tests',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Minsk',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'test-cookie-validation-key-for-testing-only',
            'enableCsrfValidation' => false,
        ],
        'cache' => [
            'class' => 'yii\caching\ArrayCache',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => env('DB_DSN', 'mysql:host=127.0.0.1;dbname=sneaker_test'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'useFileTransport' => true,
        ],
        'assetManager' => [
            'basePath' => '@runtime/assets',
            'baseUrl' => '/',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
        ],
        'user' => [
            'identityClass' => 'app\backend\modules\catalog\models\User',
            'enableAutoLogin' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
    ],
    'params' => $params,
];
