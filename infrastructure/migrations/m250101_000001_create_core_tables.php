<?php

use yii\db\Migration;

/**
 * Create basic tables for core functionality
 */
class m250101_000001_create_core_tables extends Migration
{
    public function safeUp()
    {
        // Create customer table first
        $this->createTable('{{%customer}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string()->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'first_name' => $this->string(),
            'last_name' => $this->string(),
            'phone' => $this->string(),
            'address' => $this->text(),
            'is_active' => $this->boolean()->defaultValue(1),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add foreign key to existing order table
        $this->addForeignKey('fk_order_customer', '{{%order}}', 'customer_id', '{{%customer}}', 'id', 'SET NULL');
    }

    public function safeDown()
    {
        // Only drop the FK we added and the customer table we created.
        // The order table was created by an earlier migration and must NOT be dropped here.
        $this->dropForeignKey('fk_order_customer', '{{%order}}');
        $this->dropTable('{{%customer}}');
    }
}
