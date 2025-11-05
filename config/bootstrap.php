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

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

if (file_exists($envPath)) {
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
