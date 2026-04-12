<?php
/**
 * PHP built-in dev server router.
 * Returns false for existing static files so the server serves them directly.
 * Everything else goes through the Yii2 entry point.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$docRoot = __DIR__ . '/frontend/web';
$file = $docRoot . $uri;

// Serve static files directly
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Route through Yii2
$_SERVER['SCRIPT_FILENAME'] = $docRoot . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $docRoot . '/index.php';
