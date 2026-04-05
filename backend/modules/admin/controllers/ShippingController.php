<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ArrayDataProvider;

/**
 * Контроллер управления доставкой
 */
class ShippingController extends BaseAdminController
{
    /**
     * Главная страница управления доставкой
     */
    public function actionIndex()
    {
        $shippingMethods = $this->getShippingMethods();
        
        $dataProvider = new ArrayDataProvider([
            'allModels' => $shippingMethods,
            'pagination' => false,
        ]);
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'methods' => $shippingMethods,
        ]);
    }
    
    /**
     * Международная доставка
     */
    public function actionInternational()
    {
        $methods = $this->getInternationalMethods();
        
        return $this->render('international', [
            'methods' => $methods,
        ]);
    }
    
    /**
     * Доставка внутри страны
     */
    public function actionLocal()
    {
        $methods = $this->getLocalMethods();
        
        return $this->render('local', [
            'methods' => $methods,
        ]);
    }
    
    /**
     * Настройки доставки
     */
    public function actionSettings()
    {
        return $this->render('settings');
    }
    
    /**
     * Получить все методы доставки
     */
    private function getShippingMethods()
    {
        return [
            [
                'id' => 'international_express',
                'name' => 'Международная экспресс',
                'type' => 'international',
                'type_label' => 'Международная',
                'carrier' => 'DHL',
                'status' => 'active',
                'delivery_time' => '3-5 дней',
                'base_cost' => 45.00,
                'currency' => 'BYN',
                'description' => 'Быстрая международная доставка',
            ],
            [
                'id' => 'international_standard',
                'name' => 'Международная стандарт',
                'type' => 'international',
                'type_label' => 'Международная',
                'carrier' => 'EMS',
                'status' => 'active',
                'delivery_time' => '7-14 дней',
                'base_cost' => 25.00,
                'currency' => 'BYN',
                'description' => 'Экономичная международная доставка',
            ],
            [
                'id' => 'local_courier',
                'name' => 'Курьер по городу',
                'type' => 'local',
                'type_label' => 'Внутри страны',
                'carrier' => 'Внутренний',
                'status' => 'active',
                'delivery_time' => '1-2 дня',
                'base_cost' => 8.00,
                'currency' => 'BYN',
                'description' => 'Доставка курьером по городу',
            ],
            [
                'id' => 'local_pickup',
                'name' => 'Самовывоз',
                'type' => 'local',
                'type_label' => 'Внутри страны',
                'carrier' => 'Пункт выдачи',
                'status' => 'active',
                'delivery_time' => 'Сегодня',
                'base_cost' => 0.00,
                'currency' => 'BYN',
                'description' => 'Бесплатный самовывоз из пункта',
            ],
            [
                'id' => 'local_post',
                'name' => 'Почта',
                'type' => 'local',
                'type_label' => 'Внутри страны',
                'carrier' => 'Белпочта',
                'status' => 'active',
                'delivery_time' => '3-7 дней',
                'base_cost' => 5.00,
                'currency' => 'BYN',
                'description' => 'Доставка почтой по стране',
            ],
        ];
    }
    
    /**
     * Получить международные методы
     */
    private function getInternationalMethods()
    {
        return array_filter($this->getShippingMethods(), function($method) {
            return $method['type'] === 'international';
        });
    }
    
    /**
     * Получить локальные методы
     */
    private function getLocalMethods()
    {
        return array_filter($this->getShippingMethods(), function($method) {
            return $method['type'] === 'local';
        });
    }
}
