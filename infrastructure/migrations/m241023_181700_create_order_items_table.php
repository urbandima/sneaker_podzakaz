<?php

use yii\db\Migration;

class m241023_181700_create_order_items_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%order_item}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_name' => $this->string(255)->notNull(),
            'quantity' => $this->integer()->notNull()->defaultValue(1),
            'price' => $this->decimal(10, 2)->notNull(),
            'total' => $this->decimal(10, 2)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Индексы
        $this->createIndex('idx-order_item-order_id', '{{%order_item}}', 'order_id');

        // Внешний ключ (отключено для SQLite)
        // $this->addForeignKey(
        //     'fk-order_item-order_id',
        //     '{{%order_item}}',
        //     'order_id',
        //     '{{%order}}',
        //     'id',
        //     'CASCADE'
        // );
    }

    public function safeDown()
    {
        // $this->dropForeignKey('fk-order_item-order_id', '{{%order_item}}');
        $this->dropTable('{{%order_item}}');
    }
}
