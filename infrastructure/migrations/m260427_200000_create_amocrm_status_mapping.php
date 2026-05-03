<?php

use yii\db\Migration;

class m260427_200000_create_amocrm_status_mapping extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%amocrm_status_mapping}}', true) === null) {
            $this->createTable('{{%amocrm_status_mapping}}', [
                'id'                  => $this->primaryKey(),
                'our_status'          => $this->string(64)->notNull(),
                'our_track'           => "ENUM('payment','logistics','delivery','order') NOT NULL DEFAULT 'order'",
                'amocrm_pipeline_id'  => $this->integer()->notNull(),
                'amocrm_status_id'    => $this->integer()->notNull(),
                'amocrm_status_name'  => $this->string(255)->null(),
                'direction'           => "ENUM('to_amocrm','from_amocrm','both') NOT NULL DEFAULT 'both'",
                'created_at'          => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' : null);
        }

        // UNIQUE on (track, our_status): one row per logical key — also serves as the
        // lookup index for toAmocrm() queries.
        if (!$this->indexExists('amocrm_status_mapping', 'uniq_status_map_our')) {
            $this->createIndex(
                'uniq_status_map_our',
                '{{%amocrm_status_mapping}}',
                ['our_track', 'our_status'],
                true
            );
        }

        // UNIQUE on (pipeline, status, direction): allows a pipeline+status pair to be
        // mapped separately for to_amocrm / from_amocrm without colliding. Also serves
        // as the index for fromAmocrm() lookups.
        if (!$this->indexExists('amocrm_status_mapping', 'uniq_status_map_amo')) {
            $this->createIndex(
                'uniq_status_map_amo',
                '{{%amocrm_status_mapping}}',
                ['amocrm_pipeline_id', 'amocrm_status_id', 'direction'],
                true
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%amocrm_status_mapping}}', true) !== null) {
            $this->dropTable('{{%amocrm_status_mapping}}');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if ($this->db->getTableSchema($table, true) === null) {
            return false;
        }
        $row = $this->db->createCommand(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = :name",
            [':name' => $indexName]
        )->queryOne();
        return $row !== false;
    }
}
