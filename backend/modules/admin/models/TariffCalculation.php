<?php
/**
 * Псевдоним для обратной совместимости.
 * Каноническая модель: app\models\TariffCalculation (catalog/models/TariffCalculation.php)
 */

class_alias(
    \app\backend\modules\catalog\models\TariffCalculation::class,
    'app\backend\modules\admin\models\TariffCalculation'
);
