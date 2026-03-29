<?php

namespace app\infrastructure\services;

use Yii;
use yii\base\Component;
use yii\imagine\Image;
use yii\helpers\FileHelper;

/**
 * ImageOptimizationService - оптимизация изображений для продакшена
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
            $image = Image::getImagine()->open($sourcePath);
            $image->save($webpPath, [
                'quality' => $this->webpQuality,
                'format' => 'webp'
            ]);
            return $webpPath;
        } catch (\Exception $e) {
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
            $image = Image::getImagine()->open($sourcePath);
            
            // Ресайз если превышает максимальные размеры
            $size = $image->getSize();
            if ($size->getWidth() > $this->maxWidth || $size->getHeight() > $this->maxHeight) {
                $image = Image::resize($image, $this->maxWidth, $this->maxHeight);
            }
            
            $image->save($destPath, [
                'quality' => $this->jpegQuality,
                'format' => 'jpeg'
            ]);
            
            return true;
        } catch (\Exception $e) {
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
            $image = Image::getImagine()->open($sourcePath);
            
            // Ресайз если превышает максимальные размеры
            $size = $image->getSize();
            if ($size->getWidth() > $this->maxWidth || $size->getHeight() > $this->maxHeight) {
                $image = Image::resize($image, $this->maxWidth, $this->maxHeight);
            }
            
            $image->save($destPath, [
                'format' => 'png',
                'png_compression_level' => 6
            ]);
            
            return true;
        } catch (\Exception $e) {
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
            $image = Image::getImagine()->open($sourcePath);
            
            // Ресайз если превышает максимальные размеры
            $size = $image->getSize();
            if ($size->getWidth() > $this->maxWidth || $size->getHeight() > $this->maxHeight) {
                $image = Image::resize($image, $this->maxWidth, $this->maxHeight);
            }
            
            $image->save($destPath, [
                'quality' => $this->webpQuality,
                'format' => 'webp'
            ]);
            
            return true;
        } catch (\Exception $e) {
            Yii::error("WebP optimization failed: {$e->getMessage()}");
            return false;
        }
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
