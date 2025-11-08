<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\Response;

/**
 * HttpCacheHeaders - Управление HTTP кэш-заголовками
 * 
 * Функции:
 * - Cache-Control headers для статических ресурсов
 * - ETag генерация и валидация
 * - Last-Modified handling
 * - CDN-friendly настройки
 * - Vary headers для адаптивного контента
 * 
 * Использование:
 * ```php
 * HttpCacheHeaders::setCacheHeaders($this->response, HttpCacheHeaders::PROFILE_PUBLIC_LONG);
 * ```
 */
class HttpCacheHeaders extends Component
{
    /**
     * Профили кэширования
     */
    const PROFILE_NO_CACHE = 'no-cache';           // Без кэша
    const PROFILE_PRIVATE_SHORT = 'private-short';  // Приватный, 5 мин
    const PROFILE_PRIVATE_MEDIUM = 'private-medium'; // Приватный, 30 мин
    const PROFILE_PUBLIC_SHORT = 'public-short';    // Публичный, 5 мин
    const PROFILE_PUBLIC_MEDIUM = 'public-medium';  // Публичный, 1 час
    const PROFILE_PUBLIC_LONG = 'public-long';      // Публичный, 24 часа
    const PROFILE_PUBLIC_IMMUTABLE = 'immutable';   // Иммутабельный, 1 год
    
    /**
     * Конфигурация профилей
     */
    protected static $profiles = [
        self::PROFILE_NO_CACHE => [
            'cache-control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'pragma' => 'no-cache',
        ],
        self::PROFILE_PRIVATE_SHORT => [
            'cache-control' => 'private, max-age=300', // 5 минут
        ],
        self::PROFILE_PRIVATE_MEDIUM => [
            'cache-control' => 'private, max-age=1800', // 30 минут
        ],
        self::PROFILE_PUBLIC_SHORT => [
            'cache-control' => 'public, max-age=300, s-maxage=600', // 5 мин + 10 мин CDN
        ],
        self::PROFILE_PUBLIC_MEDIUM => [
            'cache-control' => 'public, max-age=3600, s-maxage=7200', // 1 час + 2 часа CDN
        ],
        self::PROFILE_PUBLIC_LONG => [
            'cache-control' => 'public, max-age=86400, s-maxage=172800', // 1 день + 2 дня CDN
        ],
        self::PROFILE_PUBLIC_IMMUTABLE => [
            'cache-control' => 'public, max-age=31536000, immutable', // 1 год, иммутабельный
        ],
    ];
    
    /**
     * Установить заголовки кэша по профилю
     * 
     * @param Response $response
     * @param string $profile
     * @param array $options Дополнительные опции (etag, last-modified, vary)
     */
    public static function setCacheHeaders($response, $profile = self::PROFILE_PUBLIC_MEDIUM, $options = [])
    {
        if (!isset(self::$profiles[$profile])) {
            $profile = self::PROFILE_PUBLIC_MEDIUM;
        }
        
        $config = self::$profiles[$profile];
        
        // Cache-Control
        if (isset($config['cache-control'])) {
            $response->headers->set('Cache-Control', $config['cache-control']);
        }
        
        // Pragma (для старых браузеров)
        if (isset($config['pragma'])) {
            $response->headers->set('Pragma', $config['pragma']);
        }
        
        // ETag
        if (isset($options['etag'])) {
            $response->headers->set('ETag', self::generateETag($options['etag']));
        }
        
        // Last-Modified
        if (isset($options['last_modified'])) {
            $lastModified = is_int($options['last_modified']) 
                ? $options['last_modified'] 
                : strtotime($options['last_modified']);
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        }
        
        // Vary (для адаптивного контента)
        if (isset($options['vary'])) {
            $vary = is_array($options['vary']) ? implode(', ', $options['vary']) : $options['vary'];
            $response->headers->set('Vary', $vary);
        }
        
        // CDN headers
        if (isset($options['cdn']) && $options['cdn']) {
            self::setCdnHeaders($response);
        }
    }
    
    /**
     * Установить заголовки для статических ресурсов
     * 
     * @param Response $response
     * @param string $fileExtension
     */
    public static function setStaticAssetHeaders($response, $fileExtension)
    {
        $profile = self::getProfileForExtension($fileExtension);
        
        self::setCacheHeaders($response, $profile, [
            'cdn' => true,
        ]);
    }
    
    /**
     * Установить заголовки для API ответов
     * 
     * @param Response $response
     * @param bool $cacheable Можно ли кэшировать
     * @param int $maxAge Время жизни в секундах
     */
    public static function setApiHeaders($response, $cacheable = false, $maxAge = 300)
    {
        if (!$cacheable) {
            self::setCacheHeaders($response, self::PROFILE_NO_CACHE);
            return;
        }
        
        // Для кэшируемых API используем кастомный Cache-Control
        $response->headers->set('Cache-Control', "private, max-age=$maxAge");
        $response->headers->set('Vary', 'Accept, Accept-Encoding');
    }
    
