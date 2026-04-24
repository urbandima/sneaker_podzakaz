<?php

use yii\db\Migration;

class m260414_100000_add_payment_method_to_order extends Migration
{
    public function safeUp()
    {
        $cols = $this->db->getTableSchema('{{%order}}')->columnNames;
        if (!in_array('payment_method', $cols)) {
            $this->addColumn('{{%order}}', 'payment_method', $this->string(50)->null()->defaultValue(null)->after('delivery_method'));
        }
    }

    public function safeDown()
    {
        $cols = $this->db->getTableSchema('{{%order}}')->columnNames;
        if (in_array('payment_method', $cols)) {
            $this->dropColumn('{{%order}}', 'payment_method');
        }
    }
}
