<?php

use yii\db\Migration;

/**
 * Создание таблицы корзины
 */
class m250102_000003_create_cart_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%cart}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'session_id' => $this->string(255),
            'product_id' => $this->integer()->notNull(),
            'quantity' => $this->integer()->notNull()->defaultValue(1),
            'size' => $this->string(20),
            'color' => $this->string(50),
            'price' => $this->decimal(10, 2)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        // Foreign keys
        $this->addForeignKey(
            'fk-cart-user_id',
            '{{%cart}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-cart-product_id',
            '{{%cart}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
        
        // Индексы
        $this->createIndex('idx-cart-user_id', '{{%cart}}', 'user_id');
        $this->createIndex('idx-cart-session_id', '{{%cart}}', 'session_id');
        $this->createIndex('idx-cart-product_id', '{{%cart}}', 'product_id');
        
        echo "✓ Создана таблица корзины\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-cart-product_id', '{{%cart}}');
        $this->dropForeignKey('fk-cart-user_id', '{{%cart}}');
        $this->dropTable('{{%cart}}');
    }
}
