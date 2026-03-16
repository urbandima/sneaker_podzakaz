<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

/**
 * Asset bundle для админ-панели
 * 
 * РЕФАКТОРИНГ 2025:
 * - CSS объединены в admin-bundle.min.css
 * - JS объединены в admin-bundle.min.js
 */
class AdminAsset extends VersionedAssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/dist/admin-bundle.min.css',
    ];
    
    public $js = [
        'js/dist/admin-bundle.min.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}
