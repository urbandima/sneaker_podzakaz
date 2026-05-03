<?php

use yii\db\Migration;

class m260503_130000_add_moysklad_id_to_order extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        if ($schema === null) {
            echo "    > skip: {{%order}} table does not exist yet\n";
            return;
        }
        if ($schema->getColumn('moysklad_id') === null) {
            $this->addColumn(
                '{{%order}}',
                'moysklad_id',
                $this->string(36)->null()->after('ms_number')->comment('UUID соответствующего customerorder в МойСклад')
            );
            $this->createIndex('idx_order_moysklad_id', '{{%order}}', 'moysklad_id');
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        if ($schema && $schema->getColumn('moysklad_id') !== null) {
            try { $this->dropIndex('idx_order_moysklad_id', '{{%order}}'); } catch (\Throwable $e) {}
            $this->dropColumn('{{%order}}', 'moysklad_id');
        }
    }
}
