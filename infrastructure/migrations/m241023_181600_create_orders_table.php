<?php

use yii\db\Migration;

class m241023_181600_create_orders_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%order}}', [
            'id' => $this->primaryKey(),
            'order_number' => $this->string(50)->notNull()->unique(),
            'token' => $this->string(100)->notNull()->unique(),
            
            // Информация о клиенте
            'client_name' => $this->string(255)->notNull(),
            'client_phone' => $this->string(50),
            'client_email' => $this->string(255),
            
            // Финансы
            'total_amount' => $this->decimal(10, 2)->notNull()->defaultValue(0),
            
            // Статус и сроки
            'status' => $this->string(50)->notNull()->defaultValue('created'),
            'delivery_date' => $this->string(255),
            
            // Файлы и подтверждение
            'payment_proof' => $this->string(255),
            'payment_uploaded_at' => $this->integer(),
            'offer_accepted' => $this->boolean()->defaultValue(false),
            'offer_accepted_at' => $this->integer(),
            
            // Связи
            'created_by' => $this->integer()->notNull(),
            'assigned_logist' => $this->integer(),
            
            // Временные метки
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Создаем индексы
        $this->createIndex('idx-order-number', '{{%order}}', 'order_number');
        $this->createIndex('idx-order-token', '{{%order}}', 'token');
        $this->createIndex('idx-order-status', '{{%order}}', 'status');
        $this->createIndex('idx-order-created_by', '{{%order}}', 'created_by');
        $this->createIndex('idx-order-assigned_logist', '{{%order}}', 'assigned_logist');

        // Внешние ключи (отключено для SQLite)
        // $this->addForeignKey(
        //     'fk-order-created_by',
        //     '{{%order}}',
        //     'created_by',
        //     '{{%user}}',
        //     'id',
        //     'CASCADE'
        // );

        // $this->addForeignKey(
        //     'fk-order-assigned_logist',
        //     '{{%order}}',
        //     'assigned_logist',
        //     '{{%user}}',
        //     'id',
        //     'SET NULL'
        // );
    }

    public function safeDown()
    {
        // $this->dropForeignKey('fk-order-assigned_logist', '{{%order}}');
        // $this->dropForeignKey('fk-order-created_by', '{{%order}}');
        $this->dropTable('{{%order}}');
    }
}
