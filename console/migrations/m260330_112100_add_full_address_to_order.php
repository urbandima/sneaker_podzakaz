<?php

use yii\db\Migration;

/**
 * Добавляет недостающее поле full_address в таблицу order
 */
class m260330_112100_add_full_address_to_order extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        // Добавляем full_address если нет
        if (!$tableSchema->getColumn('full_address')) {
            $this->addColumn('{{%order}}', 'full_address', $this->text());
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        if ($tableSchema->getColumn('full_address')) {
            $this->dropColumn('{{%order}}', 'full_address');
        }
    }
}
