<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    // Модульная CSS архитектура - версии добавятся автоматически в init()
    public $css = [
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        'css/core/design-tokens.css',
        'css/core/design-system.css',
        'css/core/container-system.css',
        'css/layout/public-layout.css',
        'css/layout/responsive-fixes.css',
        'css/components/header.css',
        'css/components/sidebar-menu.css',
        'css/components/footer.css',
        'css/components/cart-drawer.css',
        'css/components/product-card.css',
        'css/components/modals.css',
        'css/pages/landing.css',
        'css/pages/catalog.css',
        'css/pages/product.css',
        'css/pages/cart.css',
        'css/pages/checkout.css',
        'css/pages/account.css',
    ];
    
    public $js = [
        'js/app.js',
        'js/global-helpers.js',
        'js/favorites.js',
        'js/cart.js',
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
