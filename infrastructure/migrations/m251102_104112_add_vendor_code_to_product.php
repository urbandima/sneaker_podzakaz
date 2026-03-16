<?php

use yii\db\Migration;

class m251102_104112_add_vendor_code_to_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251102_104112_add_vendor_code_to_product cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251102_104112_add_vendor_code_to_product cannot be reverted.\n";

        return false;
    }
    */
}
