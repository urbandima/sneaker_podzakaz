<?php

use yii\db\Migration;

class m260427_300000_create_missing_finance_feedback_tables extends Migration
{
    public function safeUp()
    {
        if (!$this->db->getTableSchema('payment')) {
            $this->createTable('payment', [
                'id'             => $this->primaryKey(),
                'order_id'       => $this->integer()->null(),
                'customer_id'    => $this->integer()->null(),
                'amount'         => $this->decimal(12, 2)->notNull(),
                'amount_original'=> $this->decimal(12, 2)->null(),
                'currency'       => $this->string(3)->null()->defaultValue('BYN'),
                'payment_method' => $this->string(50)->null(),
                'status'         => $this->string(50)->notNull()->defaultValue('pending'),
                'confirmed_by'   => $this->integer()->null(),
                'confirmed_at'   => $this->dateTime()->null(),
                'bank_reference' => $this->string(100)->null(),
                'bank_details'   => $this->string(255)->null(),
                'description'    => $this->text()->null(),
                'created_at'     => $this->integer()->notNull()->defaultExpression('UNIX_TIMESTAMP()'),
            ]);
            $this->createIndex('idx_payment_order',    'payment', 'order_id');
            $this->createIndex('idx_payment_customer', 'payment', 'customer_id');
            $this->createIndex('idx_payment_status',   'payment', 'status');
        }

        if (!$this->db->getTableSchema('expense')) {
            $this->createTable('expense', [
                'id'                => $this->primaryKey(),
                'category'          => $this->string(50)->notNull(),
                'order_id'          => $this->integer()->null(),
                'supplier_id'       => $this->integer()->null(),
                'amount'            => $this->decimal(12, 2)->notNull(),
                'currency'          => $this->string(3)->null()->defaultValue('BYN'),
                'amount_original'   => $this->decimal(12, 2)->null(),
                'currency_original' => $this->string(3)->null(),
                'exchange_rate'     => $this->decimal(10, 4)->null(),
                'description'       => $this->text()->null(),
                'document_number'   => $this->string(100)->null(),
                'created_by'        => $this->integer()->null(),
                'created_at'        => $this->integer()->notNull()->defaultExpression('UNIX_TIMESTAMP()'),
            ]);
            $this->createIndex('idx_expense_cat',   'expense', 'category');
            $this->createIndex('idx_expense_order', 'expense', 'order_id');
        }

        if (!$this->db->getTableSchema('feedback')) {
            $this->createTable('feedback', [
                'id'             => $this->primaryKey(),
                'customer_id'    => $this->integer()->null(),
                'customer_name'  => $this->string(200)->null(),
                'customer_email' => $this->string(200)->null(),
                'rating'         => $this->integer()->null(),
                'message'        => $this->text()->notNull(),
                'is_read'        => $this->tinyInteger(1)->notNull()->defaultValue(0),
                'status'         => $this->string(20)->notNull()->defaultValue('new'),
                'reply_text'     => $this->text()->null(),
                'replied_at'     => $this->integer()->null(),
                'created_at'     => $this->integer()->notNull()->defaultExpression('UNIX_TIMESTAMP()'),
            ]);
            $this->createIndex('idx_feedback_status', 'feedback', 'status');
        }
    }

    public function safeDown()
    {
        $this->dropTableIfExists('feedback');
        $this->dropTableIfExists('expense');
        $this->dropTableIfExists('payment');
    }
}
