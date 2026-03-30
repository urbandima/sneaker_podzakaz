<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class AccountAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages/account.css',
        'css/pages/account-inline.css',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
