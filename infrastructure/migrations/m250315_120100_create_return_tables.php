<?php

use yii\db\Migration;

/**
 * Миграция для создания таблиц возвратов
 */
class m250315_120100_create_return_tables extends Migration
{
    public function safeUp()
    {
        // Таблица политики возврата
        $this->createTable('{{%return_policy}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'return_period_days' => $this->integer()->notNull()->defaultValue(14),
            'requires_original_packaging' => $this->boolean()->notNull()->defaultValue(true),
            'requires_tags' => $this->boolean()->notNull()->defaultValue(true),
            'allows_worn_items' => $this->boolean()->notNull()->defaultValue(false),
            'restocking_fee' => $this->decimal(5, 2)->defaultValue(0),
            'free_return_shipping' => $this->boolean()->notNull()->defaultValue(false),
            'excluded_categories' => $this->text(),
            'excluded_products' => $this->text(),
            'is_default' => $this->boolean()->notNull()->defaultValue(false),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        
        // Таблица заявок на возврат
        $this->createTable('{{%return_request}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer(),
            'return_number' => $this->string(50)->notNull(),
            'status' => $this->string(50)->notNull()->defaultValue('pending'),
            'reason' => $this->string(50)->notNull(),
            'comment' => $this->text(),
            'admin_comment' => $this->text(),
            'items_json' => $this->text()->notNull(),
            'refund_amount' => $this->decimal(10, 2)->notNull(),
            'refund_method' => $this->string(50),
            'refund_transaction' => $this->string(255),
            'pickup_address' => $this->text(),
            'pickup_date' => $this->date(),
            'tracking_number' => $this->string(100),
            'processed_at' => $this->dateTime(),
            'completed_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        
        $this->createIndex('idx-return_request-order', '{{%return_request}}', 'order_id');
        $this->createIndex('idx-return_request-customer', '{{%return_request}}', 'customer_id');
        $this->createIndex('idx-return_request-status', '{{%return_request}}', 'status');
        $this->createIndex('idx-return_request-number', '{{%return_request}}', 'return_number', true);
        
        $this->addForeignKey('fk-return_request-order', '{{%return_request}}', 'order_id', '{{%order}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-return_request-customer', '{{%return_request}}', 'customer_id', '{{%customer}}', 'id', 'SET NULL');
        
        // Вставляем политику по умолчанию
        $this->insert('{{%return_policy}}', [
            'name' => 'Стандартная политика возврата',
            'description' => 'Возврат товара в течение 14 дней при сохранении упаковки и ярлыков',
            'return_period_days' => 14,
            'requires_original_packaging' => true,
            'requires_tags' => true,
            'allows_worn_items' => false,
            'restocking_fee' => 0,
            'free_return_shipping' => false,
            'is_default' => true,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-return_request-customer', '{{%return_request}}');
        $this->dropForeignKey('fk-return_request-order', '{{%return_request}}');
        $this->dropTable('{{%return_request}}');
        $this->dropTable('{{%return_policy}}');
    }
}
