<?php

use yii\db\Migration;

class m260424_310000_create_amocrm_tables extends Migration
{
    public function up()
    {
        $this->createTable('{{%amocrm_log}}', [
            'id'         => $this->primaryKey(),
            'direction'  => $this->string(10)->notNull()->comment('outgoing / incoming'),
            'event'      => $this->string(100)->notNull(),
            'status'     => $this->string(10)->notNull()->defaultValue('ok')->comment('ok / fail'),
            'order_id'   => $this->integer()->null(),
            'payload'    => $this->text()->null(),
            'response'   => $this->text()->null(),
            'response_ms' => $this->integer()->null()->comment('response time ms'),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_amocrm_log_created', '{{%amocrm_log}}', 'created_at');
        $this->createIndex('idx_amocrm_log_status',  '{{%amocrm_log}}', 'status');

        // Add AmoCRM lead ID + last sync timestamp to order table
        $this->addColumn('{{%order}}', 'amocrm_lead_id',    $this->integer()->null()->after('amocrm_deal_id'));
        $this->addColumn('{{%order}}', 'amocrm_last_sync_at', $this->integer()->null()->after('amocrm_lead_id'));
    }

    public function down()
    {
        $this->dropTable('{{%amocrm_log}}');
        $this->dropColumn('{{%order}}', 'amocrm_lead_id');
        $this->dropColumn('{{%order}}', 'amocrm_last_sync_at');
    }
}
