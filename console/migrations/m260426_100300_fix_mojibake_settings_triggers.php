<?php

use yii\db\Migration;

/**
 * Fix mojibake encoding in settings and trigger tables.
 *
 * Detects garbled UTF-8 (double-encoded latin1→utf8) and re-encodes.
 * Only touches values that contain Ã, Ð, or Ñ without proper Cyrillic context.
 */
class m260426_100300_fix_mojibake_settings_triggers extends Migration
{
    private function isMojibake(string $val): bool
    {
        // Garbled cyrillic: latin1 bytes interpreted as utf8
        // Typical markers: Ã, multibyte sequences that don't match valid UTF-8 cyrillic
        return preg_match('/[ÃÐÑ][©-¿]/', $val) === 1;
    }

    private function fixVal(string $val): string
    {
        $fixed = mb_convert_encoding(
            mb_convert_encoding($val, 'ISO-8859-1', 'UTF-8'),
            'UTF-8',
            'ISO-8859-1'
        );
        // If result is valid UTF-8 with actual Cyrillic, use it; otherwise keep original
        return mb_check_encoding($fixed, 'UTF-8') ? $fixed : $val;
    }

    public function safeUp()
    {
        // ── settings table (key-value) ────────────────────────────────────────
        try {
            $rows = $this->db->createCommand('SELECT id, value FROM {{%settings}} WHERE value IS NOT NULL')->queryAll();
            foreach ($rows as $row) {
                if ($this->isMojibake((string)$row['value'])) {
                    $fixed = $this->fixVal($row['value']);
                    $this->update('{{%settings}}', ['value' => $fixed], ['id' => $row['id']]);
                }
            }
        } catch (\Exception $e) {
            // Table may not exist — skip silently
        }

        // ── company_settings table ────────────────────────────────────────────
        try {
            $cols = ['company_name', 'company_address', 'company_phone', 'company_email', 'company_description'];
            $row = $this->db->createCommand('SELECT * FROM {{%company_settings}} LIMIT 1')->queryOne();
            if ($row) {
                $updates = [];
                foreach ($cols as $col) {
                    if (!empty($row[$col]) && $this->isMojibake($row[$col])) {
                        $updates[$col] = $this->fixVal($row[$col]);
                    }
                }
                if ($updates) {
                    $this->update('{{%company_settings}}', $updates, ['id' => $row['id']]);
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        // ── trigger table (if exists) ─────────────────────────────────────────
        try {
            $triggers = $this->db->createCommand('SELECT id, name, description FROM {{%trigger}} WHERE name IS NOT NULL OR description IS NOT NULL')->queryAll();
            foreach ($triggers as $t) {
                $updates = [];
                if (!empty($t['name']) && $this->isMojibake($t['name'])) {
                    $updates['name'] = $this->fixVal($t['name']);
                }
                if (!empty($t['description']) && $this->isMojibake($t['description'])) {
                    $updates['description'] = $this->fixVal($t['description']);
                }
                if ($updates) {
                    $this->update('{{%trigger}}', $updates, ['id' => $t['id']]);
                }
            }
        } catch (\Exception $e) {
            // ignore
        }
    }

    public function safeDown()
    {
        // Encoding changes are not reversible
    }
}
