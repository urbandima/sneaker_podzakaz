<?php

namespace app\assets;

class PublicAsset extends VersionedAssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];

    public $css = [
        'css/layout/public-layout.css',
    ];

    public $js = [
        'js/public-layout.js',
        'js/notifications.js',
    ];

    public $depends = [
        'app\assets\AppAsset',
    ];
}
