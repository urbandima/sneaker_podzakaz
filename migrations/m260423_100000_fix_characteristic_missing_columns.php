<?php
class m260423_100000_fix_characteristic_missing_columns extends \yii\db\Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema;

        // characteristic table
        if (!$schema->getTableSchema('{{%characteristic}}')->getColumn('key')) {
            $this->addColumn('{{%characteristic}}', 'key', $this->string(100)->null()->after('name'));
        }
        if (!$schema->getTableSchema('{{%characteristic}}')->getColumn('is_required')) {
            $this->addColumn('{{%characteristic}}', 'is_required', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('is_filter'));
        }

        // characteristic_value table
        $cvTable = $schema->getTableSchema('{{%characteristic_value}}');
        if (!$cvTable->getColumn('slug')) {
            $this->addColumn('{{%characteristic_value}}', 'slug', $this->string(200)->null()->after('value'));
        }
        if (!$cvTable->getColumn('is_active')) {
            $this->addColumn('{{%characteristic_value}}', 'is_active', $this->tinyInteger(1)->notNull()->defaultValue(1)->after('slug'));
        }
        if (!$cvTable->getColumn('updated_at')) {
            $this->addColumn('{{%characteristic_value}}', 'updated_at', $this->timestamp()->null());
        }
    }

    public function safeDown()
    {
        // Columns added for compatibility — safe to keep
    }
}
