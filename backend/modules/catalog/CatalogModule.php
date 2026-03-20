<?php

/**
 * CatalogModule — Модуль каталога товаров
 * 
 * Feature-based модуль для DDD архитектуры.
 * Содержит всё необходимое для работы каталога:
 * - controllers (CatalogController, ApiController)
 * - models (Product, Category, Brand)
 * - services (FilterService, SearchService)
 * - repositories (ProductRepository)
 * - views (index, product-card, filters)
 * - assets (CatalogAsset)
 */
namespace app\backend\modules\catalog;

use yii\base\Module;
use Yii;

class CatalogModule extends Module
{
    /**
     * @var string Namespace контроллеров по умолчанию
     */
    public $controllerNamespace = 'app\backend\modules\catalog\controllers';
    
    /**
     * @var string Layout по умолчанию
     */
    public $layout = 'main';
    
    /**
     * @var array Настройки модуля
     */
    public $pageSize = 24;
    public $cacheDuration = 3600;
    
    /**
     * Инициализация модуля
     */
    public function init()
    {
        parent::init();

        // Используем глобальные frontend/views
        $this->setViewPath(Yii::getAlias('@frontend/views'));
        
        // Регистрация alias для ресурсов модуля
        Yii::setAlias('@catalog', $this->getBasePath());
    }
}
