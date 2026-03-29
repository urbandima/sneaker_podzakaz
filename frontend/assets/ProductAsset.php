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
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages.css',  // Стили для страниц (product-details, brand-block-premium)
        'css/catalog.css', // Стили карточек товаров
    ];
    
    public $js = [
        'js/product-page.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
        'yii\web\JqueryAsset',
    ];
}
