<?php

use yii\db\Migration;

/**
 * Добавляет отсутствующие поля в таблицу return_request
 */
class m260412_180000_add_missing_return_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%return_request}}', 'refund_method', $this->string(255)->null());
        $this->addColumn('{{%return_request}}', 'refund_transaction', $this->string(255)->null());
        $this->addColumn('{{%return_request}}', 'pickup_address', $this->string(500)->null());
        $this->addColumn('{{%return_request}}', 'pickup_date', $this->date()->null());
        $this->addColumn('{{%return_request}}', 'tracking_number', $this->string(255)->null());
        $this->addColumn('{{%return_request}}', 'completed_at', $this->dateTime()->null());
        $this->addColumn('{{%return_request}}', 'comment', $this->text()->null());
        $this->addColumn('{{%return_request}}', 'admin_comment', $this->text()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%return_request}}', 'refund_method');
        $this->dropColumn('{{%return_request}}', 'refund_transaction');
        $this->dropColumn('{{%return_request}}', 'pickup_address');
        $this->dropColumn('{{%return_request}}', 'pickup_date');
        $this->dropColumn('{{%return_request}}', 'tracking_number');
        $this->dropColumn('{{%return_request}}', 'completed_at');
        $this->dropColumn('{{%return_request}}', 'comment');
        $this->dropColumn('{{%return_request}}', 'admin_comment');
    }
}
