<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Checkout Asset Bundle - Стили оформления заказа
 * 
 * РЕФАКТОРИНГ 2026:
 * - CSS объединены в checkout-bundle.css
 * - Автоматическое версионирование
 */
class CheckoutAsset extends VersionedAssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages/checkout.css',  // Checkout стили (checkout-page, shipping-methods)
    ];
    
    public $js = [
        'js/checkout.js',
    ];

    public $jsOptions = ['defer' => true];

    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
