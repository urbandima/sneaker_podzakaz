<?php

use yii\db\Migration;

class m260426_180000_passport_citizenship_fields extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        if ($schema === null) {
            echo "    > skip: {{%order}} table does not exist yet\n";
            return;
        }
        $cols = $schema->columnNames;

        if (!in_array('citizenship', $cols, true)) {
            $this->addColumn('order', 'citizenship', $this->string(5)->null()->defaultValue('by')->after('inn'));
        }
        if (!in_array('passport_issued_by', $cols, true)) {
            $this->addColumn('order', 'passport_issued_by', $this->string(255)->null()->after('citizenship'));
        }
        if (!in_array('passport_division_code', $cols, true)) {
            $this->addColumn('order', 'passport_division_code', $this->string(20)->null()->after('passport_issued_by'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        $cols = $schema ? $schema->columnNames : [];

        if (in_array('passport_division_code', $cols, true)) {
            $this->dropColumn('order', 'passport_division_code');
        }
        if (in_array('passport_issued_by', $cols, true)) {
            $this->dropColumn('order', 'passport_issued_by');
        }
        if (in_array('citizenship', $cols, true)) {
            $this->dropColumn('order', 'citizenship');
        }
    }
}
