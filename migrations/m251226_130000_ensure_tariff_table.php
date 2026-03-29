<?php

use yii\db\Migration;

/**
 * Ensures tariff table exists with required columns.
 */
class m251226_130000_ensure_tariff_table extends Migration
{
    private string $table = '{{%tariff}}';

    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema($this->table, true);

        if ($schema === null) {
            $this->createTable($this->table, [
                'id' => $this->primaryKey(),
                'name' => $this->string(100)->notNull(),
                'description' => $this->text(),
                'commission_percent' => $this->decimal(6, 2)->notNull()->defaultValue(0),
                'commission_fixed' => $this->decimal(10, 2)->notNull()->defaultValue(0),
                'delivery_cost_per_kg' => $this->decimal(10, 2)->notNull()->defaultValue(0),
                'insurance_percent' => $this->decimal(6, 2)->notNull()->defaultValue(0),
                'min_order_amount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
                'currency' => $this->string(3)->notNull()->defaultValue('CNY'),
                'exchange_rate' => $this->decimal(12, 4)->notNull()->defaultValue(1),
                'is_active' => $this->boolean()->notNull()->defaultValue(true),
                'sort_order' => $this->integer()->notNull()->defaultValue(100),
                'created_at' => $this->integer(),
                'updated_at' => $this->integer(),
            ]);

            $this->createIndex('idx-tariff-sort_order', $this->table, 'sort_order');
            $this->createIndex('idx-tariff-is_active', $this->table, 'is_active');
        } else {
            $this->ensureColumn('name', $this->string(100)->notNull());
            $this->ensureColumn('description', $this->text());
            $this->ensureColumn('commission_percent', $this->decimal(6, 2)->notNull()->defaultValue(0));
            $this->ensureColumn('commission_fixed', $this->decimal(10, 2)->notNull()->defaultValue(0));
            $this->ensureColumn('delivery_cost_per_kg', $this->decimal(10, 2)->notNull()->defaultValue(0));
            $this->ensureColumn('insurance_percent', $this->decimal(6, 2)->notNull()->defaultValue(0));
            $this->ensureColumn('min_order_amount', $this->decimal(12, 2)->notNull()->defaultValue(0));
            $this->ensureColumn('currency', $this->string(3)->notNull()->defaultValue('CNY'));
            $this->ensureColumn('exchange_rate', $this->decimal(12, 4)->notNull()->defaultValue(1));
            $this->ensureColumn('is_active', $this->boolean()->notNull()->defaultValue(true));
            $this->ensureColumn('sort_order', $this->integer()->notNull()->defaultValue(100));
            $this->ensureColumn('created_at', $this->integer());
            $this->ensureColumn('updated_at', $this->integer());

            $this->ensureIndex('idx-tariff-sort_order', 'sort_order');
            $this->ensureIndex('idx-tariff-is_active', 'is_active');
        }
    }

    public function safeDown()
    {
        echo "m251226_130000_ensure_tariff_table cannot be reverted.\n";
        return false;
    }

    private function ensureColumn(string $column, $definition): void
    {
        $schema = $this->db->schema->getTableSchema($this->table, true);
        if ($schema === null || $schema->getColumn($column) !== null) {
            return;
        }

        $this->addColumn($this->table, $column, $definition);
    }

    private function ensureIndex(string $name, string $column): void
    {
        $indexes = $this->db->schema->getTableIndexes($this->table, true);
        foreach ($indexes as $index) {
            if ($index['name'] === $name) {
                return;
            }
        }

        $this->createIndex($name, $this->table, $column);
    }
}
