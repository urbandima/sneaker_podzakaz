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
        'css/dist/admin-bundle.min.css?v=2024031602',
    ];
    
    public $js = [
        // JS файл будет добавлен позже
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}
