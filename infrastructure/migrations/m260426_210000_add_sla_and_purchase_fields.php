<?php

use yii\db\Migration;

class m260426_210000_add_sla_and_purchase_fields extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('order');
        $cols   = $schema ? $schema->columnNames : [];

        if (!in_array('purchase_currency', $cols)) {
            $this->addColumn('order', 'purchase_currency', $this->string(10)->null()->after('purchase_cost'));
        }
        if (!in_array('purchase_user_id', $cols)) {
            $this->addColumn('order', 'purchase_user_id', $this->integer()->null()->after('purchase_currency'));
        }
        if (!in_array('expected_delivery_at', $cols)) {
            $this->addColumn('order', 'expected_delivery_at', $this->dateTime()->null()->after('purchase_user_id'));
            $this->createIndex('idx_order_expected_delivery', 'order', 'expected_delivery_at');
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx_order_expected_delivery', 'order');
        $this->dropColumn('order', 'expected_delivery_at');
        $this->dropColumn('order', 'purchase_user_id');
        $this->dropColumn('order', 'purchase_currency');
    }
}
