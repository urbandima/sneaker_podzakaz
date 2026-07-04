<?php

use yii\db\Migration;

/**
 * Добавляет недостающее поле postal_code в таблицу order
 */
class m260330_112000_add_postal_code_to_order extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        // Добавляем postal_code если нет
        if (!$tableSchema->getColumn('postal_code')) {
            $this->addColumn('{{%order}}', 'postal_code', $this->string(50));
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        if ($tableSchema->getColumn('postal_code')) {
            $this->dropColumn('{{%order}}', 'postal_code');
        }
    }
}
