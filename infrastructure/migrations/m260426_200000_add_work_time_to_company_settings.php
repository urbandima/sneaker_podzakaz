<?php

use yii\db\Migration;

class m260426_200000_add_work_time_to_company_settings extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%company_settings}}', true);
        if ($schema === null) {
            echo "    > skip: {{%company_settings}} table does not exist yet\n";
            return;
        }
        if (!in_array('work_time', $schema->columnNames, true)) {
            $this->addColumn('{{%company_settings}}', 'work_time', $this->string(255)->null()->after('offer_url'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%company_settings}}', true);
        if ($schema && in_array('work_time', $schema->columnNames, true)) {
            $this->dropColumn('{{%company_settings}}', 'work_time');
        }
    }
}
