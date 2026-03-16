<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%size_feedback}}`.
 * Таблица для хранения отзывов о размерах товаров
 */
class m250102_150000_create_size_feedback_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%size_feedback}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull()->comment('ID товара'),
            'user_id' => $this->integer()->null()->comment('ID пользователя (если авторизован)'),
            'session_id' => $this->string(100)->null()->comment('ID сессии (для неавторизованных)'),
            'size' => $this->string(10)->notNull()->comment('Размер, который был куплен'),
            'fit_type' => "ENUM('too_small', 'small', 'perfect', 'large', 'too_large') NOT NULL COMMENT 'Как сел размер'",
            'usual_size' => $this->string(10)->null()->comment('Обычный размер покупателя'),
            'comment' => $this->text()->null()->comment('Дополнительный комментарий'),
            'is_verified' => $this->boolean()->defaultValue(false)->comment('Подтверждена ли покупка'),
            'ip_address' => $this->string(45)->null()->comment('IP адрес'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Индексы
        $this->createIndex(
            'idx-size_feedback-product_id',
            '{{%size_feedback}}',
            'product_id'
        );

        $this->createIndex(
            'idx-size_feedback-user_id',
            '{{%size_feedback}}',
            'user_id'
        );

        $this->createIndex(
            'idx-size_feedback-session_id',
            '{{%size_feedback}}',
            'session_id'
        );

        // Внешний ключ на product
        $this->addForeignKey(
            'fk-size_feedback-product_id',
            '{{%size_feedback}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );

        // Внешний ключ на user (если таблица существует)
        if ($this->db->schema->getTableSchema('{{%user}}') !== null) {
            $this->addForeignKey(
                'fk-size_feedback-user_id',
                '{{%size_feedback}}',
                'user_id',
                '{{%user}}',
                'id',
                'CASCADE'
            );
        }

        // Добавляем тестовые данные для демонстрации
        $this->batchInsert('{{%size_feedback}}', [
            'product_id', 'size', 'fit_type', 'usual_size', 'created_at'
        ], [
            [1, '42', 'perfect', '42', date('Y-m-d H:i:s', strtotime('-5 days'))],
            [1, '42', 'perfect', '42', date('Y-m-d H:i:s', strtotime('-3 days'))],
            [1, '42', 'small', '42', date('Y-m-d H:i:s', strtotime('-2 days'))],
            [1, '43', 'perfect', '42', date('Y-m-d H:i:s', strtotime('-1 day'))],
            [1, '41', 'large', '42', date('Y-m-d H:i:s')],
            [1, '42', 'perfect', '41', date('Y-m-d H:i:s', strtotime('-4 days'))],
            [1, '42', 'perfect', '43', date('Y-m-d H:i:s', strtotime('-6 days'))],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем внешние ключи
        $this->dropForeignKey('fk-size_feedback-product_id', '{{%size_feedback}}');
        
        if ($this->db->schema->getTableSchema('{{%user}}') !== null) {
            $this->dropForeignKey('fk-size_feedback-user_id', '{{%size_feedback}}');
        }

        // Удаляем индексы
        $this->dropIndex('idx-size_feedback-product_id', '{{%size_feedback}}');
        $this->dropIndex('idx-size_feedback-user_id', '{{%size_feedback}}');
        $this->dropIndex('idx-size_feedback-session_id', '{{%size_feedback}}');

        // Удаляем таблицу
        $this->dropTable('{{%size_feedback}}');
    }
}
