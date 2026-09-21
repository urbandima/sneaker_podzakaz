<?php

use yii\db\Migration;

/**
 * AUDIT-22: Версионирует колонки MoySklad-интеграции на product/product_size,
 * которые раньше добавлялись "на лету" через ALTER TABLE прямо внутри
 * одноразового скрипта scripts/import_from_moysklad.php (функции
 * ensureMoyskladId()/ensureColumn()/ensureMoyskladExtra(), вызываемые из
 * setupSchema() для таблиц 'product' и 'product_size').
 *
 * Список колонок и их типы взяты 1:1 из setupSchema() в
 * scripts/import_from_moysklad.php (строки ~495-559 до правки AUDIT-22).
 * После этой миграции DDL для 'product'/'product_size' убран из скрипта —
 * он должен полагаться на то, что колонки уже существуют.
 */
class m260704_210000_add_moysklad_fields_to_product_and_product_size extends Migration
{
    /**
     * @return array<string, array{0:string,1:string|null}> column => [definition, afterColumn|null]
     */
    private function productColumns(): array
    {
        return [
            'moysklad_id'             => ["VARCHAR(36) NULL DEFAULT NULL", null],
            'ms_code'                 => ["VARCHAR(100) NULL DEFAULT NULL", null],
            'ms_external_code'        => ["VARCHAR(100) NULL DEFAULT NULL", null],
            'barcode'                 => ["VARCHAR(255) NULL DEFAULT NULL", null],
            'barcodes_json'           => ["JSON NULL DEFAULT NULL", null],
            'ms_volume'               => ["DECIMAL(10,4) NULL DEFAULT NULL", null],
            'uom_name'                => ["VARCHAR(50) NULL DEFAULT NULL", null],
            'ms_images_json'          => ["JSON NULL DEFAULT NULL", null],
            'ms_attributes_json'      => ["JSON NULL DEFAULT NULL", null],
            'ms_characteristics_json' => ["JSON NULL DEFAULT NULL", null],
            'ms_min_price'            => ["DECIMAL(10,2) NULL DEFAULT NULL", null],
            'ms_supplier_name'        => ["VARCHAR(255) NULL DEFAULT NULL", null],
            'ms_archived'             => ["TINYINT(1) NULL DEFAULT 0", null],
            'ms_no_export'            => ["TINYINT(1) NULL DEFAULT 0", null],
            'is_sale'                 => ["TINYINT(1) NULL DEFAULT 0", null],
            'ms_size_grid'            => ["VARCHAR(100) NULL DEFAULT NULL", null],
            'ms_purpose'              => ["VARCHAR(100) NULL DEFAULT NULL", null],
            'ms_sole_height'          => ["VARCHAR(100) NULL DEFAULT NULL", null],
            'ms_sole_color'           => ["VARCHAR(255) NULL DEFAULT NULL", null],
            'ms_inner_material'       => ["VARCHAR(255) NULL DEFAULT NULL", null],
            'ms_site_link'            => ["VARCHAR(500) NULL DEFAULT NULL", null],
            'ms_price_full'           => ["DECIMAL(10,2) NULL DEFAULT NULL", null],
            'ms_price_rub'            => ["DECIMAL(10,2) NULL DEFAULT NULL", null],
            'ms_price_sale'           => ["DECIMAL(10,2) NULL DEFAULT NULL", null],
            'ms_price_competitor'     => ["DECIMAL(10,2) NULL DEFAULT NULL", null],
            'ms_path_name'            => ["VARCHAR(255) NULL DEFAULT NULL", null],
            'moysklad_extra'          => ["JSON NULL DEFAULT NULL", null],
        ];
    }

    /**
     * @return array<string, string> column => definition
     */
    private function productSizeColumns(): array
    {
        return [
            'ms_variant_id'        => "VARCHAR(36) NULL DEFAULT NULL",
            'ms_barcode'           => "VARCHAR(255) NULL DEFAULT NULL",
            'ms_code'              => "VARCHAR(100) NULL DEFAULT NULL",
            'ms_external_code'     => "VARCHAR(100) NULL DEFAULT NULL",
            'characteristics_json' => "JSON NULL DEFAULT NULL",
            'moysklad_extra'       => "JSON NULL DEFAULT NULL",
        ];
    }

    public function safeUp()
    {
        // ── product ──────────────────────────────────────────────────────────
        $schema = $this->db->getTableSchema('{{%product}}', true);
        if ($schema === null) {
            echo "    > skip: {{%product}} table does not exist yet\n";
        } else {
            foreach ($this->productColumns() as $column => [$definition, $after]) {
                if ($schema->getColumn($column) === null) {
                    $sql = "ALTER TABLE {{%product}} ADD COLUMN `{$column}` {$definition}";
                    if ($after) {
                        $sql .= " AFTER `{$after}`";
                    }
                    $this->execute($sql);
                }
            }

            // Индекс на moysklad_id — как делал ensureMoyskladId() в скрипте импорта
            $indexes = $this->db->schema->getTableIndexes('{{%product}}');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index->name === 'idx_ms_product') {
                    $hasIndex = true;
                    break;
                }
            }
            if (!$hasIndex) {
                $this->createIndex('idx_ms_product', '{{%product}}', 'moysklad_id');
            }
        }

        // ── product_size ─────────────────────────────────────────────────────
        $sizeSchema = $this->db->getTableSchema('{{%product_size}}', true);
        if ($sizeSchema === null) {
            echo "    > skip: {{%product_size}} table does not exist yet\n";
        } else {
            foreach ($this->productSizeColumns() as $column => $definition) {
                if ($sizeSchema->getColumn($column) === null) {
                    $this->execute("ALTER TABLE {{%product_size}} ADD COLUMN `{$column}` {$definition}");
                }
            }

            $sizeIndexes = $this->db->schema->getTableIndexes('{{%product_size}}');
            $hasVariantIndex = false;
            foreach ($sizeIndexes as $index) {
                if ($index->name === 'idx_ms_variant') {
                    $hasVariantIndex = true;
                    break;
                }
            }
            if (!$hasVariantIndex) {
                $this->createIndex('idx_ms_variant', '{{%product_size}}', 'ms_variant_id');
            }
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%product}}', true);
        if ($schema !== null) {
            try {
                $this->dropIndex('idx_ms_product', '{{%product}}');
            } catch (\Throwable $e) {
            }
            foreach (array_keys($this->productColumns()) as $column) {
                if ($schema->getColumn($column) !== null) {
                    $this->dropColumn('{{%product}}', $column);
                }
            }
        }

        $sizeSchema = $this->db->getTableSchema('{{%product_size}}', true);
        if ($sizeSchema !== null) {
            try {
                $this->dropIndex('idx_ms_variant', '{{%product_size}}');
            } catch (\Throwable $e) {
            }
            foreach (array_keys($this->productSizeColumns()) as $column) {
                if ($sizeSchema->getColumn($column) !== null) {
                    $this->dropColumn('{{%product_size}}', $column);
                }
            }
        }
    }
}
