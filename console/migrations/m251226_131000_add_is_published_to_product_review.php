<?php

use yii\db\Migration;

/**
 * Ensures product_review table has is_published column to avoid runtime errors.
 */
class m251226_131000_add_is_published_to_product_review extends Migration
{
    private string $table = '{{%product_review}}';

    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema($this->table, true);
        if ($schema === null) {
            echo "product_review table missing; skipping column addition.\n";
            return;
        }

        if ($schema->getColumn('is_published') === null) {
            $this->addColumn($this->table, 'is_published', $this->boolean()->notNull()->defaultValue(false)->after('is_approved'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema($this->table, true);
        if ($schema && $schema->getColumn('is_published') !== null) {
            $this->dropColumn($this->table, 'is_published');
        }
    }
}
