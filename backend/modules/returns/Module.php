<?php

/**
 * ReturnModule — Модуль возвратов
 * 
 * НАЗНАЧЕНИЕ:
 * Управление возвратами товаров: заявки на возврат, политика возврата,
 * обработка возвратов, возврат средств.
 * 
 * ФУНКЦИИ:
 * - Создание заявок на возврат
 * - Управление политикой возврата
 * - Обработка возвратов
 * - Возврат средств
 * - Аналитика возвратов
 * 
 * КОМПОНЕНТЫ:
 * - ReturnController - контроллер управления возвратами
 * - ReturnPolicy - модель политики возврата
 * - ReturnRequest - модель заявки на возврат
 * - ReturnService - сервис обработки возвратов
 */
namespace app\backend\modules\returns;

use Yii;
use yii\base\Module;

class ReturnModule extends Module
{
    public $controllerNamespace = 'app\backend\modules\returns\controllers';

    public function init()
    {
        parent::init();
        Yii::setAlias('@returns', $this->getBasePath());
    }
}
