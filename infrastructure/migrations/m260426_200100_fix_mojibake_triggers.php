<?php

use yii\db\Migration;

/**
 * Z2+Z3: Extend mojibake fix to the trigger table (name, description).
 * The previous migration m260424_420000 covered company_settings and app_setting.
 */
class m260426_200100_fix_mojibake_triggers extends Migration
{
    public function safeUp()
    {
        $rows = [];
        try {
            $rows = $this->db->createCommand(
                'SELECT id, name, description FROM {{%trigger}} WHERE name IS NOT NULL OR description IS NOT NULL'
            )->queryAll();
        } catch (\Exception $e) {
            // trigger table may not exist
            return;
        }

        foreach ($rows as $row) {
            $update = [];
            foreach (['name', 'description'] as $col) {
                if (!empty($row[$col])) {
                    $fixed = $this->fixMojibake($row[$col]);
                    if ($fixed !== $row[$col]) {
                        $update[$col] = $fixed;
                    }
                }
            }
            if ($update) {
                $this->db->createCommand()->update('{{%trigger}}', $update, ['id' => $row['id']])->execute();
            }
        }
    }

    public function safeDown()
    {
        echo "m260426_200100_fix_mojibake_triggers: safeDown() is not reversible.\n";
    }

    private function fixMojibake(string $str): string
    {
        if (mb_check_encoding($str, 'UTF-8') === false) {
            return mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
        }
        if (preg_match('/[\xC3-\xC5][\x80-\xBF]/', $str) || strpos($str, 'Ð') !== false || strpos($str, 'Ñ') !== false) {
            $candidate = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
            if (mb_check_encoding($candidate, 'UTF-8') && $candidate !== $str) {
                return $candidate;
            }
        }
        return $str;
    }
}
