<?php

use yii\db\Migration;

/**
 * Миграция для создания таблиц программы лояльности
 */
class m250315_120200_create_loyalty_tables extends Migration
{
    public function safeUp()
    {
        // Таблица уровней программы лояльности
        $this->createTable('{{%loyalty_program}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->notNull(),
            'level' => $this->string(50)->notNull(),
            'min_points' => $this->integer()->notNull(),
            'points_multiplier' => $this->decimal(3, 2)->notNull()->defaultValue(1.00),
            'discount_percent' => $this->decimal(5, 2)->defaultValue(0),
            'benefits' => $this->text(),
            'color' => $this->string(50),
            'icon' => $this->string(50),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
        ]);
        
        $this->createIndex('idx-loyalty_program-level', '{{%loyalty_program}}', 'level', true);
        
        // Таблица баллов лояльности
        $this->createTable('{{%loyalty_points}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'points' => $this->integer()->notNull(),
            'balance' => $this->integer()->notNull(),
            'type' => $this->string(50)->notNull(),
            'description' => $this->string(255),
            'order_id' => $this->integer(),
            'related_customer_id' => $this->integer(),
            'expires_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull(),
        ]);
        
        $this->createIndex('idx-loyalty_points-customer', '{{%loyalty_points}}', 'customer_id');
        $this->createIndex('idx-loyalty_points-order', '{{%loyalty_points}}', 'order_id');
        $this->createIndex('idx-loyalty_points-type', '{{%loyalty_points}}', 'type');
        $this->createIndex('idx-loyalty_points-expires', '{{%loyalty_points}}', 'expires_at');
        
        $this->addForeignKey('fk-loyalty_points-customer', '{{%loyalty_points}}', 'customer_id', '{{%customer}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-loyalty_points-order', '{{%loyalty_points}}', 'order_id', '{{%order}}', 'id', 'SET NULL');
        
        // Добавляем поле реферала в таблицу клиентов
        $this->addColumn('{{%customer}}', 'referral_code', $this->string(50)->unique());
        $this->addColumn('{{%customer}}', 'referred_by', $this->integer());
        $this->addColumn('{{%customer}}', 'loyalty_level', $this->string(50));
        
        $this->createIndex('idx-customer-referral', '{{%customer}}', 'referral_code', true);
        $this->addForeignKey('fk-customer-referred', '{{%customer}}', 'referred_by', '{{%customer}}', 'id', 'SET NULL');
        
        // Вставляем уровни программы лояльности
        $this->batchInsert('{{%loyalty_program}}', 
            ['name', 'level', 'min_points', 'points_multiplier', 'discount_percent', 'benefits', 'color', 'icon', 'sort_order'],
            [
                ['Бронза', 'bronze', 0, 1.0, 0, json_encode(['Базовое начисление баллов']), '#CD7F32', 'medal-bronze', 1],
                ['Серебро', 'silver', 1000, 1.2, 3, json_encode(['+20% к начислению баллов', 'Скидка 3%']), '#C0C0C0', 'medal-silver', 2],
                ['Золото', 'gold', 5000, 1.5, 5, json_encode(['+50% к начислению баллов', 'Скидка 5%', 'Приоритетная поддержка']), '#FFD700', 'medal-gold', 3],
                ['Платина', 'platinum', 10000, 2.0, 10, json_encode(['+100% к начислению баллов', 'Скидка 10%', 'Персональный менеджер', 'Бесплатная доставка']), '#E5E4E2', 'medal-platinum', 4],
            ]
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%customer}}', 'loyalty_level');
        $this->dropColumn('{{%customer}}', 'referred_by');
        $this->dropColumn('{{%customer}}', 'referral_code');
        
        $this->dropTable('{{%loyalty_points}}');
        $this->dropTable('{{%loyalty_program}}');
    }
}
