<?php

use yii\db\Migration;

/**
 * Делает поле created_by в таблице order необязательным (NULL),
 * чтобы заказы с сайта могли создаваться без привязки к менеджеру.
 */
class m260413_110000_make_order_created_by_nullable extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%order}}', 'created_by', $this->integer()->null());
        echo "✓ Поле created_by в таблице order теперь nullable\n";
    }

    public function safeDown()
    {
        // Восстанавливаем NOT NULL (только если нет NULL-значений)
        $this->alterColumn('{{%order}}', 'created_by', $this->integer()->notNull()->defaultValue(1));
    }
}
