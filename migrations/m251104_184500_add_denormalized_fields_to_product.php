<?php

use yii\db\Migration;

/**
 * Денормализация Product для решения N+1 Query проблемы
 * Добавляем поля brand_name, category_name, main_image_url
 * для избежания JOIN запросов при загрузке каталога
 */
class m251104_184500_add_denormalized_fields_to_product extends Migration
{
    public function safeUp()
    {
        // Добавляем денормализованные поля
        $this->addColumn('{{%product}}', 'brand_name', $this->string(100)->after('brand_id'));
        $this->addColumn('{{%product}}', 'category_name', $this->string(100)->after('category_id'));
        $this->addColumn('{{%product}}', 'main_image_url', $this->string(500)->after('main_image'));

        // Заполняем существующие данные
        $this->execute("
            UPDATE {{%product}} p
            LEFT JOIN {{%brand}} b ON b.id = p.brand_id
            LEFT JOIN {{%category}} c ON c.id = p.category_id
            SET 
                p.brand_name = b.name,
                p.category_name = c.name,
                p.main_image_url = p.main_image
        ");

        // Создаем индексы для ускорения поиска
        $this->createIndex('idx-product-brand_name', '{{%product}}', 'brand_name');
        $this->createIndex('idx-product-category_name', '{{%product}}', 'category_name');
        $this->createIndex('idx-product-price_active', '{{%product}}', ['price', 'is_active']);
        
        // Composite index для частых запросов
        $this->createIndex('idx-product-catalog', '{{%product}}', ['is_active', 'brand_id', 'category_id', 'price']);

        echo "✓ Денормализованные поля добавлены и заполнены\n";
        echo "✓ Индексы созданы для оптимизации запросов\n";
    }

    public function safeDown()
    {
        // Удаляем индексы
        $this->dropIndex('idx-product-catalog', '{{%product}}');
        $this->dropIndex('idx-product-price_active', '{{%product}}');
        $this->dropIndex('idx-product-category_name', '{{%product}}');
        $this->dropIndex('idx-product-brand_name', '{{%product}}');

        // Удаляем колонки
        $this->dropColumn('{{%product}}', 'main_image_url');
        $this->dropColumn('{{%product}}', 'category_name');
        $this->dropColumn('{{%product}}', 'brand_name');

        echo "✓ Денормализация отменена\n";
    }
}
