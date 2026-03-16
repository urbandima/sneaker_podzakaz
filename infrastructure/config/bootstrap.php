<?php

use Dotenv\Dotenv;

if (!function_exists('env')) {
    /**
     * Получить значение из .env/.server с поддержкой дефолта и булевых значений.
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        if ($value === 'true' || $value === '(true)') {
            return true;
        }
        if ($value === 'false' || $value === '(false)') {
            return false;
        }
        if ($value === 'empty' || $value === '(empty)') {
            return '';
        }
        if ($value === 'null' || $value === '(null)') {
            return null;
        }

        if (is_numeric($value)) {
            return strpos($value, '.') === false ? (int) $value : (float) $value;
        }

        return $value;
    }
}

$projectRoot = dirname(__DIR__, 2);
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

if (file_exists($envPath) && class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv::createImmutable($projectRoot);
    $dotenv->safeLoad();
}

if (!defined('YII_ENV') && env('YII_ENV')) {
    define('YII_ENV', env('YII_ENV'));
}

if (!defined('YII_DEBUG')) {
    $envDebug = env('YII_DEBUG');
    if ($envDebug !== null) {
        define('YII_DEBUG', (bool) $envDebug);
    }
}

// Регистрация alias для новой архитектуры 2026 (frontend/backend/infrastructure)
// Отложим установку алиасов до после загрузки Yii
if (class_exists('Yii')) {
    Yii::setAlias('@backend', dirname(__DIR__, 2) . '/backend');
    Yii::setAlias('@frontend', dirname(__DIR__, 2) . '/frontend');
    Yii::setAlias('@infrastructure', dirname(__DIR__, 2) . '/infrastructure');
    Yii::setAlias('@webroot', dirname(__DIR__, 2) . '/frontend/web');
}
