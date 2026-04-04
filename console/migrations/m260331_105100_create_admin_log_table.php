<?php

use yii\db\Migration;

/**
 * Создание таблицы логов операций администратора
 */
class m260331_105100_create_admin_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%admin_log}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'username' => $this->string(100)->notNull(),
            'action' => $this->string(50)->notNull(),
            'entity_type' => $this->string(50)->notNull(),
            'entity_id' => $this->integer(),
            'entity_name' => $this->string(255),
            'description' => $this->text(),
            'old_values' => $this->text(),
            'new_values' => $this->text(),
            'ip_address' => $this->string(45),
            'user_agent' => $this->string(500),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'idx-admin_log-user_id',
            '{{%admin_log}}',
            'user_id'
        );

        $this->createIndex(
            'idx-admin_log-action',
            '{{%admin_log}}',
            'action'
        );

        $this->createIndex(
            'idx-admin_log-entity',
            '{{%admin_log}}',
            ['entity_type', 'entity_id']
        );

        $this->createIndex(
            'idx-admin_log-created_at',
            '{{%admin_log}}',
            'created_at'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%admin_log}}');
    }
}
