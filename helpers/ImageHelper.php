<?php
namespace app\helpers;

use Yii;
use yii\helpers\Html;

/**
 * ImageHelper - помощник для работы с изображениями
 * 
 * Автоматически использует WebP с fallback на оригинальное изображение
 */
class ImageHelper
{
    /**
     * Создать <picture> тег с WebP и fallback
     * 
     * @param string $src Путь к изображению
     * @param array $options HTML атрибуты для <img>
     * @param bool $lazy Использовать ли lazy loading
     * @return string HTML код
     * 
     * Пример:
     * echo ImageHelper::picture('/uploads/product.jpg', ['alt' => 'Product', 'class' => 'img-fluid']);
     */
    public static function picture($src, $options = [], $lazy = true)
    {
        if (empty($src)) {
            return self::placeholder($options);
        }
        
        // Добавляем lazy loading
        if ($lazy && !isset($options['loading'])) {
            $options['loading'] = 'lazy';
        }
        
        // Проверяем наличие alt
        if (!isset($options['alt'])) {
            $options['alt'] = '';
        }
        
        // Путь к WebP версии
        $webpSrc = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $src);
        
        // Проверяем существование WebP
        $webpExists = false;
        if (strpos($webpSrc, 'http') === 0) {
            // Внешний URL - проверяем через headers
            $webpExists = self::urlExists($webpSrc);
        } else {
            // Локальный файл
            $webpPath = Yii::getAlias('@webroot' . $webpSrc);
            $webpExists = file_exists($webpPath);
        }
        
        // Если WebP существует, создаём <picture>
        if ($webpExists) {
            $imgTag = Html::tag('img', '', array_merge($options, ['src' => $src]));
            $sourceTag = Html::tag('source', '', ['srcset' => $webpSrc, 'type' => 'image/webp']);
            
            return Html::tag('picture', $sourceTag . $imgTag);
        }
        
        // Иначе просто <img>
        return Html::tag('img', '', array_merge($options, ['src' => $src]));
    }
    
    /**
     * Создать responsive <picture> с разными размерами
     * 
     * @param array $sources Массив источников с размерами
     * @param string $defaultSrc Дефолтное изображение
     * @param array $options HTML атрибуты
     * @return string HTML код
     * 
     * Пример:
     * echo ImageHelper::responsivePicture([
     *     ['src' => '/uploads/product-320.jpg', 'media' => '(max-width: 320px)'],
     *     ['src' => '/uploads/product-768.jpg', 'media' => '(max-width: 768px)'],
     * ], '/uploads/product.jpg', ['alt' => 'Product']);
     */
    public static function responsivePicture($sources, $defaultSrc, $options = [])
    {
        if (!isset($options['alt'])) {
            $options['alt'] = '';
        }
        
        if (!isset($options['loading'])) {
            $options['loading'] = 'lazy';
        }
        
        $sourceTags = '';
        
        foreach ($sources as $source) {
            $src = $source['src'];
            $media = $source['media'] ?? '';
            
            // WebP версия
            $webpSrc = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $src);
            
            $sourceAttrs = ['srcset' => $webpSrc, 'type' => 'image/webp'];
            if ($media) {
                $sourceAttrs['media'] = $media;
            }
            
            $sourceTags .= Html::tag('source', '', $sourceAttrs);
            
            // Fallback оригинальный формат
            $sourceAttrs = ['srcset' => $src];
            if ($media) {
                $sourceAttrs['media'] = $media;
            }
            
            $sourceTags .= Html::tag('source', '', $sourceAttrs);
        }
        
        // Дефолтный <img>
        $imgTag = Html::tag('img', '', array_merge($options, ['src' => $defaultSrc]));
        
        return Html::tag('picture', $sourceTags . $imgTag);
    }
    
    /**
     * Получить WebP версию URL
     * 
     * @param string $src Оригинальный URL
     * @return string WebP URL или оригинальный если WebP не существует
     */
    public static function getWebpUrl($src)
    {
        $webpSrc = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $src);
        
        // Проверяем существование
        if (strpos($webpSrc, 'http') === 0) {
            return self::urlExists($webpSrc) ? $webpSrc : $src;
        }
        
        $webpPath = Yii::getAlias('@webroot' . $webpSrc);
        return file_exists($webpPath) ? $webpSrc : $src;
    }
    
    /**
     * Создать placeholder изображение
     * 
     * @param array $options HTML атрибуты
     * @return string HTML код
     */
    public static function placeholder($options = [])
    {
        $width = $options['width'] ?? 800;
        $height = $options['height'] ?? 600;
        $text = $options['text'] ?? 'No Image';
        
        // SVG placeholder
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">'
            . '<rect width="100%" height="100%" fill="#f3f4f6"/>'
            . '<text x="50%" y="50%" font-family="Arial" font-size="24" fill="#9ca3af" '
            . 'text-anchor="middle" dominant-baseline="middle">' . $text . '</text>'
            . '</svg>';
        
        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        
        unset($options['text']);
        
        return Html::tag('img', '', array_merge($options, ['src' => $dataUri, 'alt' => $text]));
    }
    
    /**
     * Создать thumbnail изображение
     * 
     * @param string $src Путь к изображению
     * @param int $width Ширина
     * @param int $height Высота
     * @param array $options HTML атрибуты
     * @return string HTML код
     */
    public static function thumbnail($src, $width = 200, $height = 200, $options = [])
    {
        // Можно интегрировать с yii2-imagine или другими библиотеками
        // Пока просто используем оригинал с размерами
        
        $options['width'] = $width;
        $options['height'] = $height;
        $options['style'] = isset($options['style']) 
            ? $options['style'] . '; object-fit: cover;' 
            : 'object-fit: cover;';
        
        return self::picture($src, $options);
    }
    
    /**
     * Проверить существование URL
     * 
     * @param string $url URL для проверки
     * @return bool
     */
    private static function urlExists($url)
    {
        $headers = @get_headers($url);
        return $headers && strpos($headers[0], '200') !== false;
    }
    
    /**
     * Получить размер изображения
     * 
     * @param string $src Путь к изображению
     * @return array|false ['width' => int, 'height' => int] или false
     */
    public static function getImageSize($src)
    {
        if (strpos($src, 'http') === 0) {
            $size = @getimagesize($src);
        } else {
            $path = Yii::getAlias('@webroot' . $src);
            $size = file_exists($path) ? @getimagesize($path) : false;
        }
        
        if ($size) {
            return ['width' => $size[0], 'height' => $size[1]];
        }
        
        return false;
    }
    
    /**
     * Оптимизировать изображение (уменьшить размер)
     * 
     * @param string $src Путь к изображению
     * @param int $maxWidth Максимальная ширина
     * @param int $quality Качество (0-100)
     * @return bool
     */
    public static function optimize($src, $maxWidth = 1920, $quality = 85)
    {
        $path = Yii::getAlias('@webroot' . $src);
        
        if (!file_exists($path)) {
            return false;
        }
        
        $imageType = exif_imagetype($path);
        
        // Создаём ресурс
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($path);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Если ширина больше максимальной, уменьшаем
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = intval($height * ($maxWidth / $width));
            
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Сохраняем прозрачность для PNG
            if ($imageType == IMAGETYPE_PNG) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
            }
            
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }
        
        // Сохраняем
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($image, $path, $quality);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($image, $path, 9);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($image, $path);
                break;
            default:
                $result = false;
        }
        
        imagedestroy($image);
        
        return $result;
    }
}
