<?php

use yii\db\Migration;

class m260424_120000_add_field_change_to_order_history extends Migration
{
    public function up()
    {
        // Add generic field-change columns alongside the existing status columns
        $this->addColumn('{{%order_history}}', 'field',     $this->string(64)->null()->after('comment'));
        $this->addColumn('{{%order_history}}', 'old_value', $this->text()->null()->after('field'));
        $this->addColumn('{{%order_history}}', 'new_value', $this->text()->null()->after('old_value'));

        // Backfill field/old_value/new_value from existing status-change rows
        $this->execute("
            UPDATE order_history
            SET field     = 'status',
                old_value = old_status,
                new_value = new_status
            WHERE field IS NULL AND new_status IS NOT NULL
        ");

        // Index for fast per-order lookups
        $this->createIndex('idx_order_history_order_created', '{{%order_history}}', ['order_id', 'created_at']);
    }

    public function down()
    {
        $this->dropIndex('idx_order_history_order_created', '{{%order_history}}');
        $this->dropColumn('{{%order_history}}', 'new_value');
        $this->dropColumn('{{%order_history}}', 'old_value');
        $this->dropColumn('{{%order_history}}', 'field');
    }
}
