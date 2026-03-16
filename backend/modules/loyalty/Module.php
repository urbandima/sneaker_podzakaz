<?php

/**
 * LoyaltyModule — Модуль программы лояльности
 * 
 * НАЗНАЧЕНИЕ:
 * Управление бонусами и баллами лояльности: начисление, списание,
 * уровни клиентов, вознаграждения.
 * 
 * ФУНКЦИИ:
 * - Начисление баллов за покупки
 * - Списание баллов при оплате
 * - Уровни клиентов (Bronze, Silver, Gold, Platinum)
 * - Вознаграждения за действия
 * - Реферальная программа
 * 
 * КОМПОНЕНТЫ:
 * - LoyaltyController - контроллер управления
 * - LoyaltyProgram - модель программы
 * - LoyaltyPoints - модель баллов
 * - LoyaltyService - сервис лояльности
 */
namespace app\backend\modules\loyalty;

use Yii;
use yii\base\Module;

class LoyaltyModule extends Module
{
    public $controllerNamespace = 'app\backend\modules\loyalty\controllers';
    
    /** @var int Баллы за 1 BYN */
    public $pointsPerByn = 10;
    
    /** @var float Стоимость 1 балла в BYN */
    public $pointValue = 0.01;
    
    /** @var int Минимум баллов для списания */
    public $minPointsToRedeem = 100;
    
    public function init()
    {
        parent::init();
        Yii::setAlias('@loyalty', $this->getBasePath());
    }
}
