<?php

use yii\db\Migration;

/**
 * Добавление недостающих полей для полноценной интеграции с Poizon
 * На основе реального JSON экспорта
 */
class m251104_220000_add_missing_poizon_fields extends Migration
{
    public function safeUp()
    {
        // Основные поля из JSON
        $this->addColumn('{{%product}}', 'vendor_code', $this->string(100)->comment('Артикул производителя (vendorCode)'));
        $this->addColumn('{{%product}}', 'country_of_origin', $this->string(100)->comment('Страна происхождения'));
        $this->addColumn('{{%product}}', 'favorite_count', $this->integer()->defaultValue(0)->comment('Количество добавлений в избранное'));
        
        // Характеристики из properties[]
        $this->addColumn('{{%product}}', 'properties', $this->text()->comment('Характеристики товара (JSON)'));
        
        // Размерные сетки из sizes[]
        $this->addColumn('{{%product}}', 'sizes_data', $this->text()->comment('Размерные сетки (JSON)'));
        
        // Закупочная цена и время доставки
        $this->addColumn('{{%product}}', 'purchase_price', $this->decimal(10, 2)->comment('Закупочная цена в BYN'));
        $this->addColumn('{{%product}}', 'delivery_time_min', $this->integer()->comment('Минимальное время доставки (дней)'));
        $this->addColumn('{{%product}}', 'delivery_time_max', $this->integer()->comment('Максимальное время доставки (дней)'));
        
        // Ключевые слова
        $this->addColumn('{{%product}}', 'keywords', $this->text()->comment('Ключевые слова (JSON array)'));
        
        // Series name
        $this->addColumn('{{%product}}', 'series_name', $this->string(255)->comment('Название серии'));
        
        // Поля для вариантов (children)
        $this->addColumn('{{%product}}', 'parent_product_id', $this->integer()->null()->comment('ID родительского товара'));
        $this->addColumn('{{%product}}', 'poizon_variant_id', $this->string(50)->comment('Variant ID в Poizon'));
        $this->addColumn('{{%product}}', 'variant_params', $this->text()->comment('Параметры варианта (JSON) - цвет, размер'));
        $this->addColumn('{{%product}}', 'stock_count', $this->integer()->defaultValue(0)->comment('Количество на складе'));
        
        // Индексы
        $this->createIndex('idx-product-vendor_code', '{{%product}}', 'vendor_code');
        $this->createIndex('idx-product-parent_product_id', '{{%product}}', 'parent_product_id');
        $this->createIndex('idx-product-poizon_variant_id', '{{%product}}', 'poizon_variant_id');
        $this->createIndex('idx-product-purchase_price', '{{%product}}', 'purchase_price');
        
        // Foreign key для parent_product_id
        $this->addForeignKey(
            'fk-product-parent_product_id',
            '{{%product}}',
            'parent_product_id',
            '{{%product}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        echo "✅ Добавлены недостающие поля для Poizon интеграции\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-product-parent_product_id', '{{%product}}');
        
        $this->dropIndex('idx-product-purchase_price', '{{%product}}');
        $this->dropIndex('idx-product-poizon_variant_id', '{{%product}}');
        $this->dropIndex('idx-product-parent_product_id', '{{%product}}');
        $this->dropIndex('idx-product-vendor_code', '{{%product}}');
        
        $this->dropColumn('{{%product}}', 'stock_count');
        $this->dropColumn('{{%product}}', 'variant_params');
        $this->dropColumn('{{%product}}', 'poizon_variant_id');
        $this->dropColumn('{{%product}}', 'parent_product_id');
        $this->dropColumn('{{%product}}', 'series_name');
        $this->dropColumn('{{%product}}', 'keywords');
        $this->dropColumn('{{%product}}', 'delivery_time_max');
        $this->dropColumn('{{%product}}', 'delivery_time_min');
        $this->dropColumn('{{%product}}', 'purchase_price');
        $this->dropColumn('{{%product}}', 'sizes_data');
        $this->dropColumn('{{%product}}', 'properties');
        $this->dropColumn('{{%product}}', 'favorite_count');
        $this->dropColumn('{{%product}}', 'country_of_origin');
        $this->dropColumn('{{%product}}', 'vendor_code');
        
        echo "✅ Откачены изменения\n";
    }
}
