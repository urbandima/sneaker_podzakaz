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
        'css/core/design-tokens.css',   // Базовые переменные (цвета, отступы, тени)
        'css/admin-tokens.css',          // Admin-маппинг на core-токены (--admin-*)
        'css/admin-shopify-2026.css',    // Компонентные стили панели
        'css/admin-pages.css',           // Page-specific стили (вынесенные inline)
        'css/admin-wizard.css',          // Wizard-страницы (create/view-wizard)
        'css/pages/admin-import.css',    // Импорт товаров
        'css/pages/admin-order.css',     // Заказы
        'css/pages/admin-return.css',    // Возвраты
    ];

    public $js = [
        'js/utils.js',               // Utility функции (modal, notifications, formatting)
        'js/notifications.js',      // Уведомления
        'js/admin.js',               // Основной JS админки
        'js/admin-search.js',       // Глобальный поиск
        'js/dashboard.js',           // Dashboard
        'js/orders.js',             // Заказы
        'js/admin-orders.js',       // Админка заказов
        'js/admin-products.js',     // Админка товаров
        'js/admin-customers.js',    // Админка клиентов
        'js/admin-settings.js',     // Настройки
        'js/admin-poizon.js',       // Интеграция Poizon
        'js/admin-wizard.js',       // Wizard страницы (create/view)
    ];

    public $depends = [
        // Bootstrap Icons подключается через CDN в layout
        // Yii2 core JS не нужен для админки
    ];
}
