<?php

/**
 * Миграция создания таблиц тегов товаров и цветов категорий
 * 
 * Создает:
 * - product_tag: таблица тегов
 * - product_tag_assignment: связь товаров и тегов (many-to-many)
 * - category_color: цветовая маркировка категорий
 */

class m260404_102900_create_product_tag_tables extends \yii\db\Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // Таблица тегов товаров
        $this->createTable('{{%product_tag}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull()->comment('Название тега'),
            'slug' => $this->string(100)->notNull()->unique()->comment('SEO-friendly URL'),
            'color' => $this->string(7)->null()->comment('Цвет тега (HEX)'),
            'description' => $this->text()->null()->comment('Описание'),
            'is_active' => $this->boolean()->notNull()->defaultValue(true)->comment('Активен'),
            'sort_order' => $this->integer()->notNull()->defaultValue(0)->comment('Порядок сортировки'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-product_tag-slug', '{{%product_tag}}', 'slug');
        $this->createIndex('idx-product_tag-is_active', '{{%product_tag}}', 'is_active');
        $this->createIndex('idx-product_tag-sort_order', '{{%product_tag}}', 'sort_order');

        // Связующая таблица товаров и тегов
        $this->createTable('{{%product_tag_assignment}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull()->comment('ID товара'),
            'tag_id' => $this->integer()->notNull()->comment('ID тега'),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-product_tag_assignment-product_id', '{{%product_tag_assignment}}', 'product_id');
        $this->createIndex('idx-product_tag_assignment-tag_id', '{{%product_tag_assignment}}', 'tag_id');
        $this->createIndex('idx-product_tag_assignment-unique', '{{%product_tag_assignment}}', ['product_id', 'tag_id'], true);

        // Внешние ключи
        $this->addForeignKey(
            'fk-product_tag_assignment-product_id',
            '{{%product_tag_assignment}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-product_tag_assignment-tag_id',
            '{{%product_tag_assignment}}',
            'tag_id',
            '{{%product_tag}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Таблица цветов категорий
        $this->createTable('{{%category_color}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer()->notNull()->comment('ID категории'),
            'color_code' => $this->string(7)->notNull()->comment('HEX цвет (#FF5733)'),
            'label' => $this->string(50)->null()->comment('Подпись к цвету'),
            'is_active' => $this->boolean()->notNull()->defaultValue(true)->comment('Активен'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-category_color-category_id', '{{%category_color}}', 'category_id');
        $this->createIndex('idx-category_color-is_active', '{{%category_color}}', 'is_active');

        // Внешний ключ
        $this->addForeignKey(
            'fk-category_color-category_id',
            '{{%category_color}}',
            'category_id',
            '{{%category}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Добавляем колонку color_id в category (опционально для быстрого доступа)
        $this->addColumn('{{%category}}', 'color_code', $this->string(7)->null()->comment('HEX цвет категории'));
        $this->createIndex('idx-category-color_code', '{{%category}}', 'color_code');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Удаляем внешние ключи
        $this->dropForeignKey('fk-category_color-category_id', '{{%category_color}}');
        $this->dropForeignKey('fk-product_tag_assignment-tag_id', '{{%product_tag_assignment}}');
        $this->dropForeignKey('fk-product_tag_assignment-product_id', '{{%product_tag_assignment}}');

        // Удаляем индексы с category
        $this->dropIndex('idx-category-color_code', '{{%category}}');
        $this->dropColumn('{{%category}}', 'color_code');

        // Удаляем таблицы
        $this->dropTable('{{%category_color}}');
        $this->dropTable('{{%product_tag_assignment}}');
        $this->dropTable('{{%product_tag}}');

        return true;
    }
}
