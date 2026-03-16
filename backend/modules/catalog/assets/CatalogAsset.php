<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Asset bundle для каталога товаров
 * 
 * РЕФАКТОРИНГ 2025:
 * - CSS объединены в catalog-bundle.min.css
 * - JS объединены в catalog-bundle.min.js
 */
class CatalogAsset extends VersionedAssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/dist/catalog-bundle.min.css',
    ];
    
    public $js = [
        'js/dist/catalog-bundle.min.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
