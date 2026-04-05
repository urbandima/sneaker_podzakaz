<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class AboutAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages/about.css',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
