<?php

use yii\db\Migration;

class m260426_210000_add_sla_and_purchase_fields extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        if ($schema === null) {
            echo "    > skip: {{%order}} table does not exist yet\n";
            return;
        }
        $cols = $schema->columnNames;

        if (!in_array('purchase_currency', $cols, true)) {
            $this->addColumn('order', 'purchase_currency', $this->string(10)->null()->after('purchase_cost'));
        }
        if (!in_array('purchase_user_id', $cols, true)) {
            $this->addColumn('order', 'purchase_user_id', $this->integer()->null()->after('purchase_currency'));
        }
        if (!in_array('expected_delivery_at', $cols, true)) {
            $this->addColumn('order', 'expected_delivery_at', $this->dateTime()->null()->after('purchase_user_id'));
        }

        if (!$this->indexExists('order', 'idx_order_expected_delivery')) {
            $this->createIndex('idx_order_expected_delivery', 'order', 'expected_delivery_at');
        }
    }

    public function safeDown()
    {
        if ($this->indexExists('order', 'idx_order_expected_delivery')) {
            $this->dropIndex('idx_order_expected_delivery', 'order');
        }
        $schema = $this->db->getTableSchema('{{%order}}', true);
        $cols = $schema ? $schema->columnNames : [];

        if (in_array('expected_delivery_at', $cols, true)) {
            $this->dropColumn('order', 'expected_delivery_at');
        }
        if (in_array('purchase_user_id', $cols, true)) {
            $this->dropColumn('order', 'purchase_user_id');
        }
        if (in_array('purchase_currency', $cols, true)) {
            $this->dropColumn('order', 'purchase_currency');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $row = $this->db->createCommand(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = :name",
            [':name' => $indexName]
        )->queryOne();
        return $row !== false;
    }
}
