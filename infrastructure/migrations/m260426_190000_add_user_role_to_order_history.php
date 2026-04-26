<?php

use yii\db\Migration;

class m260426_190000_add_user_role_to_order_history extends Migration
{
    public function safeUp()
    {
        $this->addColumn('order_history', 'user_role', $this->string(255)->null()->after('user_name'));
    }

    public function safeDown()
    {
        $this->dropColumn('order_history', 'user_role');
    }
}
