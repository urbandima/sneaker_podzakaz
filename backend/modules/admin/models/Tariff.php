<?php
/**
 * Псевдоним для обратной совместимости.
 * Каноническая модель: app\models\Tariff (catalog/models/Tariff.php)
 */

class_alias(
    \app\backend\modules\catalog\models\Tariff::class,
    'app\backend\modules\admin\models\Tariff'
);
