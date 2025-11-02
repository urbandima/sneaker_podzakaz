<?php

use yii\db\Migration;

/**
 * Создание базовых таблиц (brand, category, product)
 */
class m250101_000000_create_base_tables extends Migration
{
    public function safeUp()
    {
        // Таблица брендов
        $this->createTable('{{%brand}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull()->unique(),
            'description' => $this->text(),
            'logo' => $this->string(255),
            'is_active' => $this->boolean()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ]);

        $this->createIndex('idx-brand-slug', '{{%brand}}', 'slug');
        $this->createIndex('idx-brand-is_active', '{{%brand}}', 'is_active');

        // Таблица категорий
        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey(),
            'parent_id' => $this->integer(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull()->unique(),
            'description' => $this->text(),
            'image' => $this->string(255),
            'is_active' => $this->boolean()->defaultValue(1),
            'sort_order' => $this->integer()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ]);

        $this->createIndex('idx-category-parent_id', '{{%category}}', 'parent_id');
        $this->createIndex('idx-category-slug', '{{%category}}', 'slug');
        $this->createIndex('idx-category-is_active', '{{%category}}', 'is_active');

        // Таблица товаров
        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull(),
            'brand_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(255)->notNull()->unique(),
            'description' => $this->text(),
            'price' => $this->decimal(10, 2)->notNull(),
            'old_price' => $this->decimal(10, 2),
            'main_image' => $this->string(255),
            'is_active' => $this->boolean()->defaultValue(1),
            'is_featured' => $this->boolean()->defaultValue(0),
            'stock_status' => $this->string(20)->defaultValue('in_stock'),
            'views_count' => $this->integer()->defaultValue(0),
            'meta_title' => $this->string(255),
            'meta_description' => $this->text(),
            'meta_keywords' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-product-category_id', '{{%product}}', 'category_id');
        $this->createIndex('idx-product-brand_id', '{{%product}}', 'brand_id');
        $this->createIndex('idx-product-slug', '{{%product}}', 'slug');
        $this->createIndex('idx-product-is_active', '{{%product}}', 'is_active');
        $this->createIndex('idx-product-price', '{{%product}}', 'price');

        $this->addForeignKey(
            'fk-product-category_id',
            '{{%product}}',
            'category_id',
            '{{%category}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-product-brand_id',
            '{{%product}}',
            'brand_id',
            '{{%brand}}',
            'id',
            'CASCADE'
        );

        // Таблица изображений товаров
        $this->createTable('{{%product_image}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'image' => $this->string(255)->notNull(),
            'is_main' => $this->boolean()->defaultValue(0),
            'sort_order' => $this->integer()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-product_image-product_id', '{{%product_image}}', 'product_id');

        $this->addForeignKey(
            'fk-product_image-product_id',
            '{{%product_image}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );

        // Таблица размеров
        $this->createTable('{{%product_size}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'size' => $this->string(20)->notNull(),
            'is_available' => $this->boolean()->defaultValue(1),
            'stock_quantity' => $this->integer()->defaultValue(0),
        ]);

        $this->createIndex('idx-product_size-product_id', '{{%product_size}}', 'product_id');

        $this->addForeignKey(
            'fk-product_size-product_id',
            '{{%product_size}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );

        // Таблица цветов
        $this->createTable('{{%product_color}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'name' => $this->string(100)->notNull(),
            'hex' => $this->string(7),
        ]);

        $this->createIndex('idx-product_color-product_id', '{{%product_color}}', 'product_id');

        $this->addForeignKey(
            'fk-product_color-product_id',
            '{{%product_color}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );

        echo "✓ Созданы базовые таблицы: brand, category, product, product_image, product_size, product_color\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-product_color-product_id', '{{%product_color}}');
        $this->dropTable('{{%product_color}}');

        $this->dropForeignKey('fk-product_size-product_id', '{{%product_size}}');
        $this->dropTable('{{%product_size}}');

        $this->dropForeignKey('fk-product_image-product_id', '{{%product_image}}');
        $this->dropTable('{{%product_image}}');

        $this->dropForeignKey('fk-product-brand_id', '{{%product}}');
        $this->dropForeignKey('fk-product-category_id', '{{%product}}');
        $this->dropTable('{{%product}}');

        $this->dropTable('{{%category}}');
        $this->dropTable('{{%brand}}');
    }
}
