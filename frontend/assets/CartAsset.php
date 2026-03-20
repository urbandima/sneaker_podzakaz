<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Cart Asset Bundle - Стили корзины
 * 
 * РЕФАКТОРИНГ 2026:
 * - CSS объединены в cart-bundle.css
 * - Автоматическое версионирование
 */
class CartAsset extends VersionedAssetBundle
{
    public $sourcePath = '@frontend';  // Источник файлов
    public $baseUrl = '@web';
    
    public $css = [
        // Cart styles - использую минималистичный дизайн из AppAsset
        // Старые стили отключены для единого дизайна 100/100
    ];
    
    public $js = [
        'js/cart-promo-loyalty.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
