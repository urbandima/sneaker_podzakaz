<?php

use yii\db\Migration;

/**
 * Migration for return functionality
 */
class m240315_120300_create_return_tables extends Migration
{
    public function safeUp()
    {
        // Return policy table
        $this->createTable('{{%return_policy}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull(),
            'return_period_days' => $this->integer()->notNull()->defaultValue(14),
            'requires_original_packaging' => $this->boolean()->defaultValue(true),
            'requires_tags' => $this->boolean()->defaultValue(true),
            'restocking_fee' => $this->decimal(5,2)->defaultValue(0),
            'free_return_shipping' => $this->boolean()->defaultValue(false),
            'conditions_text' => $this->text(),
            'is_default' => $this->boolean()->defaultValue(false),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Return requests table
        $this->createTable('{{%return_request}}', [
            'id' => $this->primaryKey(),
            'return_number' => $this->string(32)->notNull()->unique(),
            'order_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer()->notNull(),
            'reason' => "enum('size_not_fit','quality_issue','wrong_item','changed_mind','other') NOT NULL",
            'reason_details' => $this->string(500),
            'items' => $this->text()->notNull(), // JSON array of returned items
            'refund_amount' => $this->decimal(10,2)->notNull(),
            'status' => "enum('pending','approved','rejected','processing','completed','cancelled') NOT NULL DEFAULT 'pending'",
            'tracking_number' => $this->string(100),
            'admin_comment' => $this->string(500),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Skip foreign keys for now - will be added after dependent tables are created
        
        // Insert default return policy
        $this->batchInsert('{{%return_policy}}', ['name', 'return_period_days', 'requires_original_packaging', 'requires_tags', 'restocking_fee', 'free_return_shipping', 'conditions_text', 'is_default'], [
            ['Стандартная политика', 14, true, true, 0, false, 'Товар должен быть в оригинальной упаковке с бирками', true],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%return_request}}');
        $this->dropTable('{{%return_policy}}');
    }
}
