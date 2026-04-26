<?php

/**
 * LoyaltyModule — Модуль программы лояльности
 *
 * Управление бонусами и баллами лояльности: начисление, списание,
 * уровни клиентов, вознаграждения.
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
