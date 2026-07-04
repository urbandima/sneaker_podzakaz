<?php

use yii\db\Migration;

/**
 * AUDIT-05: восстановлено из осиротевшей console/migrations/m260426_100300_fix_mojibake_settings_triggers.php.
 * Часть про {{%trigger}} уже покрыта m260426_200100_fix_mojibake_triggers.php, часть про
 * company_settings/app_setting — m260424_420000_fix_mojibake_settings.php. Таблица {{%settings}}
 * (key-value) не была покрыта ни одной миграцией.
 */
class m260704_100100_fix_mojibake_settings_table extends Migration
{
    private function isMojibake(string $val): bool
    {
        return preg_match('/[ÃÐÑ][©-¿]/', $val) === 1;
    }

    private function fixVal(string $val): string
    {
        $fixed = mb_convert_encoding(
            mb_convert_encoding($val, 'ISO-8859-1', 'UTF-8'),
            'UTF-8',
            'ISO-8859-1'
        );
        return mb_check_encoding($fixed, 'UTF-8') ? $fixed : $val;
    }

    public function safeUp()
    {
        try {
            $rows = $this->db->createCommand('SELECT id, value FROM {{%settings}} WHERE value IS NOT NULL')->queryAll();
        } catch (\Exception $e) {
            return;
        }

        foreach ($rows as $row) {
            if ($this->isMojibake((string) $row['value'])) {
                $fixed = $this->fixVal($row['value']);
                $this->update('{{%settings}}', ['value' => $fixed], ['id' => $row['id']]);
            }
        }
    }

    public function safeDown()
    {
        echo "m260704_100100_fix_mojibake_settings_table: safeDown() не поддерживается (исправление кодировки необратимо).\n";
    }
}
