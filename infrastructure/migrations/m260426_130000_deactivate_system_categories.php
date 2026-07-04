<?php

use yii\db\Migration;

/**
 * Deactivates system/placeholder categories that should not appear in the storefront.
 * Covers: slug LIKE 'item-%', name='-', name='', name='МойСклад', name LIKE 'служебная%'.
 */
class m260426_130000_deactivate_system_categories extends Migration
{
    public function safeUp()
    {
        $this->execute("
            UPDATE `category`
            SET `is_active` = 0
            WHERE `slug` LIKE 'item-%'
               OR `name` = '-'
               OR `name` = ''
               OR `name` = 'МойСклад'
               OR `name` LIKE 'служебная%'
               OR `name` LIKE 'Служебная%'
        ");
    }

    public function safeDown()
    {
        // Intentionally not reversible — admin can re-enable categories manually
        return true;
    }
}
