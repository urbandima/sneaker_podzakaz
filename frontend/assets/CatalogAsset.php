<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Catalog Asset Bundle - Единая точка управления стилями каталога
 * 
 * РЕФАКТОРИНГ 2026:
 * - CSS объединены в catalog-bundle.css
 * - Автоматическое версионирование через VersionedAssetBundle
 */
class CatalogAsset extends VersionedAssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/catalog.css',  // Стили каталога
    ];
    
    public $js = [
        'js/lazy-load.js',
        'js/catalog.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
