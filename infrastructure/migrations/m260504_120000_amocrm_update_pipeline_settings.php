<?php

use yii\db\Migration;

/**
 * Updates app_setting (section=amocrm) to production values (CMP-154).
 *
 * pipeline_id : 4453963  (СНИКЕРХЭД — единая воронка IG/FB)
 * domain      : dalmatinets102gmailcom.amocrm.ru
 *
 * Old values were qa-test placeholders (pipeline 8923098, qa-test.amocrm.ru).
 */
class m260504_120000_amocrm_update_pipeline_settings extends Migration
{
    public function safeUp(): void
    {
        $this->update(
            '{{%app_setting}}',
            ['value' => '4453963'],
            ['section' => 'amocrm', 'key' => 'pipeline_id']
        );

        $this->update(
            '{{%app_setting}}',
            ['value' => 'dalmatinets102gmailcom.amocrm.ru'],
            ['section' => 'amocrm', 'key' => 'domain']
        );
    }

    public function safeDown(): void
    {
        $this->update(
            '{{%app_setting}}',
            ['value' => '8923098'],
            ['section' => 'amocrm', 'key' => 'pipeline_id']
        );

        $this->update(
            '{{%app_setting}}',
            ['value' => 'qa-test.amocrm.ru'],
            ['section' => 'amocrm', 'key' => 'domain']
        );
    }
}
