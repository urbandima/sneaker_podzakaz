<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $sourcePath = '@frontend';  // Источник файлов
    public $baseUrl = '@web';
    
    // Файлы без версий - версии добавятся автоматически в init()
    public $css = [
        // CORE (критичные стили)
        'css/core/critical.css',
        'css/core/critical-inline.css',
        
        // CORE (системные стили)
        'css/core/container-system.css',
        'css/core/design-tokens.css',
        'css/core/design-system.css',
        
        // COMPONENTS
        'css/components/header-adaptive.css',
        'css/components/mobile-menu.css',
        'css/components/mega-menu.css',
        
        // LAYOUT
        'css/layout/public-layout.css',
        'css/layout/responsive-fixes.css',
        'css/layout/skeleton-loading.css',
        
        // FEATURES
        'css/features/accessibility.css',
        'css/features/dark-mode.css',
        'css/features/micro-interactions.css',
        
        // SITE
        'css/site.css',
        
        // ВНЕШНИЕ РЕСУРСЫ
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
    ];
    
    public $js = [
        'js/mobile-menu.js',        // Мобильное меню для ecom-header (burger, overlay)
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
    ];
    
    /**
     * АВТОМАТИЧЕСКОЕ ВЕРСИОНИРОВАНИЕ
     * При изменении файла версия обновляется автоматически на основе времени изменения.
     * Больше не нужно вручную менять версии!
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
