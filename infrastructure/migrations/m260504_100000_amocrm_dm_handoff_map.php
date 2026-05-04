<?php

use yii\db\Migration;

/**
 * Registers AmoCRM pipeline/status/custom-field IDs for the DM-Sales handoff contract (CMP-63).
 *
 * pipeline_id : 4453963  (СНИКЕРХЭД — единая воронка IG/FB)
 * dm.started  : 41258857 (Новый заказ)
 * dm.handed_off: 85588210 (Передано в выкуп)
 */
class m260504_100000_amocrm_dm_handoff_map extends Migration
{
    public function safeUp(): void
    {
        // ── 1. Extend our_track ENUM to include 'dm' ────────────────────────────
        $this->execute(
            "ALTER TABLE {{%amocrm_status_mapping}}
             MODIFY COLUMN `our_track`
             ENUM('payment','logistics','delivery','order','dm') NOT NULL DEFAULT 'order'"
        );

        // ── 2. Insert DM handoff status rows ────────────────────────────────────
        $this->batchInsert('{{%amocrm_status_mapping}}',
            ['our_status', 'our_track', 'amocrm_pipeline_id', 'amocrm_status_id', 'amocrm_status_name', 'direction'],
            [
                ['dm.started',    'dm', 4453963, 41258857, 'Новый заказ',       'to_amocrm'],
                ['dm.handed_off', 'dm', 4453963, 85588210, 'Передано в выкуп',  'to_amocrm'],
            ]
        );

        // ── 3. Create amocrm_custom_field_map ───────────────────────────────────
        $this->createTable('{{%amocrm_custom_field_map}}', [
            'id'         => $this->primaryKey(),
            'field_code' => $this->string(64)->notNull()->comment('Machine name (product_ref, size …)'),
            'field_id'   => $this->integer()->notNull()->comment('AmoCRM custom_field id'),
            'field_type' => "ENUM('text','select') NOT NULL DEFAULT 'text'",
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' : null);

        $this->createIndex('uniq_acfm_code',     '{{%amocrm_custom_field_map}}', 'field_code', true);
        $this->createIndex('uniq_acfm_field_id', '{{%amocrm_custom_field_map}}', 'field_id',   true);

        // ── 4. Create amocrm_custom_field_options ────────────────────────────────
        $this->createTable('{{%amocrm_custom_field_options}}', [
            'id'           => $this->primaryKey(),
            'field_code'   => $this->string(64)->notNull()->comment('FK → amocrm_custom_field_map.field_code'),
            'enum_code'    => $this->string(64)->notNull()->comment('Machine enum value (strict_date, yes …)'),
            'option_id'    => $this->integer()->notNull()->comment('AmoCRM enum option id'),
            'created_at'   => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' : null);

        $this->createIndex('uniq_acfo_code_enum', '{{%amocrm_custom_field_options}}', ['field_code', 'enum_code'], true);
        $this->createIndex('uniq_acfo_option_id', '{{%amocrm_custom_field_options}}', 'option_id', true);

        // ── 5. Populate custom fields ────────────────────────────────────────────
        $this->batchInsert('{{%amocrm_custom_field_map}}',
            ['field_code', 'field_id', 'field_type'],
            [
                ['product_ref',                       864515, 'text'],
                ['size',                              864517, 'text'],
                ['deadline',                          864519, 'text'],
                ['deadline_strictness',               864521, 'select'],
                ['prepay_ready',                      864523, 'select'],
                ['passport_by',                       864525, 'select'],
                ['attribution_source_self_reported',  864527, 'text'],
                ['attribution_source_referral',       864529, 'text'],
                ['disqualified_reason',               864531, 'select'],
                ['disqualified_note',                 864533, 'text'],
            ]
        );

        // ── 6. Populate select options ───────────────────────────────────────────
        $this->batchInsert('{{%amocrm_custom_field_options}}',
            ['field_code', 'enum_code', 'option_id'],
            [
                // deadline_strictness
                ['deadline_strictness', 'strict_date',     639451],
                ['deadline_strictness', 'target_window',   639453],
                ['deadline_strictness', 'flexible',        639455],
                // prepay_ready
                ['prepay_ready', 'yes',           639457],
                ['prepay_ready', 'no',            639459],
                ['prepay_ready', 'need_to_think', 639461],
                // passport_by
                ['passport_by', 'yes',           639463],
                ['passport_by', 'no',            639465],
                ['passport_by', 'other_country', 639467],
                // disqualified_reason
                ['disqualified_reason', 'no_prepay',       639469],
                ['disqualified_reason', 'no_by_passport',  639471],
                ['disqualified_reason', 'no_response',     639473],
                ['disqualified_reason', 'no_timing',       639475],
                ['disqualified_reason', 'price_objection', 639477],
                ['disqualified_reason', 'other',           639479],
            ]
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%amocrm_custom_field_options}}');
        $this->dropTable('{{%amocrm_custom_field_map}}');

        $this->delete('{{%amocrm_status_mapping}}', ['our_track' => 'dm']);

        $this->execute(
            "ALTER TABLE {{%amocrm_status_mapping}}
             MODIFY COLUMN `our_track`
             ENUM('payment','logistics','delivery','order') NOT NULL DEFAULT 'order'"
        );
    }
}
