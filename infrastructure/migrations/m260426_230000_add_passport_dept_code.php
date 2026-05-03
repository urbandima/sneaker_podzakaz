<?php

use yii\db\Migration;

class m260426_230000_add_passport_dept_code extends Migration
{
    public function safeUp()
    {
        // Resolve through {{%order}} so the configured tablePrefix is honoured;
        // refresh = true to bypass any stale schema cache from earlier migrations.
        $schema = $this->db->getTableSchema('{{%order}}', true);
        if ($schema === null) {
            // Order table doesn't exist yet — earlier migration order is broken.
            // Bail out instead of crashing on null->getColumn().
            echo "    > skip: {{%order}} table does not exist yet\n";
            return;
        }
        if ($schema->getColumn('passport_dept_code') === null) {
            $this->addColumn(
                '{{%order}}',
                'passport_dept_code',
                $this->string(7)->null()->comment('Код подразделения (РФ, формат XXX-XXX)')
            );
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%order}}', true);
        if ($schema && $schema->getColumn('passport_dept_code') !== null) {
            $this->dropColumn('{{%order}}', 'passport_dept_code');
        }
    }
}
