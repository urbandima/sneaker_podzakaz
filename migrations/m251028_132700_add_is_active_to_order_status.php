<?php

use yii\db\Migration;

class m251028_132700_add_is_active_to_order_status extends Migration
{
    public function safeUp()
    {
        // Добавляем поле is_active
        $this->addColumn('{{%order_status}}', 'is_active', $this->boolean()->notNull()->defaultValue(1));
        
        // Все существующие статусы по умолчанию активны
        $this->update('{{%order_status}}', ['is_active' => 1]);
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order_status}}', 'is_active');
    }
}
