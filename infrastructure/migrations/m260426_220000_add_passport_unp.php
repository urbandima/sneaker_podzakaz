<?php

use yii\db\Migration;

class m260426_220000_add_passport_unp extends Migration
{
    public function safeUp()
    {
        if (!$this->db->getTableSchema('order')->getColumn('passport_unp')) {
            $this->addColumn('{{%order}}', 'passport_unp', $this->string(20)->null()->comment('Личный номер (УНП) из паспорта — 14 символов'));
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'passport_unp');
    }
}
