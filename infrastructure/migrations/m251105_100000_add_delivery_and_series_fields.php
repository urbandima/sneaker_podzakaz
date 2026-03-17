<?php

use yii\db\Migration;

/**
 * Добавление полей для серии товара, сроков доставки и связанных товаров
 */
class m251105_100000_add_delivery_and_series_fields extends Migration
{
    public function safeUp()
    {
        // Добавляем поля в таблицу product
        try {
            $this->addColumn('{{%product}}', 'series_name', $this->string(255)->comment('Название серии (Air Max, Dunk Low, Jordan 1 и т.д.)'));
        } catch (\Exception $e) {
            echo "⚠ Колонка series_name уже существует\n";
        }
        try {
            $this->addColumn('{{%product}}', 'delivery_time_min', $this->integer()->comment('Минимальный срок доставки (дни)'));
        } catch (\Exception $e) {
            echo "⚠ Колонка delivery_time_min уже существует\n";
        }
        try {
            $this->addColumn('{{%product}}', 'delivery_time_max', $this->integer()->comment('Максимальный срок доставки (дни)'));
        } catch (\Exception $e) {
            echo "⚠ Колонка delivery_time_max уже существует\n";
        }
        try {
            $this->addColumn('{{%product}}', 'related_products_json', $this->text()->comment('Связанные товары (JSON array)'));
        } catch (\Exception $e) {
            echo "⚠ Колонка related_products_json уже существует\n";
        }
        
        // Добавляем поля в таблицу product_size
        try {
            $this->addColumn('{{%product_size}}', 'color_variant', $this->string(100)->comment('Цвет конкретного варианта размера'));
        } catch (\Exception $e) {
            echo "⚠ Колонка color_variant уже существует\n";
        }
        try {
            $this->addColumn('{{%product_size}}', 'delivery_time_min', $this->integer()->comment('Минимальный срок доставки для размера (дни)'));
        } catch (\Exception $e) {
            echo "⚠ Колонка delivery_time_min в product_size уже существует\n";
        }
        try {
            $this->addColumn('{{%product_size}}', 'delivery_time_max', $this->integer()->comment('Максимальный срок доставки для размера (дни)'));
        } catch (\Exception $e) {
            echo "⚠ Колонка delivery_time_max в product_size уже существует\n";
        }
        
        // Создаем индексы для оптимизации
        try {
            $this->createIndex('idx-product-series_name', '{{%product}}', 'series_name');
        } catch (\Exception $e) {
            echo "⚠ Индекс idx-product-series_name уже существует\n";
        }
        try {
            $this->createIndex('idx-product-delivery_time', '{{%product}}', ['delivery_time_min', 'delivery_time_max']);
        } catch (\Exception $e) {
            echo "⚠ Индекс idx-product-delivery_time уже существует\n";
        }
        try {
            $this->createIndex('idx-product_size-color_variant', '{{%product_size}}', 'color_variant');
        } catch (\Exception $e) {
            echo "⚠ Индекс idx-product_size-color_variant уже существует\n";
        }
        
        echo "✓ Добавлены поля серии, доставки и цветовых вариантов\n";
    }

    public function safeDown()
    {
        // Удаляем индексы
        $this->dropIndex('idx-product_size-color_variant', '{{%product_size}}');
        $this->dropIndex('idx-product-delivery_time', '{{%product}}');
        $this->dropIndex('idx-product-series_name', '{{%product}}');
        
        // Удаляем поля из product_size
        $this->dropColumn('{{%product_size}}', 'delivery_time_max');
        $this->dropColumn('{{%product_size}}', 'delivery_time_min');
        $this->dropColumn('{{%product_size}}', 'color_variant');
        
        // Удаляем поля из product
        $this->dropColumn('{{%product}}', 'related_products_json');
        $this->dropColumn('{{%product}}', 'delivery_time_max');
        $this->dropColumn('{{%product}}', 'delivery_time_min');
        $this->dropColumn('{{%product}}', 'series_name');
        
        echo "✓ Удалены поля серии, доставки и цветовых вариантов\n";
    }
}
