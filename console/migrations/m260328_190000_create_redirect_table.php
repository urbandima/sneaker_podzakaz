<?php

/**
 * Миграция для создания таблицы редиректов
 */
class m260328_190000_create_redirect_table extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%redirect}}', [
            'id' => $this->primaryKey(),
            'from_url' => $this->string(500)->notNull(),
            'to_url' => $this->string(500)->notNull(),
            'type' => $this->smallInteger()->defaultValue(301),
            'is_active' => $this->boolean()->defaultValue(true),
            'hit_count' => $this->integer()->defaultValue(0),
            'last_hit_at' => $this->dateTime()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_redirect_from_url', '{{%redirect}}', 'from_url', true);
        $this->createIndex('idx_redirect_active', '{{%redirect}}', ['from_url', 'is_active']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%redirect}}');
    }
}
