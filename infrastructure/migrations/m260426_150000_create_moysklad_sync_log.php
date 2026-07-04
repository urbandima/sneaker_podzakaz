<?php

use yii\db\Migration;

class m260426_150000_create_moysklad_sync_log extends Migration
{
    public function safeUp()
    {
        $this->createTable('moysklad_sync_log', [
            'id'         => $this->primaryKey(),
            'order_id'   => $this->integer()->notNull(),
            'success'    => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'message'    => $this->text()->null(),
            'ms_id'      => $this->string(100)->null(),
            'user_id'    => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_msl_order', 'moysklad_sync_log', 'order_id');
        $this->createIndex('idx_msl_created', 'moysklad_sync_log', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('moysklad_sync_log');
    }
}
