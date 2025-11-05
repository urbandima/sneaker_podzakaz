<?php

use yii\db\Migration;

/**
 * Добавление poizon_id для Category и Brand
 */
class m251104_220100_add_poizon_id_to_category_brand extends Migration
{
    public function safeUp()
    {
        // Category
        $this->addColumn('{{%category}}', 'poizon_id', $this->integer()->comment('ID категории в Poizon'));
        $this->createIndex('idx-category-poizon_id', '{{%category}}', 'poizon_id');
        
        // Brand
        $this->addColumn('{{%brand}}', 'poizon_id', $this->integer()->comment('ID бренда в Poizon'));
        $this->createIndex('idx-brand-poizon_id', '{{%brand}}', 'poizon_id');
        
        echo "✅ Добавлены poizon_id для Category и Brand\n";
    }

    public function safeDown()
    {
        $this->dropIndex('idx-brand-poizon_id', '{{%brand}}');
        $this->dropColumn('{{%brand}}', 'poizon_id');
        
        $this->dropIndex('idx-category-poizon_id', '{{%category}}');
        $this->dropColumn('{{%category}}', 'poizon_id');
    }
}
