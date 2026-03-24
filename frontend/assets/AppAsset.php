<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $sourcePath = '@frontend/web';
    public $baseUrl = '@web';
    
    // Единая точка входа CSS - версии добавятся автоматически в init()
    public $css = [
        'css/app.css',
    ];
    
    public $js = [
        'js/app.js',
        'js/mobile-menu.js',
    ];
    
    public $jsOptions = [
        'defer' => true,
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
    ];
    
    /**
     * АВТОМАТИЧЕСКОЕ ВЕРСИОНИРОВАНИЕ
     * При изменении файла версия обновляется автоматически на основе времени изменения.
     */
    public function init()
    {
        parent::init();
        
        // Автоматическое версионирование для всех CSS файлов
        foreach ($this->css as $index => $cssFile) {
            // Пропускаем внешние ссылки (CDN)
            if (strpos($cssFile, 'http') === 0) {
                continue;
            }
            
            // Убираем старую версию если есть
            $cleanFile = preg_replace('/\?v=.*$/', '', $cssFile);
            $filePath = \Yii::getAlias('@webroot/' . $cleanFile);
            
            // Добавляем версию на основе времени изменения файла
            if (file_exists($filePath)) {
                $version = filemtime($filePath);
                $this->css[$index] = $cleanFile . '?v=' . $version;
            }
        }
        
        // Автоматическое версионирование для всех JS файлов
        foreach ($this->js as $index => $jsFile) {
            // Пропускаем внешние ссылки (CDN)
            if (strpos($jsFile, 'http') === 0) {
                continue;
            }
            
            // Убираем старую версию если есть
            $cleanFile = preg_replace('/\?v=.*$/', '', $jsFile);
            $filePath = \Yii::getAlias('@webroot/' . $cleanFile);
            
            // Добавляем версию на основе времени изменения файла
            if (file_exists($filePath)) {
                $version = filemtime($filePath);
                $this->js[$index] = $cleanFile . '?v=' . $version;
            }
        }
    }
}
