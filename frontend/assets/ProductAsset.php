<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Asset bundle для страницы товара
 * 
 * РЕФАКТОРИНГ 2025:
 * - CSS объединены в product-bundle.min.css
 * - JS объединены в product-bundle.min.js
 */
class ProductAsset extends VersionedAssetBundle
{
    public $sourcePath = '@frontend';  // Источник файлов
    public $baseUrl = '@web';
    
    public $css = [
        // Product styles (объединены из product-page, product-reviews)
        'css/pages/product.css',
    ];
    
    public $js = [
        'js/product-page.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
        'yii\web\JqueryAsset',
    ];
}
