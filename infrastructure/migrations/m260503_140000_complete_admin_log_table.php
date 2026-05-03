<?php

use yii\db\Migration;

/**
 * Complete admin_log table with all columns AdminLog AR/AdminLogService expect.
 *
 * The original m260331_085347_create_admin_log_table migration is a stub —
 * it only creates the `id` PK column. AdminLogService::log() then fails
 * silently because of its `try/catch (\Throwable)` defense, which means
 * audit-log writes are permanently lost. This migration is idempotent:
 * creates the table fresh if missing, or backfills missing columns + indexes
 * if a stub-shaped table is already present (CMP-79 / CMP-81).
 */
class m260503_140000_complete_admin_log_table extends Migration
{
    public function safeUp()
    {
        $tableSchema = Yii::$app->db->getTableSchema('admin_log', true);

        if ($tableSchema === null) {
            $this->execute("
                CREATE TABLE `admin_log` (
                  `id`           BIGINT       NOT NULL AUTO_INCREMENT,
                  `user_id`      INT          NULL,
                  `username`     VARCHAR(100) NULL,
                  `action`       VARCHAR(50)  NOT NULL,
                  `entity_type`  VARCHAR(50)  NOT NULL,
                  `entity_id`    INT          NULL,
                  `entity_name`  VARCHAR(255) NULL,
                  `description`  TEXT         NULL,
                  `old_values`   TEXT         NULL,
                  `new_values`   TEXT         NULL,
                  `ip_address`   VARCHAR(45)  NULL,
                  `user_agent`   VARCHAR(500) NULL,
                  `created_at`   INT          NOT NULL,
                  PRIMARY KEY (`id`),
                  INDEX `idx_admin_log_user`    (`user_id`,     `created_at`),
                  INDEX `idx_admin_log_entity`  (`entity_type`, `entity_id`, `created_at`),
                  INDEX `idx_admin_log_action`  (`action`),
                  INDEX `idx_admin_log_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return;
        }

        $columns = array_keys($tableSchema->columns);

        $required = [
            'user_id'     => 'INT          NULL',
            'username'    => 'VARCHAR(100) NULL',
            'action'      => 'VARCHAR(50)  NOT NULL',
            'entity_type' => 'VARCHAR(50)  NOT NULL',
            'entity_id'   => 'INT          NULL',
            'entity_name' => 'VARCHAR(255) NULL',
            'description' => 'TEXT         NULL',
            'old_values'  => 'TEXT         NULL',
            'new_values'  => 'TEXT         NULL',
            'ip_address'  => 'VARCHAR(45)  NULL',
            'user_agent'  => 'VARCHAR(500) NULL',
            'created_at'  => 'INT          NOT NULL',
        ];

        foreach ($required as $col => $definition) {
            if (!in_array($col, $columns, true)) {
                $this->addColumn('admin_log', $col, $definition);
            }
        }

        try { $this->createIndex('idx_admin_log_user',    'admin_log', ['user_id',     'created_at']); } catch (\Exception $e) {}
        try { $this->createIndex('idx_admin_log_entity',  'admin_log', ['entity_type', 'entity_id', 'created_at']); } catch (\Exception $e) {}
        try { $this->createIndex('idx_admin_log_action',  'admin_log', 'action'); } catch (\Exception $e) {}
        try { $this->createIndex('idx_admin_log_created', 'admin_log', 'created_at'); } catch (\Exception $e) {}
    }

    public function safeDown()
    {
        $this->dropTable('admin_log');
    }
}
