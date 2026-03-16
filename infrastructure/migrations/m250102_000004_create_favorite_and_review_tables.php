<?php

use yii\db\Migration;

/**
 * Создание таблиц избранного и отзывов
 */
class m250102_000004_create_favorite_and_review_tables extends Migration
{
    public function safeUp()
    {
        // Таблица избранного
        $this->createTable('{{%product_favorite}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'session_id' => $this->string(255),
            'product_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        $this->addForeignKey(
            'fk-favorite-user_id',
            '{{%product_favorite}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-favorite-product_id',
            '{{%product_favorite}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
        
        $this->createIndex('idx-favorite-user_id', '{{%product_favorite}}', 'user_id');
        $this->createIndex('idx-favorite-session_id', '{{%product_favorite}}', 'session_id');
        $this->createIndex('idx-favorite-product_id', '{{%product_favorite}}', 'product_id');
        
        // Таблица отзывов
        $this->createTable('{{%product_review}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'user_id' => $this->integer(),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(255),
            'rating' => $this->integer()->notNull(), // 1-5
            'comment' => $this->text(),
            'is_verified' => $this->boolean()->defaultValue(0),
            'is_approved' => $this->boolean()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        $this->addForeignKey(
            'fk-review-product_id',
            '{{%product_review}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
        
        $this->addForeignKey(
            'fk-review-user_id',
            '{{%product_review}}',
            'user_id',
            '{{%user}}',
            'id',
            'SET NULL'
        );
        
        $this->createIndex('idx-review-product_id', '{{%product_review}}', 'product_id');
        $this->createIndex('idx-review-rating', '{{%product_review}}', 'rating');
        $this->createIndex('idx-review-is_approved', '{{%product_review}}', 'is_approved');
        
        echo "✓ Созданы таблицы избранного и отзывов\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-review-user_id', '{{%product_review}}');
        $this->dropForeignKey('fk-review-product_id', '{{%product_review}}');
        $this->dropTable('{{%product_review}}');
        
        $this->dropForeignKey('fk-favorite-product_id', '{{%product_favorite}}');
        $this->dropForeignKey('fk-favorite-user_id', '{{%product_favorite}}');
        $this->dropTable('{{%product_favorite}}');
    }
}
