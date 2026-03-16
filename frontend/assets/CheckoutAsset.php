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
    public $sourcePath = '@frontend';  // Источник файлов
    public $baseUrl = '@web';
    
    public $css = [
        // Checkout styles (объединены из checkout-enhancements, order-success)
        'css/pages/checkout.css',
    ];
    
    public $js = [
        'js/checkout.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
