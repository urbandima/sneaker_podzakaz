<?php

/**
 * CouponModule — Модуль купонов и скидок
 * 
 * НАЗНАЧЕНИЕ:
 * Управление купонными кодами, скидками, промо-акциями.
 * 
 * ФУНКЦИИ:
 * - Создание и управление купонами
 * - Валидация купонов при оформлении заказа
 * - Применение скидок к заказу
 * - Отслеживание использования купонов
 * - Автоматические скидки и правила
 * 
 * КОМПОНЕНТЫ:
 * - CouponController - контроллер управления купонами
 * - Coupon - модель купона
 * - CouponUsage - история использования
 * - CouponService - сервис валидации и применения
 * 
 * ТИПЫ КУПОНОВ:
 * - percentage: процентная скидка
 * - fixed: фиксированная сумма
 * - free_shipping: бесплатная доставка
 * - buy_x_get_y: купи X получи Y
 */
namespace app\backend\modules\coupon;

use Yii;
use yii\base\Module;

class CouponModule extends Module
{
    public $controllerNamespace = 'app\backend\modules\coupon\controllers';
    
    public function init()
    {
        parent::init();
        
        // Регистрация модуля в приложении
        Yii::setAlias('@coupon', $this->getBasePath());
    }
}
