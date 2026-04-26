<?php

use yii\db\Migration;

class m260426_100000_create_page_content_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%page_content}}') !== null) {
            return true;
        }

        $this->createTable('{{%page_content}}', [
            'id'         => $this->primaryKey(),
            'slug'       => $this->string(100)->notNull()->unique(),
            'title'      => $this->string(255)->notNull()->defaultValue(''),
            'content'    => $this->text()->null(),
            'is_active'  => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'updated_by' => $this->integer()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_page_content_slug', '{{%page_content}}', 'slug', true);
    }

    public function safeDown()
    {
        $this->dropTable('{{%page_content}}');
    }
}
