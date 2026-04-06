<?php
/**
 * Псевдоним для обратной совместимости.
 * Каноническая модель: app\models\Tariff (catalog/models/Tariff.php)
 */

class_alias(
    \app\models\Tariff::class,
    'app\backend\modules\admin\models\Tariff'
);
