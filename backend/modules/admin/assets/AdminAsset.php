<?php

namespace app\backend\modules\admin\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle для админ-панели.
 *
 * Порядок CSS важен: design-tokens → admin-tokens → компоненты → страницы.
 */
class AdminAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl  = '@web';

    public $css = [
        'css/core/design-tokens.css',    // Frontend-токены (--color-*, --space-*, --text-*)
        'css/admin-tokens.css',          // Admin-маппинг на core-токены (--admin-*)
        'css/admin-shopify-2026.css',    // Компонентные стили панели
        'css/admin-pages.css',           // Page-specific стили (вынесенные inline)
        'css/admin-wizard.css',          // Wizard-страницы (create/view-wizard)
    ];

    public $js = [
        'js/admin.js',
        'js/admin-search.js',
        'js/dashboard.js',
        'js/orders.js',
        'js/admin-orders.js',
        'js/admin-products.js',
        'js/admin-customers.js',
        'js/admin-settings.js',
        'js/admin-wizard.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
    ];
}
