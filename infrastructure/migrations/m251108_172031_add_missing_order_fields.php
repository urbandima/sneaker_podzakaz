<?php

use yii\db\Migration;

class m251108_172031_add_missing_order_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        if (!isset($tableSchema->columns['source'])) {
            $this->addColumn('{{%order}}', 'source', $this->string(50)->comment('Источник заказа (catalog, manual)'));
        }
        
        if (!isset($tableSchema->columns['source_id'])) {
            $this->addColumn('{{%order}}', 'source_id', $this->integer()->comment('ID источника'));
        }

        try {
            $this->createIndex('idx-order-source', '{{%order}}', ['source', 'source_id']);
        } catch (\Exception $e) {
            echo "⚠ Индекс idx-order-source уже существует\n";
        }
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
