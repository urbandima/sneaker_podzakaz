<?php
namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * WebP конвертер изображений
 * 
 * Использование:
 * php yii webp/convert - конвертировать все изображения
 * php yii webp/convert-dir web/uploads - конвертировать конкретную директорию
 */
class WebpController extends Controller
{
    /**
     * Качество WebP изображений (0-100)
     */
    public $quality = 85;
    
    /**
     * Удалять ли оригинальные изображения после конвертации
     */
    public $deleteOriginal = false;
    
    /**
     * Список директорий для конвертации
     */
    private $directories = [
        'web/uploads',
        'web/images',
    ];
    
    /**
     * Поддерживаемые форматы
     */
    private $supportedFormats = ['jpg', 'jpeg', 'png', 'gif'];
    
    /**
     * Статистика конвертации
     */
    private $stats = [
        'total' => 0,
        'converted' => 0,
        'skipped' => 0,
        'errors' => 0,
        'saved_bytes' => 0,
    ];
    
    /**
     * Конфигурация опций
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['quality', 'deleteOriginal']
        );
    }
    
    /**
     * Конвертировать все изображения
     */
    public function actionConvert()
    {
        $this->stdout("🔄 Начинаю конвертацию изображений в WebP...\n\n", Console::FG_CYAN);
        
        // Проверяем поддержку WebP
        if (!$this->checkWebPSupport()) {
            $this->stderr("❌ Библиотека GD не поддерживает WebP!\n", Console::FG_RED);
            $this->stderr("Установите PHP с поддержкой WebP: apt-get install php-gd\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        $startTime = microtime(true);
        
        // Конвертируем каждую директорию
        foreach ($this->directories as $dir) {
            $fullPath = Yii::getAlias('@app/' . $dir);
            
            if (!is_dir($fullPath)) {
                $this->stdout("⚠️  Директория не найдена: $dir\n", Console::FG_YELLOW);
                continue;
            }
            
            $this->stdout("📁 Обрабатываю: $dir\n", Console::FG_GREEN);
            $this->convertDirectory($fullPath);
        }
        
        $duration = round(microtime(true) - $startTime, 2);
        
        // Выводим статистику
        $this->printStatistics($duration);
        
        return ExitCode::OK;
    }
    
    /**
     * Конвертировать конкретную директорию
     * 
     * @param string $dir Путь к директории
     */
    public function actionConvertDir($dir)
    {
        $this->stdout("🔄 Конвертирую директорию: $dir\n\n", Console::FG_CYAN);
        
        $fullPath = Yii::getAlias('@app/' . $dir);
        
        if (!is_dir($fullPath)) {
            $this->stderr("❌ Директория не найдена: $dir\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        if (!$this->checkWebPSupport()) {
            $this->stderr("❌ Библиотека GD не поддерживает WebP!\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        $startTime = microtime(true);
        
        $this->convertDirectory($fullPath);
        
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->printStatistics($duration);
        
        return ExitCode::OK;
    }
    
    /**
     * Проверить поддержку WebP
     */
    private function checkWebPSupport()
    {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $gdInfo = gd_info();
        return isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'];
    }
    
    /**
     * Конвертировать директорию рекурсивно
     */
    private function convertDirectory($dir)
    {
        $files = FileHelper::findFiles($dir, [
            'only' => array_map(function($ext) {
                return '*.' . $ext;
            }, $this->supportedFormats),
            'recursive' => true,
        ]);
        
        foreach ($files as $file) {
            $this->stats['total']++;
            $this->convertImage($file);
        }
    }
    
    /**
     * Конвертировать одно изображение
     */
    private function convertImage($filePath)
    {
        $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $filePath);
        
        // Пропускаем если WebP уже существует и новее оригинала
        if (file_exists($webpPath) && filemtime($webpPath) >= filemtime($filePath)) {
            $this->stats['skipped']++;
            return;
        }
        
        // Определяем тип изображения
        $imageType = exif_imagetype($filePath);
        
        try {
            // Создаём ресурс изображения
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($filePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($filePath);
                    // Сохраняем прозрачность
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;
                case IMAGETYPE_GIF:
                    $image = imagecreatefromgif($filePath);
                    break;
                default:
                    $this->stats['skipped']++;
                    return;
            }
            
            if (!$image) {
                throw new \Exception("Не удалось создать изображение");
            }
            
            // Конвертируем в WebP
            $result = imagewebp($image, $webpPath, $this->quality);
            imagedestroy($image);
            
            if (!$result) {
                throw new \Exception("Не удалось сохранить WebP");
            }
            
            // Статистика
            $originalSize = filesize($filePath);
            $webpSize = filesize($webpPath);
            $savedBytes = $originalSize - $webpSize;
            $this->stats['saved_bytes'] += $savedBytes;
            $this->stats['converted']++;
            
            $percent = round(($savedBytes / $originalSize) * 100, 1);
            $relativePath = str_replace(Yii::getAlias('@app/'), '', $filePath);
            
            $this->stdout("✅ $relativePath ", Console::FG_GREEN);
            $this->stdout("(" . $this->formatBytes($originalSize) . " → " . $this->formatBytes($webpSize) . ", ");
            
            if ($savedBytes > 0) {
                $this->stdout("-{$percent}%", Console::FG_GREEN);
            } else {
                $this->stdout("+{$percent}%", Console::FG_YELLOW);
            }
            
            $this->stdout(")\n");
            
            // Удаляем оригинал если нужно
            if ($this->deleteOriginal) {
                unlink($filePath);
                $this->stdout("  🗑️  Удалён оригинал\n", Console::FG_YELLOW);
            }
            
        } catch (\Exception $e) {
            $this->stats['errors']++;
            $relativePath = str_replace(Yii::getAlias('@app/'), '', $filePath);
            $this->stderr("❌ Ошибка: $relativePath - " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }
    
    /**
     * Вывести статистику
     */
    private function printStatistics($duration)
    {
        $this->stdout("\n" . str_repeat("=", 60) . "\n", Console::FG_CYAN);
        $this->stdout("📊 СТАТИСТИКА КОНВЕРТАЦИИ\n", Console::FG_CYAN);
        $this->stdout(str_repeat("=", 60) . "\n\n", Console::FG_CYAN);
        
        $this->stdout("Всего файлов:      ", Console::BOLD);
        $this->stdout($this->stats['total'] . "\n");
        
        $this->stdout("Конвертировано:    ", Console::BOLD);
        $this->stdout($this->stats['converted'] . "\n", Console::FG_GREEN);
        
        $this->stdout("Пропущено:         ", Console::BOLD);
        $this->stdout($this->stats['skipped'] . "\n", Console::FG_YELLOW);
        
        $this->stdout("Ошибок:            ", Console::BOLD);
        $this->stdout($this->stats['errors'] . "\n", $this->stats['errors'] > 0 ? Console::FG_RED : Console::FG_GREEN);
        
        $this->stdout("Сэкономлено места: ", Console::BOLD);
        $savedMB = $this->formatBytes($this->stats['saved_bytes']);
        $this->stdout($savedMB . "\n", Console::FG_GREEN);
        
        $this->stdout("Время выполнения:  ", Console::BOLD);
        $this->stdout($duration . " сек\n");
        
        $this->stdout("\n✅ Конвертация завершена!\n", Console::FG_GREEN);
    }
    
    /**
     * Форматировать байты в читаемый вид
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
