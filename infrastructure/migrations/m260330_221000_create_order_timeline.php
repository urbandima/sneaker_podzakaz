<?php

/**
 * Миграция: Создание таблицы order_timeline для истории заказа
 * 
 * Рекомендация #17: Order Timeline
 */
class m260330_221000_create_order_timeline extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%order_timeline}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'event' => $this->string(50)->notNull(),
            'data' => $this->json()->null(),
            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        $this->createIndex('idx-order_timeline-order_id', '{{%order_timeline}}', 'order_id');
        $this->createIndex('idx-order_timeline-event', '{{%order_timeline}}', 'event');
        $this->createIndex('idx-order_timeline-created_at', '{{%order_timeline}}', 'created_at');
        
        echo "Таблица order_timeline создана\n";
    }
    
    public function safeDown()
    {
        $this->dropTable('{{%order_timeline}}');
    }
}
