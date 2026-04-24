<?php

use yii\db\Migration;

/**
 * W15: Fix double-encoded UTF-8 (mojibake) in app_setting and company_settings text fields.
 * Caused by storing UTF-8 data when the connection/column charset was latin1.
 */
class m260424_420000_fix_mojibake_settings extends Migration
{
    public function safeUp()
    {
        // Fix company_settings text fields
        $rows = $this->db->createCommand('SELECT id, name, address, phone, email, bank, bic, account, work_time FROM {{%company_settings}}')->queryAll();
        foreach ($rows as $row) {
            $fields = ['name', 'address', 'phone', 'email', 'bank', 'bic', 'account', 'work_time'];
            $update = [];
            foreach ($fields as $f) {
                if (!empty($row[$f])) {
                    $fixed = $this->fixMojibake($row[$f]);
                    if ($fixed !== $row[$f]) {
                        $update[$f] = $fixed;
                    }
                }
            }
            if ($update) {
                $this->db->createCommand()->update('{{%company_settings}}', $update, ['id' => $row['id']])->execute();
            }
        }

        // Fix app_setting value fields
        $rows = $this->db->createCommand('SELECT id, value FROM {{%app_setting}} WHERE value IS NOT NULL')->queryAll();
        foreach ($rows as $row) {
            $fixed = $this->fixMojibake($row['value']);
            if ($fixed !== $row['value']) {
                $this->db->createCommand()->update('{{%app_setting}}', ['value' => $fixed], ['id' => $row['id']])->execute();
            }
        }
    }

    public function safeDown()
    {
        // Reversing mojibake repair is not practical
        echo "m260424_420000_fix_mojibake_settings: safeDown() is not reversible.\n";
    }

    private function fixMojibake(string $str): string
    {
        if (mb_check_encoding($str, 'UTF-8') === false) {
            return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }
        // Detect if string contains typical latin-1 representation of Cyrillic UTF-8 bytes
        // Pattern: sequences like Ð°, Ñ‚, Ð¼ etc (UTF-8 encoded as if it were latin-1 then stored as UTF-8)
        if (preg_match('/[\xC3-\xC5][\x80-\xBF]/', $str) || strpos($str, 'Ð') !== false || strpos($str, 'Ñ') !== false) {
            $candidate = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
            if (mb_check_encoding($candidate, 'UTF-8') && $candidate !== $str) {
                return $candidate;
            }
        }
        return $str;
    }
}
