<?php

/**
 * Миграция: Модерация отзывов (статусы)
 * 
 * Рекомендация #26: Product Reviews Moderation
 */
class m260330_224000_add_moderation_to_reviews extends \yii\db\Migration
{
    public function safeUp()
    {
        // Добавляем статус
        $this->addColumn('{{%product_review}}', 'status', $this->string(20)->notNull()->defaultValue('pending'));
        $this->addColumn('{{%product_review}}', 'moderated_by', $this->integer()->null());
        $this->addColumn('{{%product_review}}', 'moderated_at', $this->integer()->null());
        $this->addColumn('{{%product_review}}', 'moderation_note', $this->text()->null());
        
        // Спам-оценка (0-100)
        $this->addColumn('{{%product_review}}', 'spam_score', $this->integer()->null());
        $this->addColumn('{{%product_review}}', 'sentiment_score', $this->decimal(4, 3)->null());
        
        // Индексы
        $this->createIndex('idx-product_review-status', '{{%product_review}}', 'status');
        $this->createIndex('idx-product_review-spam', '{{%product_review}}', ['status', 'spam_score']);
        
        echo "Модерация отзывов добавлена\n";
    }
    
    public function safeDown()
    {
        $this->dropColumn('{{%product_review}}', 'status');
        $this->dropColumn('{{%product_review}}', 'moderated_by');
        $this->dropColumn('{{%product_review}}', 'moderated_at');
        $this->dropColumn('{{%product_review}}', 'moderation_note');
        $this->dropColumn('{{%product_review}}', 'spam_score');
        $this->dropColumn('{{%product_review}}', 'sentiment_score');
    }
}
