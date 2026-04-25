<?php

namespace app\backend\modules\admin\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle для страницы логина админки.
 * Минималистичный набор стилей без зависимостей от Yii2 core бандлов.
 */
class LoginAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl  = '@web';

    public $css = [
        'css/core/design-tokens.css',   // Базовые переменные
        'css/admin-tokens.css',          // Admin токены
        'css/admin-shopify-2026.css',    // Компонентные стили
    ];

    public $js = [
        'js/utils.js',                   // Utility функции
    ];

    public $depends = [
        // Без зависимостей от Yii2 core бандлов
    ];

    public function init()
    {
        parent::init();

        foreach ($this->css as $i => $file) {
            if (strpos($file, 'http') === 0) {
                continue;
            }
            $clean = preg_replace('/\?v=.*$/', '', $file);
            $path  = \Yii::getAlias('@webroot/' . $clean);
            if ($path && file_exists($path)) {
                $this->css[$i] = $clean . '?v=' . filemtime($path);
            }
        }

        foreach ($this->js as $i => $file) {
            if (strpos($file, 'http') === 0) {
                continue;
            }
            $clean = preg_replace('/\?v=.*$/', '', $file);
            $path  = \Yii::getAlias('@webroot/' . $clean);
            if ($path && file_exists($path)) {
                $this->js[$i] = $clean . '?v=' . filemtime($path);
            }
        }
    }
}
