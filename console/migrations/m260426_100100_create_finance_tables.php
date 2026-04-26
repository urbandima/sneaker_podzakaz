<?php

use yii\db\Migration;

class m260426_100100_create_finance_tables extends Migration
{
    public function safeUp()
    {
        // ── payment ──────────────────────────────────────────────────────────
        if ($this->db->schema->getTableSchema('payment') === null) {
            $this->createTable('payment', [
                'id'             => $this->primaryKey(),
                'order_id'       => $this->integer()->null(),
                'customer_id'    => $this->integer()->null(),
                'amount'         => $this->decimal(12, 2)->notNull()->defaultValue(0),
                'currency'       => $this->char(3)->notNull()->defaultValue('BYN'),
                'payment_method' => $this->string(50)->null(),
                'status'         => $this->string(30)->notNull()->defaultValue('pending'),
                'bank_reference' => $this->string(100)->null(),
                'description'    => $this->text()->null(),
                'confirmed_by'   => $this->integer()->null(),
                'confirmed_at'   => $this->dateTime()->null(),
                'created_at'     => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at'     => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
            ]);
            $this->createIndex('idx_payment_order',    'payment', 'order_id');
            $this->createIndex('idx_payment_customer', 'payment', 'customer_id');
            $this->createIndex('idx_payment_status',   'payment', 'status');
        }

        // ── expense ───────────────────────────────────────────────────────────
        if ($this->db->schema->getTableSchema('expense') === null) {
            $this->createTable('expense', [
                'id'                => $this->primaryKey(),
                'category'          => $this->string(50)->notNull()->defaultValue('other'),
                'order_id'          => $this->integer()->null(),
                'supplier_id'       => $this->integer()->null(),
                'amount'            => $this->decimal(12, 2)->notNull()->defaultValue(0),
                'currency'          => $this->char(3)->notNull()->defaultValue('BYN'),
                'amount_original'   => $this->decimal(12, 2)->null(),
                'currency_original' => $this->char(3)->null(),
                'exchange_rate'     => $this->decimal(10, 4)->null(),
                'description'       => $this->text()->null(),
                'document_number'   => $this->string(100)->null(),
                'created_by'        => $this->integer()->null(),
                'created_at'        => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at'        => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
            ]);
            $this->createIndex('idx_expense_category',   'expense', 'category');
            $this->createIndex('idx_expense_order',      'expense', 'order_id');
            $this->createIndex('idx_expense_created_at', 'expense', 'created_at');
        }
    }

    public function safeDown()
    {
        $this->dropTable('expense');
        $this->dropTable('payment');
    }
}
