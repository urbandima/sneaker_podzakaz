<?php

use yii\db\Migration;

/**
 * Миграция для создания таблиц купонов
 */
class m250315_120000_create_coupon_tables extends Migration
{
    public function safeUp()
    {
        // Таблица купонов
        $this->createTable('{{%coupon}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(50)->notNull()->unique(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'type' => $this->string(50)->notNull()->defaultValue('percentage'),
            'value' => $this->decimal(10, 2)->notNull(),
            'min_order_amount' => $this->decimal(10, 2),
            'max_discount' => $this->decimal(10, 2),
            'max_uses' => $this->integer(),
            'current_uses' => $this->integer()->notNull()->defaultValue(0),
            'max_uses_per_user' => $this->integer(),
            'valid_from' => $this->dateTime(),
            'valid_until' => $this->dateTime(),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'is_first_order' => $this->boolean()->notNull()->defaultValue(false),
            'buy_quantity' => $this->integer(),
            'get_quantity' => $this->integer(),
            'applicable_products' => $this->text(),
            'applicable_categories' => $this->text(),
            'excluded_products' => $this->text(),
            'excluded_categories' => $this->text(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        
        $this->createIndex('idx-coupon-code', '{{%coupon}}', 'code');
        $this->createIndex('idx-coupon-active', '{{%coupon}}', ['is_active', 'valid_from', 'valid_until']);
        
        // Таблица истории использования купонов
        $this->createTable('{{%coupon_usage}}', [
            'id' => $this->primaryKey(),
            'coupon_id' => $this->integer()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'user_id' => $this->integer(),
            'discount_amount' => $this->decimal(10, 2)->notNull(),
            'used_at' => $this->dateTime()->notNull(),
        ]);
        
        $this->createIndex('idx-coupon_usage-coupon', '{{%coupon_usage}}', 'coupon_id');
        $this->createIndex('idx-coupon_usage-order', '{{%coupon_usage}}', 'order_id');
        $this->createIndex('idx-coupon_usage-user', '{{%coupon_usage}}', 'user_id');
        
        $this->addForeignKey('fk-coupon_usage-coupon', '{{%coupon_usage}}', 'coupon_id', '{{%coupon}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-coupon_usage-order', '{{%coupon_usage}}', 'order_id', '{{%order}}', 'id', 'CASCADE');
        
        // Добавляем поля купона в таблицу заказов
        $this->addColumn('{{%order}}', 'coupon_id', $this->integer());
        $this->addColumn('{{%order}}', 'coupon_code', $this->string(50));
        $this->addColumn('{{%order}}', 'discount_amount', $this->decimal(10, 2)->defaultValue(0));
        
        $this->createIndex('idx-order-coupon', '{{%order}}', 'coupon_id');
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'discount_amount');
        $this->dropColumn('{{%order}}', 'coupon_code');
        $this->dropColumn('{{%order}}', 'coupon_id');
        
        $this->dropTable('{{%coupon_usage}}');
        $this->dropTable('{{%coupon}}');
    }
}
