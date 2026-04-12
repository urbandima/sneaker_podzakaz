<?php

use yii\db\Migration;

/**
 * Создает таблицу import_log для логирования операций импорта
 */
class m260411_160000_create_import_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%import_log}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->comment('ID задачи импорта'),
            'source_id' => $this->integer()->comment('ID источника'),
            'level' => $this->string(20)->defaultValue('info')->comment('Уровень лога: info, warning, error'),
            'message' => $this->text()->comment('Сообщение'),
            'context' => $this->text()->comment('Контекст (JSON)'),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-import_log-task_id', '{{%import_log}}', 'task_id');
        $this->createIndex('idx-import_log-source_id', '{{%import_log}}', 'source_id');
        $this->createIndex('idx-import_log-level', '{{%import_log}}', 'level');
        $this->createIndex('idx-import_log-created_at', '{{%import_log}}', 'created_at');

        $this->addForeignKey('fk-import_log-task_id', '{{%import_log}}', 'task_id', '{{%import_task}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-import_log-source_id', '{{%import_log}}', 'source_id', '{{%import_source}}', 'id', 'SET NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-import_log-source_id', '{{%import_log}}');
        $this->dropForeignKey('fk-import_log-task_id', '{{%import_log}}');
        $this->dropTable('{{%import_log}}');
    }
}
