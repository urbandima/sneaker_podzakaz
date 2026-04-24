<?php

use yii\db\Migration;

/**
 * Создание таблицы настроек приложения (key-value store по секциям)
 */
class m260413_120000_create_app_settings_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%app_setting}}', [
            'id' => $this->primaryKey(),
            'section' => $this->string(100)->notNull(),
            'key' => $this->string(255)->notNull(),
            'value' => $this->mediumText()->null(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq-app_setting-section-key', '{{%app_setting}}', ['section', 'key'], true);
        echo "✓ Создана таблица app_setting\n";
    }

    public function safeDown()
    {
        $this->dropTable('{{%app_setting}}');
    }
}