    /**
     * Установить заголовки для страниц каталога
     * 
     * @param Response $response
     * @param array $options
     */
    public static function setCatalogHeaders($response, $options = [])
    {
        // Каталог кэшируется публично, но с коротким TTL
        self::setCacheHeaders($response, self::PROFILE_PUBLIC_SHORT, array_merge([
            'vary' => ['Accept-Encoding', 'Cookie'], // Учитываем авторизацию
            'cdn' => true,
        ], $options));
    }
    
    /**
     * Установить заголовки для страницы товара
     * 
     * @param Response $response
     * @param int $productId
     * @param int $updatedAt Timestamp обновления
     */
    public static function setProductHeaders($response, $productId, $updatedAt = null)
    {
        $options = [
            'vary' => ['Accept-Encoding', 'Cookie'],
            'cdn' => true,
        ];
        
        if ($updatedAt) {
            $options['last_modified'] = $updatedAt;
            $options['etag'] = "product-{$productId}-{$updatedAt}";
        }
        
        self::setCacheHeaders($response, self::PROFILE_PUBLIC_MEDIUM, $options);
    }
    
    /**
     * Проверить условный GET запрос (304 Not Modified)
     * 
     * @param string $etag
     * @param int|null $lastModified Timestamp
     * @return bool true если нужно вернуть 304
     */
    public static function checkNotModified($etag = null, $lastModified = null)
    {
        $request = Yii::$app->request;
        
        // Проверка If-None-Match (ETag)
        if ($etag !== null) {
            $clientEtag = $request->headers->get('If-None-Match');
            if ($clientEtag && self::generateETag($etag) === $clientEtag) {
                return true;
            }
        }
        
        // Проверка If-Modified-Since
        if ($lastModified !== null) {
            $clientTime = $request->headers->get('If-Modified-Since');
            if ($clientTime) {
                $clientTimestamp = strtotime($clientTime);
                if ($clientTimestamp >= $lastModified) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Вернуть 304 Not Modified
     * 
     * @param Response $response
     * @param string|null $etag
     * @param int|null $lastModified
     */
    public static function sendNotModified($response, $etag = null, $lastModified = null)
    {
        $response->statusCode = 304;
        $response->content = null;
        
        if ($etag) {
            $response->headers->set('ETag', self::generateETag($etag));
        }
        
        if ($lastModified) {
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        }
    }
    
    /**
     * Генерация ETag
     * 
     * @param mixed $data Данные для хэширования
     * @return string
     */
    protected static function generateETag($data)
    {
        if (is_string($data)) {
            return '"' . md5($data) . '"';
        }
        
        return '"' . md5(serialize($data)) . '"';
    }
    
    /**
     * Определить профиль по расширению файла
     * 
     * @param string $extension
     * @return string
     */
    protected static function getProfileForExtension($extension)
    {
        // Иммутабельные ресурсы (с хэшем в имени)
        $immutable = ['woff', 'woff2', 'ttf', 'eot'];
        if (in_array($extension, $immutable)) {
            return self::PROFILE_PUBLIC_IMMUTABLE;
        }
        
        // Долгое кэширование
        $longCache = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];
        if (in_array($extension, $longCache)) {
            return self::PROFILE_PUBLIC_LONG;
        }
        
        // Среднее кэширование (JS, CSS)
        $mediumCache = ['js', 'css'];
        if (in_array($extension, $mediumCache)) {
            return self::PROFILE_PUBLIC_MEDIUM;
        }
        
        return self::PROFILE_PUBLIC_SHORT;
    }
    
    /**
     * Установить специфичные заголовки для CDN
     * 
     * @param Response $response
     */
    protected static function setCdnHeaders($response)
    {
        // Cloudflare
        $response->headers->set('CDN-Cache-Control', 'public, max-age=31536000');
        
        // Fastly
        $response->headers->set('Surrogate-Control', 'max-age=31536000');
        
        // Общий Surrogate-Key для групповой инвалидации
        $response->headers->set('Surrogate-Key', 'static-assets');
    }
    
    /**
     * Очистить все заголовки кэша
     * 
     * @param Response $response
     */
    public static function clearCacheHeaders($response)
    {
        $response->headers->remove('Cache-Control');
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');
        $response->headers->remove('ETag');
        $response->headers->remove('Last-Modified');
        $response->headers->remove('Vary');
    }
    
    /**
     * Получить рекомендации по настройке веб-сервера
     * 
     * @return array
     */
    public static function getServerConfigRecommendations()
    {
        return [
            'nginx' => <<<'NGINX'
# Nginx конфигурация для статических ресурсов
location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    add_header Vary "Accept-Encoding";
    access_log off;
}

# Gzip компрессия
gzip on;
gzip_vary on;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss image/svg+xml;
gzip_min_length 1000;

# Brotli (если установлен модуль)
brotli on;
brotli_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss image/svg+xml;
NGINX
            ,
            'apache' => <<<'APACHE'
# Apache .htaccess для статических ресурсов
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
    <FilesMatch "\.(jpg|jpeg|png|gif|css|js|woff|woff2)$">
        Header set Cache-Control "public"
        Header set Vary "Accept-Encoding"
    </FilesMatch>
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript application/json
</IfModule>
APACHE
        ];
    }
}
