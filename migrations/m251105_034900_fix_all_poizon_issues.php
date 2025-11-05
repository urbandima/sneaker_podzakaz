<?php

use yii\db\Migration;

/**
 * Исправление всех проблем с импортом Poizon
 */
class m251105_034900_fix_all_poizon_issues extends Migration
{
    public function safeUp()
    {
        // 1. Добавляем sort_order в product_size (критическая ошибка)
        $this->addColumn('{{%product_size}}', 'sort_order', $this->integer()->defaultValue(0)->after('id'));
        $this->createIndex('idx_product_size_sort', '{{%product_size}}', 'sort_order');
        
        // 2. Добавляем цены в product_size
        $this->addColumn('{{%product_size}}', 'price_cny', $this->decimal(10, 2)->after('price')->comment('Цена в юанях'));
        $this->addColumn('{{%product_size}}', 'price_byn', $this->decimal(10, 2)->after('price_cny')->comment('Цена в BYN (для магазина)'));
        $this->addColumn('{{%product_size}}', 'price_client_byn', $this->decimal(10, 2)->after('price_byn')->comment('Цена для клиента в BYN'));
        
        return true;
    }

    public function safeDown()
    {
        $this->dropIndex('idx_product_size_sort', '{{%product_size}}');
        $this->dropColumn('{{%product_size}}', 'sort_order');
        
        $this->dropColumn('{{%product_size}}', 'price_cny');
        $this->dropColumn('{{%product_size}}', 'price_byn');
        $this->dropColumn('{{%product_size}}', 'price_client_byn');
        
        return true;
    }
}
