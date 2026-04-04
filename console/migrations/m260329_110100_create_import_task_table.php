<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%import_task}}`.
 */
class m260329_110100_create_import_task_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%import_task}}', [
            'id' => $this->primaryKey(),
            'source_id' => $this->integer()->notNull()->comment('ID источника'),
            'status' => $this->string()->notNull()->defaultValue('pending')->comment('Статус: pending, running, completed, failed'),
            'started_at' => $this->integer()->comment('Время начала'),
            'completed_at' => $this->integer()->comment('Время завершения'),
            'imported_count' => $this->integer()->defaultValue(0)->comment('Количество импортированных'),
            'updated_count' => $this->integer()->defaultValue(0)->comment('Количество обновленных'),
            'error_count' => $this->integer()->defaultValue(0)->comment('Количество ошибок'),
            'error_message' => $this->text()->comment('Сообщение об ошибке'),
            'log_file' => $this->string()->comment('Путь к файлу лога'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Внешний ключ на import_source
        $this->addForeignKey(
            'fk-import_task-source_id',
            '{{%import_task}}',
            'source_id',
            '{{%import_source}}',
            'id',
            'CASCADE'
        );

        $this->createIndex(
            'idx-import_task-source_id',
            '{{%import_task}}',
            'source_id'
        );

        $this->createIndex(
            'idx-import_task-status',
            '{{%import_task}}',
            'status'
        );

        $this->createIndex(
            'idx-import_task-created_at',
            '{{%import_task}}',
            'created_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-import_task-source_id',
            '{{%import_task}}'
        );

        $this->dropTable('{{%import_task}}');
    }
}
