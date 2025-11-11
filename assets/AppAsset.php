<?php

namespace app\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    // Файлы без версий - версии добавятся автоматически в init()
    public $css = [
        'css/critical.css',         // КРИТИЧНО: Базовые стили, sticky header
        'css/container-system.css', // ВАЖНО: Первым для единой ширины контейнеров
        'css/site.css',
        'css/responsive-fixes.css', // Универсальные адаптивные правила
        'css/header-adaptive.css',  // nav-menu скрыто на mobile
        'css/mobile-menu.css',      // Крестик слева + логотип с текстом
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
