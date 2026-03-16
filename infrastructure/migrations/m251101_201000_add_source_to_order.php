<?php

use yii\db\Migration;

/**
 * Добавление полей source и source_id в таблицу order
 * для связи с заявками из каталога
 */
class m251101_201000_add_source_to_order extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'source', $this->string(50)->comment('Источник заказа (catalog, manual)')->after('comment'));
        $this->addColumn('{{%order}}', 'source_id', $this->integer()->comment('ID источника')->after('source'));

        $this->createIndex('idx-order-source', '{{%order}}', ['source', 'source_id']);

        return true;
    }

    public function safeDown()
    {
        $this->dropIndex('idx-order-source', '{{%order}}');
        $this->dropColumn('{{%order}}', 'source_id');
        $this->dropColumn('{{%order}}', 'source');

        return true;
    }
}
