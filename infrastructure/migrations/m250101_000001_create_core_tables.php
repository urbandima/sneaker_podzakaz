<?php

use yii\db\Migration;

/**
 * Create basic tables for core functionality
 */
class m250101_000001_create_core_tables extends Migration
{
    public function safeUp()
    {
        // customer table is also created by the earlier m241228_213000 migration.
        // Skip creation if it already exists (e.g. when that migration ran first).
        if ($this->db->schema->getTableSchema('{{%customer}}', true) === null) {
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
        }

        // Add FK from order to customer if not already added by m241228_213000
        $tableSchema = $this->db->schema->getTableSchema('{{%order}}', true);
        if ($tableSchema && !isset($tableSchema->foreignKeys['fk_order_customer'])
            && !isset($tableSchema->foreignKeys['fk-order-customer_id'])) {
            $this->addForeignKey('fk_order_customer', '{{%order}}', 'customer_id', '{{%customer}}', 'id', 'SET NULL');
        }
    }

    public function safeDown()
    {
        // Only drop the FK we added and the customer table we created.
        // The order table was created by an earlier migration and must NOT be dropped here.
        $this->dropForeignKey('fk_order_customer', '{{%order}}');
        $this->dropTable('{{%customer}}');
    }
}
