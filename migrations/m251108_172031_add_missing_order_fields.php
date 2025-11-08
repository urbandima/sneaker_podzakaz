<?php

use yii\db\Migration;

class m251108_172031_add_missing_order_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'source', $this->string(50)->comment('Источник заказа (catalog, manual)'));
        $this->addColumn('{{%order}}', 'source_id', $this->integer()->comment('ID источника'));

        $this->createIndex('idx-order-source', '{{%order}}', ['source', 'source_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-order-source', '{{%order}}');
        $this->dropColumn('{{%order}}', 'source_id');
        $this->dropColumn('{{%order}}', 'source');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251108_172031_add_missing_order_fields cannot be reverted.\n";

        return false;
    }
    */
}
