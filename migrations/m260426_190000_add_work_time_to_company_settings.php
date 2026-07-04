<?php

use yii\db\Migration;

class m260426_190000_add_work_time_to_company_settings extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%company_settings}}');
        if ($tableSchema && !isset($tableSchema->columns['work_time'])) {
            $this->addColumn('{{%company_settings}}', 'work_time', $this->string(255)->null()->defaultValue(null)->comment('Время работы'));
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('{{%company_settings}}');
        if ($tableSchema && isset($tableSchema->columns['work_time'])) {
            $this->dropColumn('{{%company_settings}}', 'work_time');
        }
    }
}
