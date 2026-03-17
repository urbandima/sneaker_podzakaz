<?php

use yii\db\Migration;

/**
 * Добавление поля size в таблицу order_item для хранения размера товара
 */
class m260317_231000_add_size_to_order_item extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Добавляем поле size в таблицу order_item
        $this->addColumn('{{%order_item}}', 'size', $this->string(50)->defaultValue(null)->after('product_name'));
        
        // Добавляем индекс для поля size
        $this->createIndex('idx-order_item-size', '{{%order_item}}', 'size');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем индекс
        $this->dropIndex('idx-order_item-size', '{{%order_item}}');
        
        // Удаляем поле size
        $this->dropColumn('{{%order_item}}', 'size');
    }
}
