<?php

/**
 * Миграция: Добавление soft delete (deleted_at) в таблицы
 * 
 * Рекомендация #12 из аудита
 */
class m260330_220000_add_soft_delete_to_tables extends \yii\db\Migration
{
    public function safeUp()
    {
        // Добавляем deleted_at в order
        $this->addColumn('{{%order}}', 'deleted_at', $this->integer()->null());
        $this->createIndex('idx-order-deleted_at', '{{%order}}', 'deleted_at');
        
        // Добавляем deleted_at в customer
        $this->addColumn('{{%customer}}', 'deleted_at', $this->integer()->null());
        $this->createIndex('idx-customer-deleted_at', '{{%customer}}', 'deleted_at');
        
        // Добавляем deleted_at в product
        $this->addColumn('{{%product}}', 'deleted_at', $this->integer()->null());
        $this->createIndex('idx-product-deleted_at', '{{%product}}', 'deleted_at');
        
        // Добавляем deleted_at в coupon
        $this->addColumn('{{%coupon}}', 'deleted_at', $this->integer()->null());
        $this->createIndex('idx-coupon-deleted_at', '{{%coupon}}', 'deleted_at');
        
        echo "Soft delete добавлен в order, customer, product, coupon\n";
    }
    
    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'deleted_at');
        $this->dropColumn('{{%customer}}', 'deleted_at');
        $this->dropColumn('{{%product}}', 'deleted_at');
        $this->dropColumn('{{%coupon}}', 'deleted_at');
    }
}
