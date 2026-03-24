<?php

namespace app\backend\modules\compare;

use Yii;

/**
 * CompareModule — Модуль сравнения товаров
 * 
 * НАЗНАЧЕНИЕ:
 * Позволяет пользователям добавлять товары в список сравнения
 * и сравнивать их характеристики.
 * 
 * ФУНКЦИИ:
 * - Добавление/удаление товаров в сравнение
 * - Просмотр таблицы сравнения
 * - Очистка списка сравнения
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\backend\modules\compare\controllers';

    public function init()
    {
        parent::init();
        
        // Регистрация компонента сравнения
        if (!Yii::$app->has('compare')) {
            Yii::$app->set('compare', [
                'class' => 'app\backend\modules\compare\components\CompareComponent',
            ]);
        }
    }
}
