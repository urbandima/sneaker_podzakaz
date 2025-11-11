<?php

use yii\db\Migration;

/**
 * Добавление индекса для оптимизации сортировки по цене
 * 
 * Проблема: При сортировке каталога по цене используются подзапросы к product_size,
 * которые без индекса работают медленно на больших объемах данных.
 * 
 * Решение: Составной индекс (product_id, is_available, price_byn) для оптимизации
 * подзапросов MIN/MAX в CatalogController::applyFilters()
 */
class m251110_191000_add_price_sorting_index extends Migration
{
    public function safeUp()
    {
        // Составной индекс для оптимизации сортировки по цене
        // Покрывает условия: WHERE product_id = X AND is_available = 1 AND price_byn > 0
        $this->createIndex(
            'idx_product_size_price_sorting',
            '{{%product_size}}',
            ['product_id', 'is_available', 'price_byn']
        );
        
        echo "✅ Создан индекс idx_product_size_price_sorting для оптимизации сортировки по цене\n";
    }

    public function safeDown()
    {
        $this->dropIndex('idx_product_size_price_sorting', '{{%product_size}}');
        echo "✅ Удален индекс idx_product_size_price_sorting\n";
    }
}
