<?php

use yii\db\Migration;

/**
 * Безопасное создание таблиц import_batch и import_log
 */
class m251105_106000_ensure_import_tables_exist extends Migration
{
    public function safeUp()
    {
        echo "📊 Проверка таблиц импорта Poizon...\n";
        
        // 1. Таблица import_batch
        if (!$this->db->schema->getTableSchema('{{%import_batch}}', true)) {
            echo "  Создание таблицы import_batch...\n";
            
            $this->createTable('{{%import_batch}}', [
                'id' => $this->primaryKey(),
                'source' => $this->string(50)->notNull()->defaultValue('poizon')->comment('Источник'),
                'type' => $this->string(50)->notNull()->defaultValue('full')->comment('Тип импорта'),
                'status' => $this->string(20)->notNull()->defaultValue('pending')->comment('Статус'),
                'started_at' => $this->timestamp()->null()->comment('Время старта'),
                'finished_at' => $this->timestamp()->null()->comment('Время завершения'),
                'duration_seconds' => $this->integer()->comment('Длительность'),
                
                // Метрики
                'total_items' => $this->integer()->defaultValue(0)->comment('Всего обработано'),
                'created_count' => $this->integer()->defaultValue(0)->comment('Создано'),
                'updated_count' => $this->integer()->defaultValue(0)->comment('Обновлено'),
                'skipped_count' => $this->integer()->defaultValue(0)->comment('Пропущено'),
                'error_count' => $this->integer()->defaultValue(0)->comment('Ошибок'),
                
                // Дополнительно
                'config' => $this->text()->comment('Конфигурация JSON'),
                'summary' => $this->text()->comment('Итоговая статистика JSON'),
                'error_message' => $this->text()->comment('Сообщение об ошибке'),
                
                'created_by' => $this->integer()->comment('Кто запустил'),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            
            $this->createIndex('idx-import_batch-status', '{{%import_batch}}', 'status');
            $this->createIndex('idx-import_batch-source', '{{%import_batch}}', 'source');
            $this->createIndex('idx-import_batch-started_at', '{{%import_batch}}', 'started_at');
            
            echo "  ✓ Таблица import_batch создана\n";
        } else {
            echo "  ✓ Таблица import_batch уже существует\n";
        }
        
        // 2. Таблица import_log
        if (!$this->db->schema->getTableSchema('{{%import_log}}', true)) {
            echo "  Создание таблицы import_log...\n";
            
            $this->createTable('{{%import_log}}', [
                'id' => $this->primaryKey(),
                'batch_id' => $this->integer()->notNull()->comment('ID батча'),
                'product_id' => $this->integer()->comment('ID товара'),
                'action' => $this->string(20)->notNull()->comment('Действие'),
                'level' => $this->string(20)->notNull()->defaultValue('info')->comment('Уровень'),
                
                // Данные товара
                'sku' => $this->string(100)->comment('SKU'),
                'poizon_id' => $this->string(50)->comment('Poizon ID'),
                'product_name' => $this->string(255)->comment('Название'),
                
                // Детали
                'message' => $this->text()->comment('Сообщение'),
                'data' => $this->text()->comment('Данные JSON'),
                'error_details' => $this->text()->comment('Детали ошибки'),
                
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
            
            $this->createIndex('idx-import_log-batch_id', '{{%import_log}}', 'batch_id');
            $this->createIndex('idx-import_log-product_id', '{{%import_log}}', 'product_id');
            $this->createIndex('idx-import_log-action', '{{%import_log}}', 'action');
            $this->createIndex('idx-import_log-level', '{{%import_log}}', 'level');
            $this->createIndex('idx-import_log-sku', '{{%import_log}}', 'sku');
            
            echo "  ✓ Таблица import_log создана\n";
        } else {
            echo "  ✓ Таблица import_log уже существует\n";
        }
        
        // 3. Создание внешних ключей (безопасно)
        $this->createForeignKeyIfNotExists(
            'fk-import_log-batch_id',
            '{{%import_log}}',
            'batch_id',
            '{{%import_batch}}',
            'id',
            'CASCADE'
        );
        
        $this->createForeignKeyIfNotExists(
            'fk-import_log-product_id',
            '{{%import_log}}',
            'product_id',
            '{{%product}}',
            'id',
            'SET NULL'
        );
        
        // Внешний ключ на user только если таблица существует
        if ($this->db->schema->getTableSchema('{{%user}}', true)) {
            $this->createForeignKeyIfNotExists(
                'fk-import_batch-created_by',
                '{{%import_batch}}',
                'created_by',
                '{{%user}}',
                'id',
                'SET NULL'
            );
        }
        
        echo "✅ Таблицы импорта готовы\n";
    }
    
    /**
     * Создание внешнего ключа если его нет
     */
    private function createForeignKeyIfNotExists($name, $table, $column, $refTable, $refColumn, $delete = null)
    {
        try {
            // Проверяем существование через информационную схему
            $db = $this->db;
            $schema = $db->schema;
            
            // Получаем имя таблицы без префикса
            $tableName = str_replace(['{{%', '}}'], '', $table);
            $tableName = $db->tablePrefix . $tableName;
            
            $sql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = :table 
                    AND CONSTRAINT_NAME = :name";
            
            $exists = $db->createCommand($sql, [
                ':table' => $tableName,
                ':name' => $name
            ])->queryScalar();
            
            if (!$exists) {
                $this->addForeignKey($name, $table, $column, $refTable, $refColumn, $delete);
                echo "  ✓ Создан внешний ключ {$name}\n";
            }
        } catch (\Exception $e) {
            echo "  ⚠️ Ошибка создания FK {$name}: " . $e->getMessage() . "\n";
        }
    }

    public function safeDown()
    {
        echo "Откат не реализован (безопасная миграция)\n";
        return true;
    }
}
