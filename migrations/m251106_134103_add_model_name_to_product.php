<?php

use yii\db\Migration;

class m251106_134103_add_model_name_to_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%product}}', 'model_name', $this->string(255)->after('name')->comment('Название модели на английском'));
        
        echo "Добавлено поле model_name в таблицу product\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%product}}', 'model_name');
        
        echo "Поле model_name удалено из таблицы product\n";
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251106_134103_add_model_name_to_product cannot be reverted.\n";

        return false;
    }
    */
}
