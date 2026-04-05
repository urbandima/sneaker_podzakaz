<?php

namespace app\frontend\assets;

use yii\web\AssetBundle;

class ContactsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $css = [
        'css/pages/contacts.css',
    ];
    
    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
