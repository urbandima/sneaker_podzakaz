<?php

use yii\db\Migration;

/**
 * Migration for coupon functionality
 */
class m240315_120000_create_coupon_tables extends Migration
{
    public function safeUp()
    {
        // Coupons table
        $this->createTable('{{%coupon}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'type' => "enum('percentage','fixed') NOT NULL DEFAULT 'percentage'",
            'value' => $this->decimal(10,2)->notNull(),
            'min_order_amount' => $this->decimal(10,2)->defaultValue(0),
            'max_discount_amount' => $this->decimal(10,2),
            'usage_limit' => $this->integer(),
            'usage_count' => $this->integer()->defaultValue(0),
            'start_date' => $this->date(),
            'end_date' => $this->date(),
            'description' => $this->string(255),
            'status' => "enum('active','inactive','expired') NOT NULL DEFAULT 'active'",
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Coupon usage table
        $this->createTable('{{%coupon_usage}}', [
            'id' => $this->primaryKey(),
            'coupon_id' => $this->integer()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer(),
            'discount_amount' => $this->decimal(10,2)->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        $this->addForeignKey('fk_coupon_usage_coupon', '{{%coupon_usage}}', 'coupon_id', '{{%coupon}}', 'id', 'CASCADE');
        // Skip foreign keys for now - will be added after dependent tables are created
        
        // Insert sample coupons
        $this->batchInsert('{{%coupon}}', ['code', 'type', 'value', 'min_order_amount', 'description', 'status'], [
            ['WELCOME10', 'percentage', 10, 0, 'Скидка 10% для новых клиентов', 'active'],
            ['SAVE20', 'percentage', 20, 100, 'Скидка 20% при заказе от 100 BYN', 'active'],
            ['FLAT15', 'fixed', 15, 0, 'Скидка 15 BYN на любой заказ', 'active'],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%coupon_usage}}');
        $this->dropTable('{{%coupon}}');
    }
}
