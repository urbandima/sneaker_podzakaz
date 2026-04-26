<?php
use yii\db\Migration;

/**
 * Z41: Add order_id to payment table so "Заказ" column shows linked order.
 * Z42: Add bank_details to payment table for "Реквизит банка" display.
 */
class m260426_200400_add_order_id_to_payment extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema;

        // Z41: order_id — foreign key to `order` table
        if (!isset($schema->getTableSchema('payment')->columns['order_id'])) {
            $this->addColumn('payment', 'order_id', $this->integer()->null()->after('id'));
        }

        // Z42: bank_details — human-readable bank requisite string
        if (!isset($schema->getTableSchema('payment')->columns['bank_details'])) {
            $this->addColumn('payment', 'bank_details', $this->string(255)->null()->after('bank_reference'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema;

        if (isset($schema->getTableSchema('payment')->columns['bank_details'])) {
            $this->dropColumn('payment', 'bank_details');
        }
        if (isset($schema->getTableSchema('payment')->columns['order_id'])) {
            $this->dropColumn('payment', 'order_id');
        }
    }
}
