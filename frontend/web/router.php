<?php
/**
 * PHP built-in dev server router.
 * Returns false for existing static files so the server serves them directly.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

require __DIR__ . '/index.php';
