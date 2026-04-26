<?php

use yii\db\Migration;

/**
 * Creates buyout, buyout_order_link, buyout_history tables.
 * See docs/PROCUREMENT_BUYOUT_PLAN.md for full spec.
 * DRAFT — not yet run in production.
 */
class m260426_200200_create_buyout_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%buyout}}', [
            'id'            => $this->primaryKey(),
            'number'        => $this->string(32)->notNull()->unique(),
            'source'        => $this->string(64)->notNull()->defaultValue('manual'),
            'source_url'    => $this->text()->null(),
            'status'        => $this->string(32)->notNull()->defaultValue('draft'),
            'total_cny'     => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'total_byn'     => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'rate_snapshot' => $this->decimal(10, 4)->null(),
            'markup_pct'    => $this->decimal(5, 2)->null(),
            'weight_kg'     => $this->decimal(8, 3)->null(),
            'delivery_cost' => $this->decimal(10, 2)->null()->defaultValue(0),
            'comment'       => $this->text()->null(),
            'created_by'    => $this->integer()->null(),
            'created_at'    => $this->integer()->notNull(),
            'updated_at'    => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_buyout_status',  '{{%buyout}}', 'status');
        $this->createIndex('idx_buyout_source',  '{{%buyout}}', 'source');
        $this->createIndex('idx_buyout_created', '{{%buyout}}', 'created_at');

        $this->createTable('{{%buyout_order_link}}', [
            'id'        => $this->primaryKey(),
            'buyout_id' => $this->integer()->notNull(),
            'order_id'  => $this->integer()->notNull(),
            'linked_at' => $this->integer()->notNull(),
            'linked_by' => $this->integer()->null(),
        ]);

        $this->createIndex('idx_bol_buyout', '{{%buyout_order_link}}', 'buyout_id');
        $this->createIndex('idx_bol_order',  '{{%buyout_order_link}}', 'order_id');
        $this->addForeignKey('fk_bol_buyout', '{{%buyout_order_link}}', 'buyout_id', '{{%buyout}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%buyout_history}}', [
            'id'          => $this->primaryKey(),
            'buyout_id'   => $this->integer()->notNull(),
            'from_status' => $this->string(32)->null(),
            'to_status'   => $this->string(32)->notNull(),
            'comment'     => $this->text()->null(),
            'changed_by'  => $this->integer()->null(),
            'changed_at'  => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_bh_buyout', '{{%buyout_history}}', 'buyout_id');
        $this->addForeignKey('fk_bh_buyout', '{{%buyout_history}}', 'buyout_id', '{{%buyout}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%buyout_history}}');
        $this->dropTable('{{%buyout_order_link}}');
        $this->dropTable('{{%buyout}}');
    }
}
