<?php

namespace app\components;

use Yii;
use yii\base\Component;

/**
 * CdnHelper - Хелпер для работы с CDN
 * 
 * Поддерживает:
 * - CloudFlare
 * - BunnyCDN  
 * - AWS CloudFront
 * - KeyCDN
 * - Собственный CDN
 * 
 * Настройка в config/web.php:
 * 'components' => [
 *     'cdn' => [
 *         'class' => 'app\components\CdnHelper',
 *         'enabled' => true,
 *         'baseUrl' => 'https://cdn.sneaker-head.by',
 *     ],
 * ]
 */
class CdnHelper extends Component
{
    /**
     * @var bool Включен ли CDN
     */
    public bool $enabled = false;
    
    /**
     * @var string Базовый URL CDN
     */
    public string $baseUrl = '';
    
    /**
     * @var string Тип CDN (cloudflare, bunny, cloudfront, keycdn, custom)
     */
    public string $type = 'custom';
    
    /**
     * @var array Расширения файлов для CDN
     */
    public array $extensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico',
        'css', 'js',
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        'mp4', 'webm', 'ogg',
        'pdf'
    ];
    
    /**
     * @var array Директории для CDN
     */
    public array $directories = [
        '/images/',
        '/css/',
        '/js/',
        '/fonts/',
        '/uploads/',
    ];
    
    /**
     * @var bool Использовать версионирование
     */
    public bool $useVersioning = true;
    
    /**
     * @var string|null Версия для cache busting
     */
    public ?string $version = null;

    /**
     * Инициализация компонента
     */
    public function init(): void
    {
        parent::init();
        
        // Автоматическое включение CDN в production
        if ($this->enabled === false && !YII_ENV_DEV) {
            $this->enabled = !empty(env('CDN_URL'));
            $this->baseUrl = env('CDN_URL', '');
        }
        
        // Версия для cache busting
        if ($this->version === null) {
            $this->version = $this->getAppVersion();
        }
    }

    /**
     * Получить CDN URL для ресурса
     * 
     * @param string $path Путь к ресурсу
     * @param bool $absolute Возвращать абсолютный URL
     * @return string
     */
    public function url(string $path, bool $absolute = true): string
    {
        // Если CDN выключен - возвращаем оригинальный путь
        if (!$this->enabled || empty($this->baseUrl)) {
            return $absolute ? Yii::$app->request->hostInfo . $path : $path;
        }
        
        // Проверяем, подходит ли файл для CDN
        if (!$this->shouldUseCdn($path)) {
            return $absolute ? Yii::$app->request->hostInfo . $path : $path;
        }
        
        // Формируем CDN URL
        $cdnUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
        
        // Добавляем версию для cache busting
        if ($this->useVersioning && $this->version) {
            $separator = strpos($cdnUrl, '?') !== false ? '&' : '?';
            $cdnUrl .= $separator . 'v=' . $this->version;
        }
        
        return $cdnUrl;
    }

    /**
     * Получить CDN URL для изображения с трансформацией
     * 
     * @param string $path Путь к изображению
     * @param array $options Опции трансформации
     * @return string
     */
    public function image(string $path, array $options = []): string
    {
        if (!$this->enabled || empty($this->baseUrl)) {
            return $path;
        }
        
        $cdnUrl = $this->url($path, true);
        
        // Добавляем параметры трансформации в зависимости от типа CDN
        if (!empty($options)) {
            $cdnUrl = $this->addTransformParams($cdnUrl, $options);
        }
        
        return $cdnUrl;
    }

    /**
     * Получить srcset для responsive изображений
     * 
     * @param string $path Путь к изображению
     * @param array $widths Массив ширин
     * @return string
     */
    public function srcset(string $path, array $widths = [320, 640, 960, 1280, 1920]): string
    {
        $srcset = [];
        
        foreach ($widths as $width) {
            $url = $this->image($path, ['width' => $width]);
            $srcset[] = "{$url} {$width}w";
        }
        
        return implode(', ', $srcset);
    }

    /**
     * Проверить, должен ли файл обслуживаться через CDN
     * 
     * @param string $path
     * @return bool
     */
    protected function shouldUseCdn(string $path): bool
    {
        // Проверяем расширение
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->extensions)) {
            return false;
        }
        
        // Проверяем директорию
        foreach ($this->directories as $dir) {
            if (strpos($path, $dir) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Добавить параметры трансформации
     * 
     * @param string $url
     * @param array $options
     * @return string
     */
    protected function addTransformParams(string $url, array $options): string
    {
        $params = [];
        
        switch ($this->type) {
            case 'cloudflare':
                // Cloudflare Image Resizing
                if (isset($options['width'])) {
                    $params[] = "width={$options['width']}";
                }
                if (isset($options['height'])) {
                    $params[] = "height={$options['height']}";
                }
                if (isset($options['quality'])) {
                    $params[] = "quality={$options['quality']}";
                }
                if (isset($options['format'])) {
                    $params[] = "format={$options['format']}";
                }
                break;
                
            case 'bunny':
                // BunnyCDN Optimizer
                if (isset($options['width'])) {
                    $params[] = "width={$options['width']}";
                }
                if (isset($options['height'])) {
                    $params[] = "height={$options['height']}";
                }
                if (isset($options['quality'])) {
                    $params[] = "quality={$options['quality']}";
                }
                break;
                
            case 'cloudfront':
                // AWS CloudFront with Lambda@Edge
                if (isset($options['width'])) {
                    $params[] = "w={$options['width']}";
                }
                if (isset($options['height'])) {
                    $params[] = "h={$options['height']}";
                }
                break;
                
            default:
                // Custom CDN - просто добавляем параметры
                foreach ($options as $key => $value) {
                    $params[] = "{$key}={$value}";
                }
        }
        
        if (empty($params)) {
            return $url;
        }
        
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . implode('&', $params);
    }

    /**
     * Получить версию приложения
     * 
     * @return string
     */
    protected function getAppVersion(): string
    {
        // Из переменной окружения
        $version = env('APP_VERSION');
        if ($version) {
            return $version;
        }
        
        // Из git
        $gitVersion = @shell_exec('git rev-parse --short HEAD 2>/dev/null');
        if ($gitVersion) {
            return trim($gitVersion);
        }
        
        // Timestamp файла
        return (string)filemtime(Yii::getAlias('@webroot/index.php'));
    }

    /**
     * Очистить кэш CDN (для поддерживающих API)
     * 
     * @param array $paths Пути для очистки
     * @return bool
     */
    public function purgeCache(array $paths = []): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        // Реализация зависит от типа CDN
        switch ($this->type) {
            case 'cloudflare':
                return $this->purgeCloudflare($paths);
            case 'bunny':
                return $this->purgeBunny($paths);
            default:
                Yii::warning('CDN purge not implemented for type: ' . $this->type);
                return false;
        }
    }

    /**
     * Очистка кэша CloudFlare
     */
    protected function purgeCloudflare(array $paths): bool
    {
        $zoneId = env('CLOUDFLARE_ZONE_ID');
        $apiToken = env('CLOUDFLARE_API_TOKEN');
        
        if (!$zoneId || !$apiToken) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$apiToken}",
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode(
                empty($paths) 
                    ? ['purge_everything' => true]
                    : ['files' => array_map(fn($p) => $this->baseUrl . $p, $paths)]
            ),
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }

    /**
     * Очистка кэша BunnyCDN
     */
    protected function purgeBunny(array $paths): bool
    {
        $apiKey = env('BUNNY_API_KEY');
        $pullZoneId = env('BUNNY_PULLZONE_ID');
        
        if (!$apiKey || !$pullZoneId) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.bunny.net/pullzone/{$pullZoneId}/purgeCache",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "AccessKey: {$apiKey}",
                "Content-Type: application/json",
            ],
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 204;
    }
}
