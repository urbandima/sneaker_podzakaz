<?php

use yii\db\Migration;

class m260424_120000_add_field_change_to_order_history extends Migration
{
    public function safeUp()
    {
        $cols = array_column($this->db->createCommand('SHOW COLUMNS FROM {{%order_history}}')->queryAll(), 'Field');

        if (!in_array('field', $cols)) {
            $this->addColumn('{{%order_history}}', 'field', $this->string(64)->null()->after('comment'));
        }
        if (!in_array('old_value', $cols)) {
            $this->addColumn('{{%order_history}}', 'old_value', $this->text()->null()->after('field'));
        }
        if (!in_array('new_value', $cols)) {
            $this->addColumn('{{%order_history}}', 'new_value', $this->text()->null()->after('old_value'));
        }

        $this->execute("
            UPDATE {{%order_history}}
            SET field     = 'status',
                old_value = old_status,
                new_value = new_status
            WHERE field IS NULL AND new_status IS NOT NULL
        ");

        $indexes = array_column($this->db->createCommand('SHOW INDEX FROM {{%order_history}}')->queryAll(), 'Key_name');
        if (!in_array('idx_order_history_order_created', $indexes)) {
            $this->createIndex('idx_order_history_order_created', '{{%order_history}}', ['order_id', 'created_at']);
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx_order_history_order_created', '{{%order_history}}');
        $this->dropColumn('{{%order_history}}', 'new_value');
        $this->dropColumn('{{%order_history}}', 'old_value');
        $this->dropColumn('{{%order_history}}', 'field');
    }
}
