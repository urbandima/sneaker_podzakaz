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
    public $sourcePath = '@frontend/web';  // Источник файлов
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages.css',  // Cart стили (cart-page, cart-item)
    ];
    
    public $js = [
        'js/cart-promo-loyalty.js',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
