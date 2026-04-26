<?php

namespace app\frontend\assets;

use app\frontend\assets\VersionedAssetBundle;

class PublicAsset extends VersionedAssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/layout/public-layout.css',
    ];

    // notifications.js уже загружается через AppAsset с правильным порядком (после utils.js)
    public $js = [
        'js/public-layout.js',
    ];

    public $jsOptions = ['defer' => true];

    public $depends = [
        'app\frontend\assets\AppAsset',
    ];
}
