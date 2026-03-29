<?php

namespace app\backend\modules\admin\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle для админ-панели
 * 
 * РЕФАКТОРИНГ 2025:
 * - CSS объединены в admin-bundle.min.css
 * - JS объединены в admin-bundle.min.js
 */
class AdminAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/admin.css',
    ];
    
    public $js = [
        'js/admin.js',
        'js/dashboard.js',
    ];
    
    public $depends = [
        'app\backend\assets\AdminAsset', // Основной AdminAsset с минималистичными стилями
        'yii\web\YiiAsset',
        // Bootstrap5 отключен для минималистичного дизайна
        // 'yii\bootstrap5\BootstrapAsset',
        // 'yii\bootstrap5\BootstrapPluginAsset',
    ];
}
