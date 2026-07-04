<?php

use yii\db\Migration;

/**
 * Добавляет недостающие поля city и region в таблицу order
 */
class m260330_111700_add_city_region_to_order extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        // Добавляем city если нет
        if (!$tableSchema->getColumn('city')) {
            $this->addColumn('{{%order}}', 'city', $this->string(100));
        }
        
        // Добавляем region если нет
        if (!$tableSchema->getColumn('region')) {
            $this->addColumn('{{%order}}', 'region', $this->string(100));
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        if ($tableSchema->getColumn('region')) {
            $this->dropColumn('{{%order}}', 'region');
        }
        
        if ($tableSchema->getColumn('city')) {
            $this->dropColumn('{{%order}}', 'city');
        }
    }
}
