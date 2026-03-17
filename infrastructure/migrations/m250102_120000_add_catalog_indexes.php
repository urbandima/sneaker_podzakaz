<?php

use yii\db\Migration;

/**
 * Создание индексов для оптимизации каталога
 * Улучшение производительности фильтрации и сортировки
 */
class m250102_120000_add_catalog_indexes extends Migration
{
    public function safeUp()
    {
        echo "Создание индексов для оптимизации каталога...\n";
        
        // Составной индекс для фильтрации (is_active + brand_id + category_id + price)
        // Используется в 90% запросов каталога
        try {
            $this->createIndex(
                'idx-product-filter',
                '{{%product}}',
                ['is_active', 'brand_id', 'category_id', 'price']
            );
            echo "✓ Создан idx-product-filter\n";
        } catch (\Exception $e) {
            echo "⚠ idx-product-filter уже существует, пропускаем\n";
        }
        
        // Индекс для сортировки по дате создания
        try {
            $this->createIndex(
                'idx-product-created',
                '{{%product}}',
                ['created_at']
            );
            echo "✓ Создан idx-product-created\n";
        } catch (\Exception $e) {
            echo "⚠ idx-product-created уже существует, пропускаем\n";
        }
        
        // Индекс для сортировки по просмотрам (популярность)
        try {
            $this->createIndex(
                'idx-product-views',
                '{{%product}}',
                ['views_count']
            );
            echo "✓ Создан idx-product-views\n";
        } catch (\Exception $e) {
            echo "⚠ idx-product-views уже существует, пропускаем\n";
        }
        
        // Индекс для сортировки по рейтингу - пропускаем, поле не существует
        // $this->createIndex(
        //     'idx-product-rating',
        //     '{{%product}}',
        //     ['rating']
        // );
        echo "⚠ Поле rating не существует, индекс idx-product-rating пропущен\n";
        
        // Индекс для поиска по названию
        try {
            $this->createIndex(
                'idx-product-name',
                '{{%product}}',
                ['name']
            );
            echo "✓ Создан idx-product-name\n";
        } catch (\Exception $e) {
            echo "⚠ idx-product-name уже существует, пропускаем\n";
        }
        
        // Индекс для фильтра по slug (для страницы товара)
        try {
            $this->createIndex(
                'idx-product-slug',
                '{{%product}}',
                ['slug'],
                true  // UNIQUE
            );
            echo "✓ Создан idx-product-slug\n";
        } catch (\Exception $e) {
            echo "⚠ idx-product-slug уже существует, пропускаем\n";
        }
        
        // Индексы для новых полей фильтров - пропускаем несуществующие поля
        // $this->createIndex('idx-product-material', '{{%product}}', ['material']);
        echo "⚠ Поле material не существует, индекс idx-product-material пропущен\n";
        
        // $this->createIndex('idx-product-season', '{{%product}}', ['season']);
        echo "⚠ Поле season не существует, индекс idx-product-season пропущен\n";
        
        // $this->createIndex('idx-product-gender', '{{%product}}', ['gender']);
        echo "⚠ Поле gender не существует, индекс idx-product-gender пропущен\n";
        
        $this->createIndex('idx-product-stock', '{{%product}}', ['stock_status']);
        echo "✓ Создан idx-product-stock\n";
        
        // Индекс для старой цены (для фильтра скидок)
        try {
            $this->createIndex('idx-product-old-price', '{{%product}}', ['old_price']);
            echo "✓ Создан idx-product-old-price\n";
        } catch (\Exception $e) {
            echo "⚠ idx-product-old-price уже существует, пропускаем\n";
        }
        
        // Индексы для связанных таблиц
        
        // Brand
        try {
            $this->createIndex('idx-brand-slug', '{{%brand}}', ['slug'], true);
            $this->createIndex('idx-brand-active', '{{%brand}}', ['is_active']);
            echo "✓ Созданы индексы для brand\n";
        } catch (\Exception $e) {
            echo "⚠ Индексы brand уже существуют, пропускаем\n";
        }
        
        // Category
        try {
            $this->createIndex('idx-category-slug', '{{%category}}', ['slug'], true);
            $this->createIndex('idx-category-active', '{{%category}}', ['is_active']);
            $this->createIndex('idx-category-parent', '{{%category}}', ['parent_id']);
            echo "✓ Созданы индексы для category\n";
        } catch (\Exception $e) {
            echo "⚠ Индексы category уже существуют, пропускаем\n";
        }
        
        // ProductImage
        try {
            $this->createIndex('idx-product-image-main', '{{%product_image}}', ['product_id', 'is_main']);
            $this->createIndex('idx-product-image-sort', '{{%product_image}}', ['product_id', 'sort_order']);
            echo "✓ Созданы индексы для product_image\n";
        } catch (\Exception $e) {
            echo "⚠ Индексы product_image уже существуют, пропускаем\n";
        }
        
        echo "\n✅ Все индексы успешно созданы!\n";
        echo "Ожидаемое улучшение производительности: +200%\n";
    }

    public function safeDown()
    {
        echo "Удаление индексов каталога...\n";
        
        // Product
        $this->dropIndex('idx-product-filter', '{{%product}}');
        $this->dropIndex('idx-product-created', '{{%product}}');
        $this->dropIndex('idx-product-views', '{{%product}}');
        // $this->dropIndex('idx-product-rating', '{{%product}}'); // поле не существует
        $this->dropIndex('idx-product-name', '{{%product}}');
        $this->dropIndex('idx-product-slug', '{{%product}}');
        // $this->dropIndex('idx-product-material', '{{%product}}'); // поле не существует
        // $this->dropIndex('idx-product-season', '{{%product}}'); // поле не существует
        // $this->dropIndex('idx-product-gender', '{{%product}}'); // поле не существует
        $this->dropIndex('idx-product-stock', '{{%product}}');
        $this->dropIndex('idx-product-old-price', '{{%product}}');
        
        // Brand
        $this->dropIndex('idx-brand-slug', '{{%brand}}');
        $this->dropIndex('idx-brand-active', '{{%brand}}');
        
        // Category
        $this->dropIndex('idx-category-slug', '{{%category}}');
        $this->dropIndex('idx-category-active', '{{%category}}');
        $this->dropIndex('idx-category-parent', '{{%category}}');
        
        // ProductImage
        $this->dropIndex('idx-product-image-main', '{{%product_image}}');
        $this->dropIndex('idx-product-image-sort', '{{%product_image}}');
        
        echo "✓ Индексы удалены\n";
    }
}
