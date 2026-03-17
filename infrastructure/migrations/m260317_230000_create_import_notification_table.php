<?php

use yii\db\Migration;

/**
 * Создание таблицы уведомлений об импорте
 */
class m260317_230000_create_import_notification_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%import_notification}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull()->comment('ID задачи импорта'),
            'type' => $this->string(20)->notNull()->comment('Тип: success, error, warning, info'),
            'title' => $this->string(255)->notNull()->comment('Заголовок'),
            'message' => $this->text()->notNull()->comment('Сообщение'),
            'is_read' => $this->boolean()->defaultValue(false)->comment('Прочитано'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx-import_notification-task_id', '{{%import_notification}}', 'task_id');
        $this->createIndex('idx-import_notification-is_read', '{{%import_notification}}', 'is_read');
        $this->createIndex('idx-import_notification-created_at', '{{%import_notification}}', 'created_at');

        $this->addForeignKey(
            'fk-import_notification-task_id',
            '{{%import_notification}}',
            'task_id',
            '{{%import_task}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-import_notification-task_id', '{{%import_notification}}');
        $this->dropTable('{{%import_notification}}');
    }
}
