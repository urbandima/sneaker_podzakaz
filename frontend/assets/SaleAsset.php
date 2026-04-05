<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class SaleAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages/sale-inline.css',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
