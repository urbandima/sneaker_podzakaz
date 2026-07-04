<?php

use yii\db\Migration;

/**
 * Creates amocrm_field_mapping table for storing field mapping configuration.
 * Also ensures order table has the amocrm columns (idempotent — skips if exist).
 */
class m260426_170000_amocrm_field_mapping extends Migration
{
    public function safeUp()
    {
        // Field mapping table
        $this->createTable('amocrm_field_mapping', [
            'id'               => $this->primaryKey(),
            'entity_type'      => "ENUM('lead','contact','company') NOT NULL DEFAULT 'lead'",
            'local_field'      => $this->string(64)->notNull(),
            'amocrm_field_id'  => $this->integer()->notNull(),
            'amocrm_field_name'=> $this->string(255)->null(),
            'direction'        => "ENUM('to_amocrm','from_amocrm','both') NOT NULL DEFAULT 'both'",
            'created_at'       => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx_afm_entity_local', 'amocrm_field_mapping', ['entity_type', 'local_field']);
        $this->createIndex('idx_afm_amo_field', 'amocrm_field_mapping', 'amocrm_field_id');

        // Ensure order table has amocrm columns
        $schema = $this->db->getTableSchema('order');
        $cols   = $schema ? $schema->columnNames : [];

        if (!in_array('amocrm_lead_id', $cols)) {
            $this->addColumn('order', 'amocrm_lead_id', $this->integer()->null()->after('source_id'));
        }
        if (!in_array('amocrm_last_sync_at', $cols)) {
            $this->addColumn('order', 'amocrm_last_sync_at', $this->integer()->null()->after('amocrm_lead_id'));
        }
        if (!in_array('amocrm_source', $cols)) {
            $this->addColumn('order', 'amocrm_source', $this->string(50)->null()->after('amocrm_last_sync_at'));
        }

        // Index for lead lookups
        $indexNames = array_keys($this->db->getSchema()->getTableIndexes('order', true));
        if (!in_array('idx_order_amocrm_lead', $indexNames)) {
            try {
                $this->createIndex('idx_order_amocrm_lead', 'order', 'amocrm_lead_id');
            } catch (\Exception $e) {}
        }
    }

    public function safeDown()
    {
        $this->dropTable('amocrm_field_mapping');
        // Leave order columns — they may have data
    }
}
