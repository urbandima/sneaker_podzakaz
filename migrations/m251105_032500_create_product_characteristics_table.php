<?php

use yii\db\Migration;

/**
 * Создание таблицы характеристик товара
 */
class m251105_032500_create_product_characteristics_table extends Migration
{
    public function safeUp()
    {
        // Таблица характеристик товара
        $this->createTable('{{%product_characteristic}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'characteristic_key' => $this->string(100)->notNull()->comment('Ключ характеристики'),
            'characteristic_name' => $this->string(255)->notNull()->comment('Название характеристики'),
            'characteristic_value' => $this->text()->notNull()->comment('Значение'),
            'sort_order' => $this->integer()->defaultValue(0),
            'created_at' => $this->datetime(),
        ]);

        // Индексы
        $this->createIndex('idx_product_characteristic_product_id', '{{%product_characteristic}}', 'product_id');
        $this->createIndex('idx_product_characteristic_key', '{{%product_characteristic}}', 'characteristic_key');
        
        // Внешний ключ
        $this->addForeignKey(
            'fk_product_characteristic_product',
            '{{%product_characteristic}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
        
        // Добавляем недостающие поля в product
        $this->addColumn('{{%product}}', 'vat', $this->string(50)->after('price')->comment('НДС'));
        $this->addColumn('{{%product}}', 'currency', $this->string(10)->defaultValue('BYN')->after('vat')->comment('Валюта'));
        $this->addColumn('{{%product}}', 'related_products', $this->text()->after('keywords')->comment('Похожие товары (JSON)'));
        
        // Добавляем цвет в product_size
        $this->addColumn('{{%product_size}}', 'color', $this->string(100)->after('size')->comment('Цвет варианта'));
        
        return true;
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_product_characteristic_product', '{{%product_characteristic}}');
        $this->dropTable('{{%product_characteristic}}');
        
        $this->dropColumn('{{%product}}', 'vat');
        $this->dropColumn('{{%product}}', 'currency');
        $this->dropColumn('{{%product}}', 'related_products');
        
        $this->dropColumn('{{%product_size}}', 'color');
        
        return true;
    }
}
