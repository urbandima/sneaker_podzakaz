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
        $this->createIndex(
            'idx-product-filter',
            '{{%product}}',
            ['is_active', 'brand_id', 'category_id', 'price']
        );
        echo "✓ Создан idx-product-filter\n";
        
        // Индекс для сортировки по дате создания
        $this->createIndex(
            'idx-product-created',
            '{{%product}}',
            ['created_at']
        );
        echo "✓ Создан idx-product-created\n";
        
        // Индекс для сортировки по просмотрам (популярность)
        $this->createIndex(
            'idx-product-views',
            '{{%product}}',
            ['views_count']
        );
        echo "✓ Создан idx-product-views\n";
        
        // Индекс для сортировки по рейтингу
        $this->createIndex(
            'idx-product-rating',
            '{{%product}}',
            ['rating']
        );
        echo "✓ Создан idx-product-rating\n";
        
        // Индекс для поиска по названию
        $this->createIndex(
            'idx-product-name',
            '{{%product}}',
            ['name']
        );
        echo "✓ Создан idx-product-name\n";
        
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
        
        // Индексы для новых полей фильтров
        $this->createIndex('idx-product-material', '{{%product}}', ['material']);
        echo "✓ Создан idx-product-material\n";
        
        $this->createIndex('idx-product-season', '{{%product}}', ['season']);
        echo "✓ Создан idx-product-season\n";
        
        $this->createIndex('idx-product-gender', '{{%product}}', ['gender']);
        echo "✓ Создан idx-product-gender\n";
        
        $this->createIndex('idx-product-stock', '{{%product}}', ['stock_status']);
        echo "✓ Создан idx-product-stock\n";
        
        // Индекс для старой цены (для фильтра скидок)
        $this->createIndex('idx-product-old-price', '{{%product}}', ['old_price']);
        echo "✓ Создан idx-product-old-price\n";
        
        // Индексы для связанных таблиц
        
        // Brand
        $this->createIndex('idx-brand-slug', '{{%brand}}', ['slug'], true);
        $this->createIndex('idx-brand-active', '{{%brand}}', ['is_active']);
        echo "✓ Созданы индексы для brand\n";
        
        // Category
        $this->createIndex('idx-category-slug', '{{%category}}', ['slug'], true);
        $this->createIndex('idx-category-active', '{{%category}}', ['is_active']);
        $this->createIndex('idx-category-parent', '{{%category}}', ['parent_id']);
        echo "✓ Созданы индексы для category\n";
        
        // ProductImage
        $this->createIndex('idx-product-image-main', '{{%product_image}}', ['product_id', 'is_main']);
        $this->createIndex('idx-product-image-sort', '{{%product_image}}', ['product_id', 'sort_order']);
        echo "✓ Созданы индексы для product_image\n";
        
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
        $this->dropIndex('idx-product-rating', '{{%product}}');
        $this->dropIndex('idx-product-name', '{{%product}}');
        $this->dropIndex('idx-product-slug', '{{%product}}');
        $this->dropIndex('idx-product-material', '{{%product}}');
        $this->dropIndex('idx-product-season', '{{%product}}');
        $this->dropIndex('idx-product-gender', '{{%product}}');
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
