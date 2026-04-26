<?php

use yii\db\Migration;

class m260424_110000_create_settings_table extends Migration
{
    public function up()
    {
        $this->createTable('{{%settings}}', [
            'id'         => $this->primaryKey(),
            'section'    => $this->string(64)->notNull(),
            'key'        => $this->string(128)->notNull(),
            'value'      => $this->text(),
            'updated_at' => $this->integer(),
        ]);
        $this->createUniqueIndex('ux_settings_section_key', '{{%settings}}', ['section', 'key']);
    }

    public function down()
    {
        $this->dropTable('{{%settings}}');
    }
}
