<?php

use yii\db\Migration;

class m241023_181800_create_order_history_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%order_history}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'old_status' => $this->string(50),
            'new_status' => $this->string(50)->notNull(),
            'comment' => $this->text(),
            'changed_by' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Индексы
        $this->createIndex('idx-order_history-order_id', '{{%order_history}}', 'order_id');
        $this->createIndex('idx-order_history-created_at', '{{%order_history}}', 'created_at');

        // Внешние ключи (отключено для SQLite)
        // $this->addForeignKey(
        //     'fk-order_history-order_id',
        //     '{{%order_history}}',
        //     'order_id',
        //     '{{%order}}',
        //     'id',
        //     'CASCADE'
        // );

        // $this->addForeignKey(
        //     'fk-order_history-changed_by',
        //     '{{%order_history}}',
        //     'changed_by',
        //     '{{%user}}',
        //     'id',
        //     'SET NULL'
        // );
    }

    public function safeDown()
    {
        // $this->dropForeignKey('fk-order_history-changed_by', '{{%order_history}}');
        // $this->dropForeignKey('fk-order_history-order_id', '{{%order_history}}');
        $this->dropTable('{{%order_history}}');
    }
}
