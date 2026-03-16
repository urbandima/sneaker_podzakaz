<?php

use yii\db\Migration;

/**
 * Добавление полей для вариантов размеров:
 * - variant_vendor_code: артикул варианта из Poizon
 * - images_json: JSON массив изображений варианта
 */
class m251106_070000_add_variant_fields_to_product_size extends Migration
{
    public function safeUp()
    {
        // Проверяем, существует ли уже колонка variant_vendor_code
        $tableSchema = $this->db->getTableSchema('{{%product_size}}');
        
        if (!isset($tableSchema->columns['variant_vendor_code'])) {
            $this->addColumn('{{%product_size}}', 'variant_vendor_code', $this->string(100)->after('poizon_sku_id')->comment('Артикул варианта (vendorCode из children)'));
        }
        
        if (!isset($tableSchema->columns['images_json'])) {
            $this->addColumn('{{%product_size}}', 'images_json', $this->text()->after('variant_vendor_code')->comment('JSON массив изображений варианта'));
        }
        
        // Индекс для быстрого поиска по артикулу варианта
        try {
            $this->createIndex('idx_variant_vendor_code', '{{%product_size}}', 'variant_vendor_code');
        } catch (\Exception $e) {
            echo "⚠ Индекс idx_variant_vendor_code уже существует\n";
        }
        
        echo "✅ Добавлены поля variant_vendor_code и images_json в product_size\n";
    }

    public function safeDown()
    {
        $this->dropIndex('idx_variant_vendor_code', '{{%product_size}}');
        $this->dropColumn('{{%product_size}}', 'images_json');
        $this->dropColumn('{{%product_size}}', 'variant_vendor_code');
    }
}
