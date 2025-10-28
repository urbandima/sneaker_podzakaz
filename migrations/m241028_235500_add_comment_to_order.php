<?php

use yii\db\Migration;

class m241028_235500_add_comment_to_order extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'comment', $this->text()->after('delivery_date'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'comment');
    }
}
