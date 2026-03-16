<?php

use yii\db\Migration;

/**
 * Проверка и добавление полей (безопасная миграция)
 */
class m251105_102000_ensure_new_fields_exist extends Migration
{
    public function safeUp()
    {
        $db = $this->db;
        
        // Проверяем поля в product
        $productColumns = $db->getTableSchema('{{%product}}')->columnNames;
        
        if (!in_array('series_name', $productColumns)) {
            $this->addColumn('{{%product}}', 'series_name', $this->string(255)->comment('Название серии'));
            echo "✓ Добавлено поле series_name\n";
        }
        
        if (!in_array('delivery_time_min', $productColumns)) {
            $this->addColumn('{{%product}}', 'delivery_time_min', $this->integer()->comment('Минимальный срок доставки (дни)'));
            echo "✓ Добавлено поле delivery_time_min\n";
        }
        
        if (!in_array('delivery_time_max', $productColumns)) {
            $this->addColumn('{{%product}}', 'delivery_time_max', $this->integer()->comment('Максимальный срок доставки (дни)'));
            echo "✓ Добавлено поле delivery_time_max\n";
        }
        
        if (!in_array('related_products_json', $productColumns)) {
            $this->addColumn('{{%product}}', 'related_products_json', $this->text()->comment('Связанные товары (JSON array)'));
            echo "✓ Добавлено поле related_products_json\n";
        }
        
        // Проверяем поля в product_size
        $sizeColumns = $db->getTableSchema('{{%product_size}}')->columnNames;
        
        if (!in_array('color_variant', $sizeColumns)) {
            $this->addColumn('{{%product_size}}', 'color_variant', $this->string(100)->comment('Цвет конкретного варианта'));
            echo "✓ Добавлено поле color_variant\n";
        }
        
        if (!in_array('delivery_time_min', $sizeColumns)) {
            $this->addColumn('{{%product_size}}', 'delivery_time_min', $this->integer()->comment('Минимальный срок доставки'));
            echo "✓ Добавлено поле delivery_time_min в product_size\n";
        }
        
        if (!in_array('delivery_time_max', $sizeColumns)) {
            $this->addColumn('{{%product_size}}', 'delivery_time_max', $this->integer()->comment('Максимальный срок доставки'));
            echo "✓ Добавлено поле delivery_time_max в product_size\n";
        }
        
        // Проверяем индексы
        $productIndexes = $this->db->schema->getTableIndexes('{{%product}}');
        $indexNames = array_keys($productIndexes);
        
        if (!in_array('idx-product-series_name', $indexNames)) {
            $this->createIndex('idx-product-series_name', '{{%product}}', 'series_name');
            echo "✓ Создан индекс idx-product-series_name\n";
        }
        
        if (!in_array('idx-product-delivery_time', $indexNames)) {
            $this->createIndex('idx-product-delivery_time', '{{%product}}', ['delivery_time_min', 'delivery_time_max']);
            echo "✓ Создан индекс idx-product-delivery_time\n";
        }
        
        $sizeIndexes = $this->db->schema->getTableIndexes('{{%product_size}}');
        $sizeIndexNames = array_keys($sizeIndexes);
        
        if (!in_array('idx-product_size-color_variant', $sizeIndexNames)) {
            $this->createIndex('idx-product_size-color_variant', '{{%product_size}}', 'color_variant');
            echo "✓ Создан индекс idx-product_size-color_variant\n";
        }
        
        echo "✅ Все поля проверены и добавлены если необходимо\n";
    }

    public function safeDown()
    {
        echo "Откат не реализован (безопасная миграция)\n";
        return true;
    }
}
