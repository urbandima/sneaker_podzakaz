<?php

use yii\db\Migration;

class m260426_180000_passport_citizenship_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('order', 'citizenship',            $this->string(5)->null()->defaultValue('by')->after('inn'));
        $this->addColumn('order', 'passport_issued_by',     $this->string(255)->null()->after('citizenship'));
        $this->addColumn('order', 'passport_division_code', $this->string(20)->null()->after('passport_issued_by'));
    }

    public function safeDown()
    {
        $this->dropColumn('order', 'passport_division_code');
        $this->dropColumn('order', 'passport_issued_by');
        $this->dropColumn('order', 'citizenship');
    }
}
