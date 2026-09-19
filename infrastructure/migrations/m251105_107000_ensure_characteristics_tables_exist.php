<?php

use yii\db\Migration;

/**
 * Безопасное создание таблиц системы характеристик
 */
class m251105_107000_ensure_characteristics_tables_exist extends Migration
{
    public function safeUp()
    {
        echo "📝 Проверка таблиц характеристик...\n";

        // 1. Таблица characteristic
        if (!$this->db->schema->getTableSchema('{{%characteristic}}', true)) {
            echo "  Создание таблицы characteristic...\n";

            $this->createTable('{{%characteristic}}', [
                'id' => $this->primaryKey(),
                'key' => $this->string(100)->notNull()->unique()->comment('Ключ характеристики'),
                'name' => $this->string(255)->notNull()->comment('Название'),
                'type' => "ENUM('select', 'multiselect', 'text', 'number', 'boolean') DEFAULT 'select'",
                'is_filter' => $this->boolean()->defaultValue(0),
                'is_required' => $this->boolean()->defaultValue(0),
                'sort_order' => $this->integer()->defaultValue(0),
                'is_active' => $this->boolean()->defaultValue(1),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ]);

            $this->createIndex('idx_characteristic_key', '{{%characteristic}}', 'key');
            $this->createIndex('idx_characteristic_active', '{{%characteristic}}', 'is_active');

            echo "  ✓ Таблица characteristic создана\n";
        } else {
            echo "  ✓ Таблица characteristic уже существует\n";
        }

        // 2. Таблица characteristic_value
        if (!$this->db->schema->getTableSchema('{{%characteristic_value}}', true)) {
            echo "  Создание таблицы characteristic_value...\n";

            $this->createTable('{{%characteristic_value}}', [
                'id' => $this->primaryKey(),
                'characteristic_id' => $this->integer()->notNull(),
                'value' => $this->string(255)->notNull(),
                'slug' => $this->string(255)->notNull(),
                'sort_order' => $this->integer()->defaultValue(0),
                'is_active' => $this->boolean()->defaultValue(1),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ]);

            $this->createIndex('idx_characteristic_value_char_id', '{{%characteristic_value}}', 'characteristic_id');
            $this->createIndex('idx_characteristic_value_slug', '{{%characteristic_value}}', 'slug');

            $this->createForeignKeyIfNotExists(
                'fk_characteristic_value_characteristic',
                '{{%characteristic_value}}',
                'characteristic_id',
                '{{%characteristic}}',
                'id',
                'CASCADE'
            );

            echo "  ✓ Таблица characteristic_value создана\n";
        } else {
            echo "  ✓ Таблица characteristic_value уже существует\n";
        }

        // 3. Таблица product_characteristic_value
        if (!$this->db->schema->getTableSchema('{{%product_characteristic_value}}', true)) {
            echo "  Создание таблицы product_characteristic_value...\n";

            $this->createTable('{{%product_characteristic_value}}', [
                'id' => $this->primaryKey(),
                'product_id' => $this->integer()->notNull(),
                'characteristic_id' => $this->integer()->notNull(),
                'characteristic_value_id' => $this->integer()->null(),
                'value_text' => $this->text()->null(),
                'value_number' => $this->decimal(10, 2)->null(),
                'value_boolean' => $this->boolean()->null(),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);

            $this->createIndex('idx_pcv_product', '{{%product_characteristic_value}}', 'product_id');
            $this->createIndex('idx_pcv_characteristic', '{{%product_characteristic_value}}', 'characteristic_id');
            $this->createIndex('idx_pcv_value', '{{%product_characteristic_value}}', 'characteristic_value_id');

            $this->createForeignKeyIfNotExists(
                'fk_pcv_product',
                '{{%product_characteristic_value}}',
                'product_id',
                '{{%product}}',
                'id',
                'CASCADE'
            );

            $this->createForeignKeyIfNotExists(
                'fk_pcv_characteristic',
                '{{%product_characteristic_value}}',
                'characteristic_id',
                '{{%characteristic}}',
                'id',
                'CASCADE'
            );

            $this->createForeignKeyIfNotExists(
                'fk_pcv_characteristic_value',
                '{{%product_characteristic_value}}',
                'characteristic_value_id',
                '{{%characteristic_value}}',
                'id',
                'CASCADE'
            );

            echo "  ✓ Таблица product_characteristic_value создана\n";
        } else {
            echo "  ✓ Таблица product_characteristic_value уже существует\n";
        }

        echo "✅ Таблицы характеристик готовы\n";
    }

    /**
     * Создание внешнего ключа если его нет
     */
    private function createForeignKeyIfNotExists($name, $table, $column, $refTable, $refColumn, $delete = null)
    {
        try {
            $db = $this->db;
            $tableName = str_replace(['{{%', '}}'], '', $table);
            $tableName = $db->tablePrefix . $tableName;

            $sql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = :table 
                    AND CONSTRAINT_NAME = :name";

            $exists = $db->createCommand($sql, [
                ':table' => $tableName,
                ':name' => $name
            ])->queryScalar();

            if (!$exists) {
                $this->addForeignKey($name, $table, $column, $refTable, $refColumn, $delete);
            }
        } catch (\Exception $e) {
            echo "  ⚠️ FK {$name}: " . $e->getMessage() . "\n";
        }
    }

    public function safeDown()
    {
        echo "Откат не реализован\n";
        return true;
    }
}
