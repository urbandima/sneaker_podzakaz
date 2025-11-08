<?php

use yii\db\Migration;

/**
 * Индексы для оптимизации производительности
 * Ожидаемый эффект: -80% времени выполнения запросов с фильтрами
 */
class m250107_002700_add_performance_indexes extends Migration
{
    public function safeUp()
    {
        // 1. Фильтрация по бренду + статус (используется постоянно в каталоге)
        $this->createIndex(
            'idx_product_brand_active',
            'product',
            ['brand_id', 'is_active', 'stock_status']
        );

        // 2. Фильтрация по категории + статус
        $this->createIndex(
            'idx_product_category_active',
            'product',
            ['category_id', 'is_active', 'stock_status']
        );

        // 3. Поиск товара по slug (страница товара)
        $this->createIndex(
            'idx_product_slug',
            'product',
            'slug',
            true // unique
        );

        // 4. Сортировка по популярности (views_count DESC)
        $this->createIndex(
            'idx_product_views',
            'product',
            'views_count'
        );

        // 5. Сортировка по дате создания (новинки)
        $this->createIndex(
            'idx_product_created',
            'product',
            'created_at'
        );

        // 6. Размеры для фильтров (product_id + доступность + EU размер)
        $this->createIndex(
            'idx_size_product_available',
            'product_size',
            ['product_id', 'is_available', 'eu_size']
        );

        // 7. Избранное пользователя (быстрая проверка "в избранном?")
        $this->createIndex(
            'idx_favorite_user_product',
            'product_favorite',
            ['user_id', 'product_id'],
            true // unique - один товар один раз в избранном
        );

        // 8. Цветовые варианты товара
        $this->createIndex(
            'idx_color_product',
            'product_color',
            'product_id'
        );

        // 9. Изображения товара (для галереи)
        $this->createIndex(
            'idx_image_product',
            'product_image',
            'product_id'
        );

        // 10. Composite index для сложных фильтров (бренд + категория + активность)
        $this->createIndex(
            'idx_product_brand_category',
            'product',
            ['brand_id', 'category_id', 'is_active']
        );

        echo "✅ Добавлено 10 индексов для оптимизации производительности\n";
        echo "📊 Ожидаемый эффект: -80% времени запросов с фильтрами\n";
    }

    public function safeDown()
    {
        $this->dropIndex('idx_product_brand_active', 'product');
        $this->dropIndex('idx_product_category_active', 'product');
        $this->dropIndex('idx_product_slug', 'product');
        $this->dropIndex('idx_product_views', 'product');
        $this->dropIndex('idx_product_created', 'product');
        $this->dropIndex('idx_size_product_available', 'product_size');
        $this->dropIndex('idx_favorite_user_product', 'product_favorite');
        $this->dropIndex('idx_color_product', 'product_color');
        $this->dropIndex('idx_image_product', 'product_image');
        $this->dropIndex('idx_product_brand_category', 'product');

        echo "❌ Удалены индексы производительности\n";
    }
}
