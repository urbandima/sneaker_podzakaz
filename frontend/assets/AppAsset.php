<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    // Модульная CSS архитектура - версии добавятся автоматически в init()
    // Google Fonts загружается async в layouts/main.php чтобы не блокировать рендер
    public $css = [
        // Bootstrap Icons loaded async in main.php to avoid render-blocking
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
        'css/components/micro-interactions.css',
        'css/components/choice-card.css',
        // Page-specific CSS (landing, catalog, product, cart, checkout, account)
        // is loaded per-controller in frontend/views/layouts/main.php to avoid
        // sending e.g. product.css (650 KB) on every page.
    ];
    
    public $js = [
        'js/utils.js',
        'js/notifications.js',
        'js/app.js',
        'js/global-helpers.js',
        'js/scroll-reveal.js',
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
