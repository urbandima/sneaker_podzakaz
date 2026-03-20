<?php

namespace app\backend\assets;

use yii\web\AssetBundle;

class AdminAsset extends AssetBundle
{
    public $sourcePath = null;  // Отключаем публикацию в assets
    public $baseUrl = '@web';
    
    // Файлы без версий - версии добавятся автоматически в init()
    public $css = [
        // MINIMALIST DESIGN SYSTEM 100/100 - прямой доступ к файлам
        '/css/minimalist-design.css',
        '/css/admin-minimalist.css',
        
        // Bootstrap Icons (CDN)
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
    ];
    
    public $js = [
        'js/admin.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
    ];
    
    /**
     * АВТОМАТИЧЕСКОЕ ВЕРСИОНИРОВАНИЕ
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
