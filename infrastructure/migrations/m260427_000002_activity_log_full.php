<?php

use yii\db\Migration;

/**
 * Full activity_log table with all required columns.
 * Creates fresh if not exists; adds missing columns if already exists.
 */
class m260427_000002_activity_log_full extends Migration
{
    public function safeUp()
    {
        $tableSchema = Yii::$app->db->getTableSchema('activity_log');

        if ($tableSchema === null) {
            // Table doesn't exist — create it
            $this->execute("
                CREATE TABLE `activity_log` (
                  `id`           BIGINT          NOT NULL AUTO_INCREMENT,
                  `user_id`      INT             NULL,
                  `user_name`    VARCHAR(255)    NULL,
                  `user_role`    VARCHAR(64)     NULL,
                  `action`       VARCHAR(64)     NOT NULL,
                  `target_type`  VARCHAR(64)     NOT NULL,
                  `target_id`    BIGINT          NULL,
                  `target_label` VARCHAR(255)    NULL,
                  `changes`      JSON            NULL,
                  `source`       ENUM('web','api','cli','cron','webhook','system') NOT NULL DEFAULT 'web',
                  `ip`           VARCHAR(45)     NULL,
                  `user_agent`   VARCHAR(255)    NULL,
                  `created_at`   INT             NOT NULL,
                  PRIMARY KEY (`id`),
                  INDEX `idx_al_user`    (`user_id`,    `created_at`),
                  INDEX `idx_al_target`  (`target_type`,`target_id`, `created_at`),
                  INDEX `idx_al_action`  (`action`),
                  INDEX `idx_al_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return;
        }

        // Table exists — ensure all required columns are present
        $columns = array_keys($tableSchema->columns);

        $required = [
            'user_id'      => 'INT NULL AFTER `id`',
            'user_name'    => 'VARCHAR(255) NULL',
            'user_role'    => 'VARCHAR(64)  NULL',
            'action'       => 'VARCHAR(64)  NOT NULL',
            'target_type'  => 'VARCHAR(64)  NOT NULL',
            'target_id'    => 'BIGINT       NULL',
            'target_label' => 'VARCHAR(255) NULL',
            'changes'      => 'JSON         NULL',
            'source'       => "ENUM('web','api','cli','cron','webhook','system') NOT NULL DEFAULT 'web'",
            'ip'           => 'VARCHAR(45)  NULL',
            'user_agent'   => 'VARCHAR(255) NULL',
            'created_at'   => 'INT          NOT NULL',
        ];

        foreach ($required as $col => $definition) {
            if (!in_array($col, $columns, true)) {
                $this->addColumn('activity_log', $col, $definition);
            }
        }

        // Ensure indexes exist (ignore errors if already present)
        try { $this->createIndex('idx_al_user',    'activity_log', ['user_id',    'created_at']); } catch (\Exception $e) {}
        try { $this->createIndex('idx_al_target',  'activity_log', ['target_type','target_id', 'created_at']); } catch (\Exception $e) {}
        try { $this->createIndex('idx_al_action',  'activity_log', 'action'); } catch (\Exception $e) {}
        try { $this->createIndex('idx_al_created', 'activity_log', 'created_at'); } catch (\Exception $e) {}
    }

    public function safeDown()
    {
        $this->dropTable('activity_log');
    }
}
