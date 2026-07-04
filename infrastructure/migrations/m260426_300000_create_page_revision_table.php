<?php

use yii\db\Migration;

/**
 * Creates page_revision table for version history of static pages.
 */
class m260426_300000_create_page_revision_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%page_revision}}') !== null) {
            return true;
        }

        $this->createTable('{{%page_revision}}', [
            'id'       => $this->primaryKey(),
            'page_id'  => $this->integer()->notNull(),
            'content'  => $this->mediumText()->null(),
            'title'    => $this->string(512)->null(),
            'saved_by' => $this->integer()->null(),
            'saved_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_page_revision_page_saved', '{{%page_revision}}', ['page_id', 'saved_at']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%page_revision}}');
    }
}
