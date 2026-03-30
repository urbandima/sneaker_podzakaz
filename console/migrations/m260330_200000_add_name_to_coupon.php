<?php

use yii\db\Migration;

/**
 * Добавление недостающих полей в таблицу coupon
 */
class m260330_200000_add_name_to_coupon extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->schema->getTableSchema('{{%coupon}}');
        if (!$tableSchema) {
            echo "✗ Таблица coupon не найдена\n";
            return;
        }

        // Добавляем name
        if (!isset($tableSchema->columns['name'])) {
            $this->addColumn('{{%coupon}}', 'name', $this->string(255)->notNull()->defaultValue('')->after('code'));
            echo "✓ Добавлена колонка name\n";
        }

        // Добавляем current_uses
        if (!isset($tableSchema->columns['current_uses'])) {
            $this->addColumn('{{%coupon}}', 'current_uses', $this->integer()->notNull()->defaultValue(0));
            echo "✓ Добавлена колонка current_uses\n";
        }

        // Добавляем max_uses если отсутствует
        if (!isset($tableSchema->columns['max_uses'])) {
            $this->addColumn('{{%coupon}}', 'max_uses', $this->integer());
            echo "✓ Добавлена колонка max_uses\n";
        }

        // Добавляем is_active если отсутствует
        if (!isset($tableSchema->columns['is_active'])) {
            $this->addColumn('{{%coupon}}', 'is_active', $this->boolean()->notNull()->defaultValue(true));
            echo "✓ Добавлена колонка is_active\n";
        }

        // Добавляем valid_until если отсутствует
        if (!isset($tableSchema->columns['valid_until'])) {
            $this->addColumn('{{%coupon}}', 'valid_until', $this->dateTime());
            echo "✓ Добавлена колонка valid_until\n";
        }
    }

    public function safeDown()
    {
        echo "ℹ safeDown пропущен для безопасности\n";
    }
}
