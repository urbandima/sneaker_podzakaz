<?php

use yii\db\Migration;

/**
 * Добавление полей для интеграции с Poizon/Dewu
 */
class m251104_120000_add_poizon_fields_to_product extends Migration
{
    public function safeUp()
    {
        // Добавляем поля для интеграции с Poizon
        $this->addColumn('{{%product}}', 'sku', $this->string(100)->unique()->comment('Уникальный SKU товара'));
        $this->addColumn('{{%product}}', 'poizon_id', $this->string(50)->comment('ID товара в Poizon'));
        $this->addColumn('{{%product}}', 'poizon_spu_id', $this->string(50)->comment('SPU ID в Poizon'));
        $this->addColumn('{{%product}}', 'poizon_url', $this->string(500)->comment('URL товара на Poizon'));
        $this->addColumn('{{%product}}', 'poizon_price_cny', $this->decimal(10, 2)->comment('Цена в CNY на Poizon'));
        $this->addColumn('{{%product}}', 'last_sync_at', $this->timestamp()->null()->comment('Последняя синхронизация'));
        
        // Дополнительные характеристики обуви
        $this->addColumn('{{%product}}', 'upper_material', $this->string(100)->comment('Материал верха'));
        $this->addColumn('{{%product}}', 'sole_material', $this->string(100)->comment('Материал подошвы'));
        $this->addColumn('{{%product}}', 'color_description', $this->string(255)->comment('Описание цвета'));
        $this->addColumn('{{%product}}', 'style_code', $this->string(100)->comment('Код стиля/модели'));
        $this->addColumn('{{%product}}', 'release_year', $this->integer()->comment('Год выпуска'));
        $this->addColumn('{{%product}}', 'is_limited', $this->boolean()->defaultValue(0)->comment('Лимитированная модель'));
        $this->addColumn('{{%product}}', 'weight', $this->integer()->comment('Вес в граммах'));
        
        // Индексы для производительности
        $this->createIndex('idx-product-sku', '{{%product}}', 'sku');
        $this->createIndex('idx-product-poizon_id', '{{%product}}', 'poizon_id');
        $this->createIndex('idx-product-poizon_spu_id', '{{%product}}', 'poizon_spu_id');
        $this->createIndex('idx-product-last_sync_at', '{{%product}}', 'last_sync_at');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-product-last_sync_at', '{{%product}}');
        $this->dropIndex('idx-product-poizon_spu_id', '{{%product}}');
        $this->dropIndex('idx-product-poizon_id', '{{%product}}');
        $this->dropIndex('idx-product-sku', '{{%product}}');
        
        $this->dropColumn('{{%product}}', 'weight');
        $this->dropColumn('{{%product}}', 'is_limited');
        $this->dropColumn('{{%product}}', 'release_year');
        $this->dropColumn('{{%product}}', 'style_code');
        $this->dropColumn('{{%product}}', 'color_description');
        $this->dropColumn('{{%product}}', 'sole_material');
        $this->dropColumn('{{%product}}', 'upper_material');
        $this->dropColumn('{{%product}}', 'last_sync_at');
        $this->dropColumn('{{%product}}', 'poizon_price_cny');
        $this->dropColumn('{{%product}}', 'poizon_url');
        $this->dropColumn('{{%product}}', 'poizon_spu_id');
        $this->dropColumn('{{%product}}', 'poizon_id');
        $this->dropColumn('{{%product}}', 'sku');
    }
}
