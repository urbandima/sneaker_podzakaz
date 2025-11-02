<?php

namespace app\commands;

use Yii;
use yii\console\Controller;

/**
 * Контроллер для минификации JS файлов
 * 
 * Использование:
 * php yii asset/minify
 */
class AssetController extends Controller
{
    /**
     * Минификация JavaScript файлов каталога
     */
    public function actionMinify()
    {
        $this->stdout("Минификация JS файлов...\n");
        
        $jsFile = Yii::getAlias('@webroot/js/catalog.js');
        $minFile = Yii::getAlias('@webroot/js/catalog.min.js');
        
        if (!file_exists($jsFile)) {
            $this->stderr("Файл $jsFile не найден!\n");
            return 1;
        }
        
        // Читаем содержимое
        $content = file_get_contents($jsFile);
        
        // Простая минификация (удаление комментариев и пробелов)
        $minified = $this->minifyJs($content);
        
        // Сохраняем минифицированную версию
        file_put_contents($minFile, $minified);
        
        $originalSize = filesize($jsFile);
        $minifiedSize = filesize($minFile);
        $saved = $originalSize - $minifiedSize;
        $percent = round(($saved / $originalSize) * 100, 2);
        
        $this->stdout("✓ Минификация завершена\n");
        $this->stdout("  Оригинал: " . $this->formatBytes($originalSize) . "\n");
        $this->stdout("  Минифицированный: " . $this->formatBytes($minifiedSize) . "\n");
        $this->stdout("  Сэкономлено: " . $this->formatBytes($saved) . " ($percent%)\n");
        
        return 0;
    }
    
    /**
     * Простая минификация JavaScript
     */
    protected function minifyJs($code)
    {
        // Удаляем однострочные комментарии
        $code = preg_replace('/\/\/.*$/m', '', $code);
        
        // Удаляем многострочные комментарии
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);
        
        // Удаляем лишние пробелы и переносы строк
        $code = preg_replace('/\s+/', ' ', $code);
        
        // Удаляем пробелы вокруг операторов
        $code = preg_replace('/\s*([{}();,:])\s*/', '$1', $code);
        
        return trim($code);
    }
    
    /**
     * Форматирование размера файла
     */
    protected function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
    
    /**
     * Очистка кэша
     */
    public function actionClearCache()
    {
        $this->stdout("Очистка кэша...\n");
        
        $cache = Yii::$app->cache;
        if ($cache->flush()) {
            $this->stdout("✓ Кэш успешно очищен\n");
            return 0;
        }
        
        $this->stderr("✗ Ошибка очистки кэша\n");
        return 1;
    }
}
