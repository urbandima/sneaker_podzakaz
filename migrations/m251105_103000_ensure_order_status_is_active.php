<?php

use yii\db\Migration;

/**
 * Безопасное добавление поля is_active в order_status
 */
class m251105_103000_ensure_order_status_is_active extends Migration
{
    public function safeUp()
    {
        $db = $this->db;
        
        // Проверяем существование таблицы
        $tableSchema = $db->getTableSchema('{{%order_status}}');
        
        if (!$tableSchema) {
            echo "⚠️ Таблица order_status не существует, пропускаем\n";
            return true;
        }
        
        $columns = $tableSchema->columnNames;
        
        // Добавляем is_active если его нет
        if (!in_array('is_active', $columns)) {
            $this->addColumn('{{%order_status}}', 'is_active', $this->boolean()->notNull()->defaultValue(1));
            
            // Все существующие статусы по умолчанию активны
            $this->update('{{%order_status}}', ['is_active' => 1]);
            
            echo "✓ Добавлено поле is_active в order_status\n";
        } else {
            echo "✓ Поле is_active уже существует в order_status\n";
        }
        
        // Создаем индекс если его нет
        $indexes = $db->schema->getTableIndexes('{{%order_status}}');
        $indexNames = array_keys($indexes);
        
        if (!in_array('idx-order_status-is_active', $indexNames)) {
            $this->createIndex('idx-order_status-is_active', '{{%order_status}}', 'is_active');
            echo "✓ Создан индекс idx-order_status-is_active\n";
        }
        
        echo "✅ Таблица order_status готова к использованию\n";
    }

    public function safeDown()
    {
        echo "Откат не реализован (безопасная миграция)\n";
        return true;
    }
}
