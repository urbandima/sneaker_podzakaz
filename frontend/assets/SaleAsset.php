<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class SaleAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        // sale-inline.css was removed — styles are in the main CSS bundle
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
