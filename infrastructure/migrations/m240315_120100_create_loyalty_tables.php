<?php

use yii\db\Migration;

/**
 * Migration for loyalty functionality
 */
class m240315_120100_create_loyalty_tables extends Migration
{
    public function safeUp()
    {
        // Loyalty levels table
        $this->createTable('{{%loyalty_level}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->notNull(),
            'level' => "enum('bronze','silver','gold','platinum') NOT NULL",
            'min_points' => $this->integer()->notNull(),
            'points_multiplier' => $this->decimal(3,2)->defaultValue(1.00),
            'discount_percent' => $this->decimal(5,2)->defaultValue(0),
            'benefits' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Loyalty points table
        $this->createTable('{{%loyalty_points}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'type' => "enum('purchase','redeem','bonus','referral','signup','review') NOT NULL",
            'points' => $this->integer()->notNull(),
            'balance' => $this->integer()->notNull(),
            'description' => $this->string(255),
            'order_id' => $this->integer(),
            'expires_at' => $this->timestamp(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        // Customer loyalty level
        $this->createTable('{{%customer_loyalty_level}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'loyalty_level_id' => $this->integer()->notNull(),
            'total_spent' => $this->decimal(10,2)->defaultValue(0),
            'points_earned' => $this->integer()->defaultValue(0),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Skip foreign keys for now - will be added after dependent tables are created
        
        // Insert loyalty levels
        $this->batchInsert('{{%loyalty_level}}', ['name', 'level', 'min_points', 'points_multiplier', 'discount_percent', 'benefits'], [
            ['Бронза', 'bronze', 0, 1.00, 0, 'Базовые преимущества'],
            ['Серебро', 'silver', 1000, 1.10, 5, '+10% к баллам, скидка 5%'],
            ['Золото', 'gold', 3000, 1.20, 10, '+20% к баллам, скидка 10%'],
            ['Платина', 'platinum', 10000, 1.50, 15, '+50% к баллам, скидка 15%, VIP поддержка'],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%customer_loyalty_level}}');
        $this->dropTable('{{%loyalty_points}}');
        $this->dropTable('{{%loyalty_level}}');
    }
}
