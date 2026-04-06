<?php
/**
 * Псевдоним для обратной совместимости.
 * Каноническая модель: app\backend\modules\account\models\Customer
 */

class_alias(
    \app\backend\modules\account\models\Customer::class,
    'app\models\Customer'
);
