<?php

use yii\db\Migration;

/**
 * Creates receiving, receiving_item, receiving_expense, receiving_document tables.
 * See docs/PROCUREMENT_RECEIVING_PLAN.md for full spec.
 */
class m260426_200300_create_receiving_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%receiving}}', [
            'id'                     => $this->primaryKey(),
            'number'                 => $this->string(32)->notNull()->unique(),
            'supplier_id'            => $this->integer()->null(),
            'buyout_id'              => $this->integer()->null(),
            'receiving_date'         => $this->dateTime()->null(),
            'expected_date'          => $this->dateTime()->null(),
            'arrived_date'           => $this->dateTime()->null(),
            'accepted_date'          => $this->dateTime()->null(),
            'status'                 => $this->string(32)->notNull()->defaultValue('draft'),
            'total_items'            => $this->integer()->notNull()->defaultValue(0),
            'total_qty_expected'     => $this->integer()->notNull()->defaultValue(0),
            'total_qty_arrived'      => $this->integer()->notNull()->defaultValue(0),
            'total_qty_defected'     => $this->integer()->notNull()->defaultValue(0),
            'subtotal_byn'           => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'expenses_total_byn'     => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'total_with_expenses_byn'=> $this->decimal(12, 2)->notNull()->defaultValue(0),
            'receiver_user_id'       => $this->integer()->null(),
            'notes'                  => $this->text()->null(),
            'created_at'             => $this->integer()->notNull(),
            'updated_at'             => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_receiving_status',      '{{%receiving}}', 'status');
        $this->createIndex('idx_receiving_supplier',    '{{%receiving}}', 'supplier_id');
        $this->createIndex('idx_receiving_buyout',      '{{%receiving}}', 'buyout_id');
        $this->createIndex('idx_receiving_expected',    '{{%receiving}}', 'expected_date');
        $this->createIndex('idx_receiving_arrived',     '{{%receiving}}', 'arrived_date');
        $this->createIndex('idx_receiving_created',     '{{%receiving}}', 'created_at');

        $this->createTable('{{%receiving_item}}', [
            'id'                    => $this->primaryKey(),
            'receiving_id'          => $this->integer()->notNull(),
            'product_id'            => $this->integer()->notNull(),
            'size_id'               => $this->integer()->null(),
            'qty_expected'          => $this->integer()->notNull()->defaultValue(1),
            'qty_arrived'           => $this->integer()->notNull()->defaultValue(0),
            'qty_defected'          => $this->integer()->notNull()->defaultValue(0),
            'unit_cost_source'      => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'source_currency'       => $this->char(3)->notNull()->defaultValue('BYN'),
            'exchange_rate'         => $this->decimal(10, 4)->notNull()->defaultValue(1),
            'unit_cost_byn'         => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'allocated_expenses_byn'=> $this->decimal(10, 2)->notNull()->defaultValue(0),
            'final_cost_byn'        => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'notes'                 => $this->text()->null(),
        ]);

        $this->createIndex('idx_ri_receiving', '{{%receiving_item}}', 'receiving_id');
        $this->createIndex('idx_ri_product',   '{{%receiving_item}}', 'product_id');
        $this->createIndex('idx_ri_size',      '{{%receiving_item}}', 'size_id');

        $this->addForeignKey(
            'fk_ri_receiving', '{{%receiving_item}}', 'receiving_id',
            '{{%receiving}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->createTable('{{%receiving_expense}}', [
            'id'                  => $this->primaryKey(),
            'receiving_id'        => $this->integer()->notNull(),
            'type'                => $this->string(32)->notNull()->defaultValue('other'),
            'amount'              => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'currency'            => $this->char(3)->notNull()->defaultValue('BYN'),
            'exchange_rate'       => $this->decimal(10, 4)->notNull()->defaultValue(1),
            'amount_byn'          => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'distribution_method' => $this->string(16)->notNull()->defaultValue('equal'),
            'notes'               => $this->text()->null(),
        ]);

        $this->createIndex('idx_re_receiving', '{{%receiving_expense}}', 'receiving_id');
        $this->addForeignKey(
            'fk_re_receiving', '{{%receiving_expense}}', 'receiving_id',
            '{{%receiving}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->createTable('{{%receiving_document}}', [
            'id'            => $this->primaryKey(),
            'receiving_id'  => $this->integer()->notNull(),
            'type'          => $this->string(32)->notNull()->defaultValue('other'),
            'file_path'     => $this->string(512)->notNull(),
            'original_name' => $this->string(255)->notNull(),
            'mime_type'     => $this->string(128)->null(),
            'size_bytes'    => $this->integer()->null(),
            'uploaded_by'   => $this->integer()->null(),
            'uploaded_at'   => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_rd_receiving', '{{%receiving_document}}', 'receiving_id');
        $this->addForeignKey(
            'fk_rd_receiving', '{{%receiving_document}}', 'receiving_id',
            '{{%receiving}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->createTable('{{%receiving_history}}', [
            'id'          => $this->primaryKey(),
            'receiving_id'=> $this->integer()->notNull(),
            'from_status' => $this->string(32)->null(),
            'to_status'   => $this->string(32)->null(),
            'comment'     => $this->text()->null(),
            'changed_by'  => $this->integer()->null(),
            'changed_at'  => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_rh_receiving', '{{%receiving_history}}', 'receiving_id');
        $this->addForeignKey(
            'fk_rh_receiving', '{{%receiving_history}}', 'receiving_id',
            '{{%receiving}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%receiving_history}}');
        $this->dropTable('{{%receiving_document}}');
        $this->dropTable('{{%receiving_expense}}');
        $this->dropTable('{{%receiving_item}}');
        $this->dropTable('{{%receiving}}');
    }
}
