<?php

use yii\db\Migration;

class m260331_085833_add_is_active_to_coupon_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%coupon}}', 'is_active', $this->boolean()->notNull()->defaultValue(1));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%coupon}}', 'is_active');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260331_085833_add_is_active_to_coupon_table cannot be reverted.\n";

        return false;
    }
    */
}
