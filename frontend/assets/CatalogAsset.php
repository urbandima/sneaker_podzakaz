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
        // Catalog styles - используем минималистичный дизайн из AppAsset
        // Старые стили отключены для единого дизайна 100/100
    ];
    
    public $js = [
        'js/lazy-load.js',
        'js/catalog.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
