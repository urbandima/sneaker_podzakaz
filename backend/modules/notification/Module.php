<?php

namespace app\backend\modules\notification;

use Yii;

/**
 * NotificationModule — Модуль уведомлений
 * 
 * НАЗНАЧЕНИЕ:
 * Управление уведомлениями пользователей: email, push, внутренние уведомления.
 * 
 * ФУНКЦИИ:
 * - Отправка email уведомлений
 * - Push уведомления
 * - Внутренние уведомления в личном кабинете
 * - История уведомлений
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\backend\modules\notification\controllers';

    public function init()
    {
        parent::init();
    }
}
