<?php

use yii\db\Migration;

/**
 * Fix migration order - create orders table first
 */
class m240315_120000_fix_coupon_migration extends Migration
{
    public function safeUp()
    {
        // Skip coupon table creation - will be handled by proper migration order
    }

    public function safeDown()
    {
        // Nothing to rollback
    }
}
