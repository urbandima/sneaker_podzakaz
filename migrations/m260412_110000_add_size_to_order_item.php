<?php

use yii\db\Migration;

class m260412_110000_add_size_to_order_item extends Migration
{
    public function up()
    {
        $this->addColumn('{{%order_item}}', 'size', $this->string(255)->null()->after('product_name'));
    }

    public function down()
    {
        $this->dropColumn('{{%order_item}}', 'size');
    }
}
