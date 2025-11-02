<?php

use yii\db\Migration;

/**
 * Создание таблицы filter_history для истории фильтрации
 */
class m251101_202000_create_filter_history_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%filter_history}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->comment('ID пользователя'),
            'session_id' => $this->string(255)->comment('ID сессии'),
            'filter_params' => $this->text()->notNull()->comment('JSON параметры фильтров'),
            'results_count' => $this->integer()->notNull()->comment('Количество результатов'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-filter-history-user', '{{%filter_history}}', 'user_id');
        $this->createIndex('idx-filter-history-session', '{{%filter_history}}', 'session_id');
        $this->createIndex('idx-filter-history-created', '{{%filter_history}}', 'created_at');
        
        $this->addForeignKey(
            'fk-filter-history-user',
            '{{%filter_history}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        return true;
    }

    public function safeDown()
    {
        $this->dropTable('{{%filter_history}}');
        return true;
    }
}
