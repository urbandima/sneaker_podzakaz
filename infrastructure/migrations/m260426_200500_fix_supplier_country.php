<?php

use yii\db\Migration;

class m260426_200500_fix_supplier_country extends Migration
{
    public function safeUp()
    {
        // На чистой инсталляции {{%supplier}} на этот момент ещё не существует — её создаёт более
        // поздняя миграция m260427_000001_supplier_grouping_fields. Чинить в свежей таблице нечего.
        if ($this->db->schema->getTableSchema('{{%supplier}}', true) === null) {
            echo "    > skipped: {{%supplier}} ещё не создана, добавит m260427_000001_supplier_grouping_fields\n";
            return true;
        }

        $this->db->createCommand(
            "UPDATE {{%supplier}} SET country = 'BY' WHERE (phone LIKE '+375%' OR phone LIKE '375%') AND (country = 'CN' OR country IS NULL)"
        )->execute();
    }

    public function safeDown()
    {
        // Non-destructive: no rollback
    }
}
