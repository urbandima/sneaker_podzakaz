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
        'css/pages/catalog.css',  // Новые B&W стили каталога
        'css/components/product-card.css',  // Стили карточки товара
    ];
    
    public $js = [
        'js/lazy-load.js',
        'js/cart.js',
        'js/favorites.js',
        'js/catalog.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
