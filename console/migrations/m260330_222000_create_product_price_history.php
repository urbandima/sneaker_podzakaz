<?php

/**
 * Миграция: Создание таблицы product_price_history
 * 
 * Рекомендация #22: Price History
 */
class m260330_222000_create_product_price_history extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%product_price_history}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'old_price' => $this->decimal(10, 2)->notNull(),
            'new_price' => $this->decimal(10, 2)->notNull(),
            'reason' => $this->string(255)->null(),
            'changed_by' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        $this->createIndex('idx-product_price_history-product_id', '{{%product_price_history}}', 'product_id');
        $this->createIndex('idx-product_price_history-created_at', '{{%product_price_history}}', 'created_at');
        
        echo "Таблица product_price_history создана\n";
    }
    
    public function safeDown()
    {
        $this->dropTable('{{%product_price_history}}');
    }
}
