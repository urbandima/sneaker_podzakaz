<?php

use yii\db\Migration;

/**
 * catalog\models\CatalogInquiry — полноценная модель (не заглушка) для "быстрого заказа"
 * с карточки товара (CatalogController::actionCreateInquiry, создаёт Order напрямую),
 * но таблица catalog_inquiry никогда не создавалась ни одной миграцией — 100% реальных
 * обращений к /catalog/create-inquiry падали с "Table 'catalog_inquiry' doesn't exist".
 * Найдено при живом прогоне CMP-410.
 */
class m260921_141500_create_catalog_inquiry_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%catalog_inquiry}}', true) !== null) {
            echo "    > skip: {{%catalog_inquiry}} already exists\n";
            return;
        }

        $this->createTable('{{%catalog_inquiry}}', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'phone' => $this->string(50)->notNull(),
            'email' => $this->string(255)->null(),
            'message' => $this->text()->null(),
            'size' => $this->string(20)->null(),
            'color' => $this->string(100)->null(),
            'status' => $this->string(20)->notNull()->defaultValue('new'),
            'created_at' => $this->dateTime()->null(),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx_catalog_inquiry_product', '{{%catalog_inquiry}}', 'product_id');
        $this->createIndex('idx_catalog_inquiry_status', '{{%catalog_inquiry}}', 'status');

        $this->addForeignKey(
            'fk_catalog_inquiry_product',
            '{{%catalog_inquiry}}',
            'product_id',
            '{{%product}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%catalog_inquiry}}');
    }
}
