<?php

use yii\db\Migration;

class m260516_120000_create_redirect_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%redirect}}', [
            'id'          => $this->primaryKey(),
            'from_url'    => $this->string(500)->notNull()->unique(),
            'to_url'      => $this->string(500)->notNull(),
            'type'        => $this->smallInteger()->notNull()->defaultValue(301)->comment('301, 302, 404'),
            'is_active'   => $this->boolean()->notNull()->defaultValue(true),
            'hit_count'   => $this->integer()->notNull()->defaultValue(0),
            'last_hit_at' => $this->string(20)->null(),
            'created_at'  => $this->integer()->notNull(),
            'updated_at'  => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_redirect_is_active', '{{%redirect}}', 'is_active');
    }

    public function safeDown()
    {
        $this->dropTable('{{%redirect}}');
    }
}
