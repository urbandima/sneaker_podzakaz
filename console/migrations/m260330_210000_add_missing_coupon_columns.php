<?php

use yii\db\Migration;

/**
 * Добавление недостающих полей в таблицу coupon
 */
class m260330_210000_add_missing_coupon_columns extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->schema->getTableSchema('{{%coupon}}');
        if (!$tableSchema) {
            echo "✗ Таблица coupon не найдена\n";
            return;
        }

        $added = [];

        if (!isset($tableSchema->columns['name'])) {
            $this->addColumn('{{%coupon}}', 'name', $this->string(255)->notNull()->defaultValue('')->after('code'));
            $added[] = 'name';
        }

        if (!isset($tableSchema->columns['current_uses'])) {
            $this->addColumn('{{%coupon}}', 'current_uses', $this->integer()->notNull()->defaultValue(0));
            $added[] = 'current_uses';
        }

        if (!isset($tableSchema->columns['max_uses'])) {
            $this->addColumn('{{%coupon}}', 'max_uses', $this->integer());
            $added[] = 'max_uses';
        }

        if (!isset($tableSchema->columns['is_active'])) {
            $this->addColumn('{{%coupon}}', 'is_active', $this->boolean()->notNull()->defaultValue(true));
            $added[] = 'is_active';
        }

        if (!isset($tableSchema->columns['valid_until'])) {
            $this->addColumn('{{%coupon}}', 'valid_until', $this->dateTime());
            $added[] = 'valid_until';
        }

        if (empty($added)) {
            echo "ℹ Все колонки уже существуют\n";
        } else {
            echo "✓ Добавлены колонки: " . implode(', ', $added) . "\n";
        }
    }

    public function safeDown()
    {
        echo "ℹ safeDown пропущен\n";
    }
}
