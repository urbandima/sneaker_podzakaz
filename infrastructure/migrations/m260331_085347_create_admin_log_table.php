<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%admin_log}}`.
 */
class m260331_085347_create_admin_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%admin_log}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%admin_log}}');
    }
}
