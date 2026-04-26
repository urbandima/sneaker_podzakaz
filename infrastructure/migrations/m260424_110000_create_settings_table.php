<?php

use yii\db\Migration;

class m260424_110000_create_settings_table extends Migration
{
    public function safeUp()
    {
        $tables = $this->db->createCommand('SHOW TABLES LIKE "settings"')->queryAll();
        if (empty($tables)) {
            $this->createTable('{{%settings}}', [
                'id'         => $this->primaryKey(),
                'section'    => $this->string(64)->notNull(),
                'key'        => $this->string(128)->notNull(),
                'value'      => $this->text(),
                'updated_at' => $this->integer(),
            ]);
            $this->createIndex('ux_settings_section_key', '{{%settings}}', ['section', 'key'], true);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%settings}}');
    }
}
