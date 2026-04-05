<?php

use yii\db\Migration;

class m260330_091448_add_test_data_simple extends Migration
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
        echo "m260330_091448_add_test_data_simple cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260330_091448_add_test_data_simple cannot be reverted.\n";

        return false;
    }
    */
}
