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
    public $sourcePath = '@frontend';  // Источник файлов
    public $baseUrl = '@web';
    
    public $css = [
        // Catalog styles (объединены из catalog-layout, catalog-card, catalog-inline)
        'css/pages/catalog.css',
        // Grid styles (из catalog-mobile-fixes)
        'css/pages/catalog-grid.css',
    ];
    
    public $js = [
        'js/catalog.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
