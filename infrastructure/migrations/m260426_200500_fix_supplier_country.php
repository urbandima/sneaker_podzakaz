<?php

use yii\db\Migration;

class m260426_200500_fix_supplier_country extends Migration
{
    public function safeUp()
    {
        $this->db->createCommand(
            "UPDATE {{%supplier}} SET country = 'BY' WHERE (phone LIKE '+375%' OR phone LIKE '375%') AND (country = 'CN' OR country IS NULL)"
        )->execute();
    }

    public function safeDown()
    {
        // Non-destructive: no rollback
    }
}
