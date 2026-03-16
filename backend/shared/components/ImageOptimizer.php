<?php

/**
 * ImageOptimizer — Оптимизация изображений
 * 
 * НАЗНАЧЕНИЕ:
 * Автоматическая оптимизация изображений: сжатие, WebP, lazy loading.
 * 
 * ФУНКЦИИ:
 * - Конвертация в WebP
 * - Ресайз с сохранением пропорций
 * - Сжатие без потери качества
 * - Генерация srcset
 */
namespace app\backend\shared\components;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class ImageOptimizer extends Component
{
    /**
     * Качество WebP (0-100)
     */
    public int $webpQuality = 85;
    
    /**
     * Качество JPEG (0-100)
     */
    public int $jpegQuality = 85;
    
    /**
     * Максимальная ширина
     */
    public int $maxWidth = 1920;
    
    /**
     * Максимальная высота
     */
    public int $maxHeight = 1080;
    
    /**
     * Размеры для srcset
     */
    public array $srcsetSizes = [320, 480, 768, 1024, 1280, 1920];
    
    /**
     * Оптимизировать изображение
     * 
     * @param string $sourcePath Путь к исходному файлу
     * @param string $targetPath Путь для сохранения (null = перезапись)
     * @return array Результат оптимизации
     */
    public function optimize(string $sourcePath, ?string $targetPath = null): array
    {
        if (!file_exists($sourcePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }
        
        $targetPath = $targetPath ?? $sourcePath;
        $originalSize = filesize($sourcePath);
        
        // Получаем информацию об изображении
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Not an image'];
        }
        
        [$width, $height, $type] = $imageInfo;
        
        // Создаём изображение
        $image = $this->createImage($sourcePath, $type);
        if (!$image) {
            return ['success' => false, 'error' => 'Failed to create image'];
        }
        
        // Ресайз если нужно
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $image = $this->resize($image, $width, $height);
        }
        
        // Сохраняем оптимизированное изображение
        $result = $this->saveImage($image, $targetPath, $type);
        
        imagedestroy($image);
        
        $newSize = filesize($targetPath);
        $savedBytes = $originalSize - $newSize;
        $savedPercent = round(($savedBytes / $originalSize) * 100, 1);
        
        return [
            'success' => true,
            'original_size' => $this->formatBytes($originalSize),
            'new_size' => $this->formatBytes($newSize),
            'saved_bytes' => $this->formatBytes($savedBytes),
            'saved_percent' => $savedPercent,
            'dimensions' => imagesx($image) . 'x' . imagesy($image),
        ];
    }
    
    /**
     * Конвертировать в WebP
     * 
     * @param string $sourcePath
     * @param string|null $targetPath
     * @return array
     */
    public function convertToWebp(string $sourcePath, ?string $targetPath = null): array
    {
        if (!function_exists('imagewebp')) {
            return ['success' => false, 'error' => 'WebP not supported'];
        }
        
        if (!file_exists($sourcePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }
        
        if ($targetPath === null) {
            $targetPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $sourcePath);
        }
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Not an image'];
        }
        
        $image = $this->createImage($sourcePath, $imageInfo[2]);
        if (!$image) {
            return ['success' => false, 'error' => 'Failed to create image'];
        }
        
        imagewebp($image, $targetPath, $this->webpQuality);
        imagedestroy($image);
        
        $originalSize = filesize($sourcePath);
        $webpSize = filesize($targetPath);
        
        return [
            'success' => true,
            'original_size' => $this->formatBytes($originalSize),
            'webp_size' => $this->formatBytes($webpSize),
            'saved_percent' => round((($originalSize - $webpSize) / $originalSize) * 100, 1),
            'webp_path' => $targetPath,
        ];
    }
    
    /**
     * Сгенерировать srcset атрибут
     * 
     * @param string $imagePath Относительный путь к изображению
     * @return string HTML srcset атрибут
     */
    public function generateSrcset(string $imagePath): string
    {
        $fullPath = Yii::getAlias('@webroot') . $imagePath;
        
        if (!file_exists($fullPath)) {
            return '';
        }
        
        $imageInfo = getimagesize($fullPath);
        if (!$imageInfo) {
            return '';
        }
        
        $originalWidth = $imageInfo[0];
        $pathInfo = pathinfo($imagePath);
        $srcset = [];
        
        foreach ($this->srcsetSizes as $width) {
            if ($width > $originalWidth) {
                continue;
            }
            
            $resizedPath = "{$pathInfo['dirname']}/{$pathInfo['filename']}-{$width}w.{$pathInfo['extension']}";
            $fullResizedPath = Yii::getAlias('@webroot') . $resizedPath;
            
            // Если файл не существует - создаём
            if (!file_exists($fullResizedPath)) {
                $this->createResizedVersion($fullPath, $fullResizedPath, $width);
            }
            
            if (file_exists($fullResizedPath)) {
                $srcset[] = "{$resizedPath} {$width}w";
            }
        }
        
        // Добавляем оригинал
        $srcset[] = "{$imagePath} {$originalWidth}w";
        
        return implode(', ', $srcset);
    }
    
    /**
     * Создать resized версию изображения
     */
    private function createResizedVersion(string $sourcePath, string $targetPath, int $targetWidth): bool
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        [$width, $height, $type] = $imageInfo;
        
        $image = $this->createImage($sourcePath, $type);
        if (!$image) {
            return false;
        }
        
        // Вычисляем новую высоту
        $ratio = $height / $width;
        $targetHeight = (int)($targetWidth * $ratio);
        
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Сохраняем прозрачность для PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        
        $this->saveImage($resized, $targetPath, $type);
        
        imagedestroy($image);
        imagedestroy($resized);
        
        return true;
    }
    
    /**
     * Создать изображение из файла
     */
    private function createImage(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }
    
    /**
     * Сохранить изображение
     */
    private function saveImage($image, string $path, int $type): bool
    {
        FileHelper::createDirectory(dirname($path));
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $path, $this->jpegQuality);
            case IMAGETYPE_PNG:
                return imagepng($image, $path, 9);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $path, $this->webpQuality);
            default:
                return false;
        }
    }
    
    /**
     * Ресайз изображения
     */
    private function resize($image, int $width, int $height)
    {
        // Вычисляем новые размеры
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        imagedestroy($image);
        
        return $resized;
    }
    
    /**
     * Форматирование байтов
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
    
    /**
     * Оптимизировать все изображения в директории
     */
    public function optimizeDirectory(string $directory, bool $recursive = true): array
    {
        $results = [];
        $pattern = $recursive ? $directory . '/*.{jpg,jpeg,png}' : $directory . '*.{jpg,jpeg,png}';
        
        $files = glob($directory . '/*.{jpg,jpeg,png}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $results[$file] = $this->optimize($file);
        }
        
        return $results;
    }
}
