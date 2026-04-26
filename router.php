<?php
/**
 * PHP built-in dev server router.
 *
 * `return false` sends the PHP server back to the *launch directory* (project
 * root), but static assets live under frontend/web/.  So we must read and
 * stream them ourselves instead of relying on the built-in file serving.
 */

$uri     = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$webRoot = __DIR__ . '/frontend/web';
$file    = $webRoot . $uri;

if ($uri !== '/' && file_exists($file) && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    static $mime = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'mjs'   => 'application/javascript',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'map'   => 'application/json',
        'txt'   => 'text/plain',
        'xml'   => 'text/xml',
        'pdf'   => 'application/pdf',
    ];
    header('Content-Type: ' . ($mime[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream')));
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: public, max-age=3600');
    readfile($file);
    exit;
}

$_SERVER['SCRIPT_FILENAME'] = $webRoot . '/index.php';
$_SERVER['SCRIPT_NAME']     = '/index.php';
require $webRoot . '/index.php';
