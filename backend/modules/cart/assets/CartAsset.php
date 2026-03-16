<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Asset bundle для корзины
 * 
 * РЕФАКТОРИНГ 2025:
 * - JS: cart-bundle.min.js
 */
class CartAsset extends VersionedAssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $js = [
        'js/dist/cart-bundle.min.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
