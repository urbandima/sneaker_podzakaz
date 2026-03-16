<?php

use yii\db\Migration;

/**
 * Безопасное добавление всех полей в product_size
 */
class m251105_104100_ensure_product_size_fields_exist extends Migration
{
    public function safeUp()
    {
        $db = $this->db;
        
        // Проверяем существование таблицы
        $tableSchema = $db->getTableSchema('{{%product_size}}');
        
        if (!$tableSchema) {
            echo "⚠️ Таблица product_size не существует, пропускаем\n";
            return true;
        }
        
        $columns = $tableSchema->columnNames;
        
        // Проверяем stock_quantity → stock
        if (in_array('stock_quantity', $columns) && !in_array('stock', $columns)) {
            $this->renameColumn('{{%product_size}}', 'stock_quantity', 'stock');
            echo "✓ Переименовано поле stock_quantity → stock\n";
        }
        
        // Список всех необходимых полей
        $requiredFields = [
            // Размерные сетки
            'us_size' => $this->string(10)->comment('Размер US'),
            'eu_size' => $this->string(10)->comment('Размер EU'),
            'uk_size' => $this->string(10)->comment('Размер UK'),
            'cm_size' => $this->decimal(5, 1)->comment('Размер в CM'),
            
            // Poizon поля
            'poizon_sku_id' => $this->string(50)->comment('SKU ID в Poizon'),
            'poizon_stock' => $this->integer()->defaultValue(0)->comment('Остаток на Poizon'),
            'poizon_price_cny' => $this->decimal(10, 2)->comment('Цена размера на Poizon'),
            
            // Новые поля (из нашей миграции)
            'price_cny' => $this->decimal(10, 2)->comment('Цена в CNY'),
            'price_byn' => $this->decimal(10, 2)->comment('Цена в BYN'),
            
            // Timestamps
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Создано'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Обновлено'),
        ];
        
        // Добавляем каждое поле если его нет
        foreach ($requiredFields as $fieldName => $fieldType) {
            if (!in_array($fieldName, $columns)) {
                try {
                    $this->addColumn('{{%product_size}}', $fieldName, $fieldType);
                    echo "✓ Добавлено поле {$fieldName}\n";
                } catch (\Exception $e) {
                    echo "⚠️ Ошибка добавления {$fieldName}: " . $e->getMessage() . "\n";
                }
            } else {
                echo "  Поле {$fieldName} уже существует\n";
            }
        }
        
        // Создаём индексы если их нет
        $indexes = $db->schema->getTableIndexes('{{%product_size}}');
        $indexNames = array_keys($indexes);
        
        $requiredIndexes = [
            'idx-product_size-poizon_sku_id' => 'poizon_sku_id',
            'idx-product_size-is_available' => 'is_available',
        ];
        
        foreach ($requiredIndexes as $indexName => $column) {
            if (!in_array($indexName, $indexNames)) {
                try {
                    if (in_array($column, $columns) || in_array($column, array_keys($requiredFields))) {
                        $this->createIndex($indexName, '{{%product_size}}', $column);
                        echo "✓ Создан индекс {$indexName}\n";
                    }
                } catch (\Exception $e) {
                    echo "⚠️ Ошибка создания индекса {$indexName}: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "✅ Все поля product_size проверены\n";
    }

    public function safeDown()
    {
        echo "Откат не реализован (безопасная миграция)\n";
        return true;
    }
}
