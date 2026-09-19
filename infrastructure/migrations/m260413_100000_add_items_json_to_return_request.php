<?php

use yii\db\Migration;

/**
 * Добавляет поле items_json в таблицу return_request
 */
class m260413_100000_add_items_json_to_return_request extends Migration
{
    public function safeUp()
    {
        $existing = $this->db->schema->getTableSchema('{{%return_request}}', true)->columnNames;
        if (in_array('items_json', $existing, true)) {
            echo "    > skipped: {{%return_request}}.items_json уже существует\n";
            return true;
        }

        $this->addColumn('{{%return_request}}', 'items_json', $this->text()->null()->after('comment'));
        echo "✓ Добавлено поле items_json в return_request\n";
    }

    public function safeDown()
    {
        $this->dropColumn('{{%return_request}}', 'items_json');
    }
}
