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
    public $sourcePath = '@frontend/web';  // Источник файлов
    public $baseUrl = '@web';
    
    public $css = [
        // Checkout styles - использую минималистичный дизайн из AppAsset
        // Старые стили отключены для единого дизайна 100/100
    ];
    
    public $js = [
        'js/checkout.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
