<?php

/**
 * Миграция: Добавление полей для отслеживания прогресса в import_task
 * 
 * Исправление ошибки: UnknownPropertyException total_products
 */
class m260330_230000_add_progress_fields_to_import_task extends \yii\db\Migration
{
    public function safeUp()
    {
        // Добавляем поля для отслеживания прогресса
        $this->addColumn('{{%import_task}}', 'total_products', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%import_task}}', 'processed_products', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%import_task}}', 'imported_count', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%import_task}}', 'updated_count', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%import_task}}', 'failed_count', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%import_task}}', 'duplicate_count', $this->integer()->notNull()->defaultValue(0));
        
        // Добавляем поля для времени
        $this->addColumn('{{%import_task}}', 'started_at', $this->integer()->null());
        $this->addColumn('{{%import_task}}', 'finished_at', $this->integer()->null());
        $this->addColumn('{{%import_task}}', 'duration_seconds', $this->integer()->null());
        
        // Дополнительные поля
        $this->addColumn('{{%import_task}}', 'config', $this->text()->null());
        $this->addColumn('{{%import_task}}', 'error_message', $this->text()->null());
        $this->addColumn('{{%import_task}}', 'created_by', $this->integer()->null());
        $this->addColumn('{{%import_task}}', 'file_path', $this->string(500)->null());
        $this->addColumn('{{%import_task}}', 'result', $this->text()->null());
        
        // Индексы
        $this->createIndex('idx-import_task-status', '{{%import_task}}', 'status');
        $this->createIndex('idx-import_task-created_by', '{{%import_task}}', 'created_by');
        
        echo "Поля прогресса добавлены в import_task\n";
    }
    
    public function safeDown()
    {
        $this->dropColumn('{{%import_task}}', 'total_products');
        $this->dropColumn('{{%import_task}}', 'processed_products');
        $this->dropColumn('{{%import_task}}', 'imported_count');
        $this->dropColumn('{{%import_task}}', 'updated_count');
        $this->dropColumn('{{%import_task}}', 'failed_count');
        $this->dropColumn('{{%import_task}}', 'duplicate_count');
        $this->dropColumn('{{%import_task}}', 'started_at');
        $this->dropColumn('{{%import_task}}', 'finished_at');
        $this->dropColumn('{{%import_task}}', 'duration_seconds');
        $this->dropColumn('{{%import_task}}', 'config');
        $this->dropColumn('{{%import_task}}', 'error_message');
        $this->dropColumn('{{%import_task}}', 'created_by');
        $this->dropColumn('{{%import_task}}', 'file_path');
        $this->dropColumn('{{%import_task}}', 'result');
    }
}
