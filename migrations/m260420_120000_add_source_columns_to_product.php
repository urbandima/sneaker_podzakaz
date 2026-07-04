<?php

use yii\db\Migration;

/**
 * Добавляет колонки source_url и source в таблицу product
 * для дедупликации при парсинге внешних источников (Lamoda и др.)
 */
class m260420_120000_add_source_columns_to_product extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%product}}', 'source_url', $this->string(500)->null()->comment('URL источника (для дедупликации)'));
        $this->addColumn('{{%product}}', 'source', $this->string(50)->null()->comment('Источник товара (lamoda, poizon, manual)'));

        $this->createIndex('idx-product-source_url', '{{%product}}', 'source_url');
        $this->createIndex('idx-product-source', '{{%product}}', 'source');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-product-source', '{{%product}}');
        $this->dropIndex('idx-product-source_url', '{{%product}}');
        $this->dropColumn('{{%product}}', 'source');
        $this->dropColumn('{{%product}}', 'source_url');
    }
}
