<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle для каталога товаров
 * Включает минифицированные версии JS и CSS
 */
class CatalogAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    
    public $js = [
        'js/catalog.js',
    ];
    
    public $depends = [
        'yii\web\YiiAsset',
    ];
    
    /**
     * Включение минификации в production
     */
    public function init()
    {
        parent::init();
        
        // В production режиме пытаемся использовать минифицированные версии
        if (!YII_DEBUG) {
            $this->js = ['js/catalog.min.js'];
        }
    }
}
