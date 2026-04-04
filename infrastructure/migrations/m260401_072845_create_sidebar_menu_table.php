<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%sidebar_menu}}`.
 */
class m260401_072845_create_sidebar_menu_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%sidebar_menu}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%sidebar_menu}}');
    }
}
