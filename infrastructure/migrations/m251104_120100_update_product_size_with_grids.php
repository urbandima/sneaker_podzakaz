<?php

use yii\db\Migration;

/**
 * Обновление таблицы размеров - добавление размерных сеток (US, EU, UK, CM)
 */
class m251104_120100_update_product_size_with_grids extends Migration
{
    public function safeUp()
    {
        // Переименовываем stock_quantity в stock для консистентности
        if ($this->db->schema->getTableSchema('{{%product_size}}')->getColumn('stock_quantity')) {
            $this->renameColumn('{{%product_size}}', 'stock_quantity', 'stock');
        }
        
        // Добавляем размерные сетки
        $this->addColumn('{{%product_size}}', 'us_size', $this->string(10)->comment('Размер US'));
        $this->addColumn('{{%product_size}}', 'eu_size', $this->string(10)->comment('Размер EU'));
        $this->addColumn('{{%product_size}}', 'uk_size', $this->string(10)->comment('Размер UK'));
        $this->addColumn('{{%product_size}}', 'cm_size', $this->decimal(5, 1)->comment('Размер в CM'));
        
        // Поля для интеграции с Poizon
        $this->addColumn('{{%product_size}}', 'poizon_sku_id', $this->string(50)->comment('SKU ID в Poizon'));
        $this->addColumn('{{%product_size}}', 'poizon_stock', $this->integer()->defaultValue(0)->comment('Остаток на Poizon'));
        $this->addColumn('{{%product_size}}', 'poizon_price_cny', $this->decimal(10, 2)->comment('Цена размера на Poizon'));
        
        // Timestamps
        $this->addColumn('{{%product_size}}', 'created_at', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Создано'));
        $this->addColumn('{{%product_size}}', 'updated_at', $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Обновлено'));
        
        // Индексы
        $this->createIndex('idx-product_size-poizon_sku_id', '{{%product_size}}', 'poizon_sku_id');
        $this->createIndex('idx-product_size-is_available', '{{%product_size}}', 'is_available');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-product_size-is_available', '{{%product_size}}');
        $this->dropIndex('idx-product_size-poizon_sku_id', '{{%product_size}}');
        
        $this->dropColumn('{{%product_size}}', 'updated_at');
        $this->dropColumn('{{%product_size}}', 'created_at');
        $this->dropColumn('{{%product_size}}', 'poizon_price_cny');
        $this->dropColumn('{{%product_size}}', 'poizon_stock');
        $this->dropColumn('{{%product_size}}', 'poizon_sku_id');
        $this->dropColumn('{{%product_size}}', 'cm_size');
        $this->dropColumn('{{%product_size}}', 'uk_size');
        $this->dropColumn('{{%product_size}}', 'eu_size');
        $this->dropColumn('{{%product_size}}', 'us_size');
        
        if ($this->db->schema->getTableSchema('{{%product_size}}')->getColumn('stock')) {
            $this->renameColumn('{{%product_size}}', 'stock', 'stock_quantity');
        }
    }
}
