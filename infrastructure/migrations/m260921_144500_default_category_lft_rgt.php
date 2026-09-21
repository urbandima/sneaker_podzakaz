<?php

use yii\db\Migration;

/**
 * category.lft/rgt — NOT NULL без дефолта, но нигде в коде не читаются и не пишутся
 * (Category не использует NestedSetsBehavior и не сортирует по ним ни в одном запросе —
 * похоже на брошенную попытку вложенного множества). Из-за отсутствия значения по
 * умолчанию любое создание категории падало с "Field 'lft' doesn't have a default value".
 * Найдено при живом прогоне CMP-410.
 */
class m260921_144500_default_category_lft_rgt extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%category}}', true);
        if ($schema === null || !isset($schema->columns['lft'])) {
            echo "    > skip: {{%category}}.lft does not exist\n";
            return;
        }

        $this->alterColumn('{{%category}}', 'lft', $this->integer()->notNull()->defaultValue(0));
        $this->alterColumn('{{%category}}', 'rgt', $this->integer()->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%category}}', true);
        if ($schema === null || !isset($schema->columns['lft'])) {
            return;
        }

        $this->alterColumn('{{%category}}', 'lft', $this->integer()->notNull());
        $this->alterColumn('{{%category}}', 'rgt', $this->integer()->notNull());
    }
}
