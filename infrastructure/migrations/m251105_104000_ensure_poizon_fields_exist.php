<?php

use yii\db\Migration;

/**
 * Безопасное добавление всех Poizon полей в product
 */
class m251105_104000_ensure_poizon_fields_exist extends Migration
{
    public function safeUp()
    {
        $db = $this->db;
        
        // Проверяем существование таблицы
        $tableSchema = $db->getTableSchema('{{%product}}');
        
        if (!$tableSchema) {
            echo "⚠️ Таблица product не существует, пропускаем\n";
            return true;
        }
        
        $columns = $tableSchema->columnNames;
        
        // Список всех Poizon полей
        $poizonFields = [
            'sku' => $this->string(100)->comment('Уникальный SKU товара'),
            'poizon_id' => $this->string(50)->comment('ID товара в Poizon'),
            'poizon_spu_id' => $this->string(50)->comment('SPU ID в Poizon'),
            'poizon_url' => $this->string(500)->comment('URL товара на Poizon'),
            'poizon_price_cny' => $this->decimal(10, 2)->comment('Цена в CNY на Poizon'),
            'last_sync_at' => $this->timestamp()->null()->comment('Последняя синхронизация'),
            'upper_material' => $this->string(100)->comment('Материал верха'),
            'sole_material' => $this->string(100)->comment('Материал подошвы'),
            'color_description' => $this->string(255)->comment('Описание цвета'),
            'style_code' => $this->string(100)->comment('Код стиля/модели'),
            'release_year' => $this->integer()->comment('Год выпуска'),
            'is_limited' => $this->boolean()->defaultValue(0)->comment('Лимитированная модель'),
            'weight' => $this->integer()->comment('Вес в граммах'),
        ];
        
        // Добавляем каждое поле если его нет
        foreach ($poizonFields as $fieldName => $fieldType) {
            if (!in_array($fieldName, $columns)) {
                $this->addColumn('{{%product}}', $fieldName, $fieldType);
                echo "✓ Добавлено поле {$fieldName}\n";
            } else {
                echo "  Поле {$fieldName} уже существует\n";
            }
        }
        
        // Создаём индексы если их нет
        $indexes = $db->schema->getTableIndexes('{{%product}}');
        $indexNames = array_keys($indexes);
        
        $requiredIndexes = [
            'idx-product-sku' => 'sku',
            'idx-product-poizon_id' => 'poizon_id',
            'idx-product-poizon_spu_id' => 'poizon_spu_id',
            'idx-product-last_sync_at' => 'last_sync_at',
        ];
        
        foreach ($requiredIndexes as $indexName => $column) {
            if (!in_array($indexName, $indexNames)) {
                if ($column === 'sku') {
                    // Для SKU проверяем уникальность
                    $this->createIndex($indexName, '{{%product}}', $column, true);
                } else {
                    $this->createIndex($indexName, '{{%product}}', $column);
                }
                echo "✓ Создан индекс {$indexName}\n";
            }
        }
        
        echo "✅ Все Poizon поля проверены и добавлены\n";
    }

    public function safeDown()
    {
        echo "Откат не реализован (безопасная миграция)\n";
        return true;
    }
}
