<?php

use yii\db\Migration;

/**
 * Создание таблицы отзывов product_review
 */
class m260330_130000_create_product_review_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%product_review}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'user_id' => $this->integer(),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(255),
            'rating' => $this->integer()->notNull(), // 1-5
            'content' => $this->text(),
            'is_verified' => $this->boolean()->defaultValue(0),
            'is_published' => $this->boolean()->defaultValue(0),
            'is_featured' => $this->boolean()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
        
        $this->createIndex('idx-review-product_id', '{{%product_review}}', 'product_id');
        $this->createIndex('idx-review-rating', '{{%product_review}}', 'rating');
        $this->createIndex('idx-review-is_published', '{{%product_review}}', 'is_published');
        
        echo "✓ Создана таблица product_review\n";
    }

    public function safeDown()
    {
        $this->dropTable('{{%product_review}}');
    }
}
