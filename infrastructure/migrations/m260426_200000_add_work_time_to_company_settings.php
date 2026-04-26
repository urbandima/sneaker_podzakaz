<?php

use yii\db\Migration;

class m260426_200000_add_work_time_to_company_settings extends Migration
{
    public function up()
    {
        $this->addColumn('{{%company_settings}}', 'work_time', $this->string(255)->null()->after('offer_url'));
    }

    public function down()
    {
        $this->dropColumn('{{%company_settings}}', 'work_time');
    }
}
