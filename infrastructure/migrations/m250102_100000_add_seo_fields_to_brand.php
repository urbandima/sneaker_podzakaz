<?php

use yii\db\Migration;

/**
 * Добавление SEO полей в таблицу brand
 */
class m250102_100000_add_seo_fields_to_brand extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%brand}}', 'meta_title', $this->string(255)->after('logo'));
        $this->addColumn('{{%brand}}', 'meta_description', $this->text()->after('meta_title'));
        $this->addColumn('{{%brand}}', 'meta_keywords', $this->text()->after('meta_description'));
        $this->addColumn('{{%brand}}', 'sort_order', $this->integer()->defaultValue(0)->after('meta_keywords'));

        echo "✓ Добавлены SEO поля в таблицу brand\n";
    }

    public function safeDown()
    {
        $this->dropColumn('{{%brand}}', 'sort_order');
        $this->dropColumn('{{%brand}}', 'meta_keywords');
        $this->dropColumn('{{%brand}}', 'meta_description');
        $this->dropColumn('{{%brand}}', 'meta_title');
    }
}
