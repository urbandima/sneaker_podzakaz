<?php

namespace app\infrastructure\services;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

/**
 * ImageOptimizationService - оптимизация изображений для продакшена.
 *
 * Реализовано на встроенном GD (без внешних зависимостей): та же библиотека,
 * которую уже использует WebpController для конвертации в WebP.
 */
class ImageOptimizationService extends Component
{
    public $webpQuality = 80;
    public $jpegQuality = 85;
    public $maxWidth = 1200;
    public $maxHeight = 1200;

    /**
     * Оптимизация изображения
     */
    public function optimizeImage($sourcePath, $destPath = null)
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $destPath = $destPath ?: $sourcePath;
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return $this->optimizeJpeg($sourcePath, $destPath);
            case 'png':
                return $this->optimizePng($sourcePath, $destPath);
            case 'webp':
                return $this->optimizeWebp($sourcePath, $destPath);
            default:
                return false;
        }
    }

    /**
     * Создание WebP версии
     */
    public function createWebpVersion($sourcePath, $webpPath = null)
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $webpPath = $webpPath ?? preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $sourcePath);

        try {
            $image = $this->loadImage($sourcePath);
            if (!$image) {
                throw new \RuntimeException("Unsupported image type: {$sourcePath}");
            }

            $result = imagewebp($image, $webpPath, $this->webpQuality);
            imagedestroy($image);

            if (!$result) {
                throw new \RuntimeException('imagewebp() failed');
            }

            return $webpPath;
        } catch (\Throwable $e) {
            Yii::error("WebP creation failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Оптимизация JPEG
     */
    private function optimizeJpeg($sourcePath, $destPath)
    {
        try {
            $image = imagecreatefromjpeg($sourcePath);
            if (!$image) {
                throw new \RuntimeException('imagecreatefromjpeg() failed');
            }

            $image = $this->resizeIfNeeded($image);

            $result = imagejpeg($image, $destPath, $this->jpegQuality);
            imagedestroy($image);

            if (!$result) {
                throw new \RuntimeException('imagejpeg() failed');
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error("JPEG optimization failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Оптимизация PNG
     */
    private function optimizePng($sourcePath, $destPath)
    {
        try {
            $image = imagecreatefrompng($sourcePath);
            if (!$image) {
                throw new \RuntimeException('imagecreatefrompng() failed');
            }

            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $image = $this->resizeIfNeeded($image);

            // GD-компрессия PNG: 0 (без сжатия) .. 9 (максимум)
            $result = imagepng($image, $destPath, 6);
            imagedestroy($image);

            if (!$result) {
                throw new \RuntimeException('imagepng() failed');
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error("PNG optimization failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Оптимизация WebP
     */
    private function optimizeWebp($sourcePath, $destPath)
    {
        try {
            $image = imagecreatefromwebp($sourcePath);
            if (!$image) {
                throw new \RuntimeException('imagecreatefromwebp() failed');
            }

            $image = $this->resizeIfNeeded($image);

            $result = imagewebp($image, $destPath, $this->webpQuality);
            imagedestroy($image);

            if (!$result) {
                throw new \RuntimeException('imagewebp() failed');
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error("WebP optimization failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Загрузить изображение по расширению файла (для createWebpVersion)
     *
     * @return \GdImage|false
     */
    private function loadImage($sourcePath)
    {
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($sourcePath);
            case 'png':
                $image = imagecreatefrompng($sourcePath);
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
                return $image;
            case 'webp':
                return imagecreatefromwebp($sourcePath);
            default:
                return false;
        }
    }

    /**
     * Ресайз изображения, если оно превышает maxWidth/maxHeight (с сохранением пропорций)
     *
     * @param \GdImage $image
     * @return \GdImage
     */
    private function resizeIfNeeded($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $this->maxWidth && $height <= $this->maxHeight) {
            return $image;
        }

        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Массовая оптимизация папки
     */
    public function optimizeDirectory($directory, $recursive = true)
    {
        $files = FileHelper::findFiles($directory, [
            'only' => ['*.jpg', '*.jpeg', '*.png', '*.webp'],
            'recursive' => $recursive
        ]);

        $optimized = 0;
        foreach ($files as $file) {
            if ($this->optimizeImage($file)) {
                $optimized++;
                // Создаем WebP версию
                $this->createWebpVersion($file);
            }
        }

        return $optimized;
    }

    /**
     * Получение размера файла
     */
    public function getFileSize($path)
    {
        return file_exists($path) ? filesize($path) : 0;
    }

    /**
     * Статистика оптимизации
     */
    public function getOptimizationStats($beforePath, $afterPath)
    {
        $beforeSize = $this->getFileSize($beforePath);
        $afterSize = $this->getFileSize($afterPath);

        return [
            'before_size' => $beforeSize,
            'after_size' => $afterSize,
            'saved_bytes' => $beforeSize - $afterSize,
            'saved_percent' => $beforeSize > 0 ? round((($beforeSize - $afterSize) / $beforeSize) * 100, 2) : 0
        ];
    }
}
