<?php

use yii\db\Migration;

/**
 * Adds FULLTEXT index on product table and creates search_log tracking table.
 */
class m260426_120000_create_search_indexes_and_log extends Migration
{
    public function safeUp()
    {
        // FULLTEXT index for product search fields
        // Using raw SQL because Yii's createIndex() doesn't support FULLTEXT
        $this->execute('ALTER TABLE `product` ADD FULLTEXT INDEX `idx_product_fulltext` (`name`, `description`, `brand_name`, `model_name`)');

        // Search log table
        $this->createTable('search_log', [
            'id'            => $this->primaryKey(),
            'query'         => $this->string(255)->notNull(),
            'results_count' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'user_id'       => $this->integer()->unsigned()->null(),
            'session_id'    => $this->string(64)->null(),
            'ip'            => $this->string(45)->null(),
            'created_at'    => $this->integer()->unsigned()->notNull(),
        ]);

        $this->createIndex('idx_search_log_query', 'search_log', 'query');
        $this->createIndex('idx_search_log_created', 'search_log', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('search_log');
        $this->execute('ALTER TABLE `product` DROP INDEX `idx_product_fulltext`');
    }
}
