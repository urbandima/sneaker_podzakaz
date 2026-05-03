<?php

use yii\db\Migration;

/**
 * Repairs the storefront checkout schema (CMP-47).
 *
 * Both OrderItem and OrderHistory drifted ahead of their tables: the
 * models declare attributes in rules() / relations / helper methods that
 * never had backing columns, so any storefront checkout fails with
 * "Setting/Getting unknown property" the moment the AR is touched.
 *
 * order_item adds:
 *   - product_id      (int, indexed) — used by Order::createOrderItem and the getProduct() relation
 *   - product_article (varchar 100)
 *   - color           (varchar 100)
 *
 * order_history adds:
 *   - action, field_name, user_name, user_role, ip_address (varchar)
 *   - old_value, new_value (text) — model uses these names; the older
 *     m260424_120000 migration added `field` instead, but the model was
 *     refactored to `field_name`. The older migration was never applied
 *     to MySQL and is superseded by this one.
 *   - idx_order_history_order_created composite index
 *
 * Idempotent: each column / index is added only if missing.
 */
class m260503_120000_add_product_fields_to_order_item extends Migration
{
    public function safeUp()
    {
        // ---- order_item ----
        $itemCols = array_keys($this->db->schema->getTableSchema('{{%order_item}}', true)->columns);

        if (!in_array('product_id', $itemCols, true)) {
            $this->addColumn('{{%order_item}}', 'product_id', $this->integer()->null()->after('order_id'));
            $this->createIndex('idx-order_item-product_id', '{{%order_item}}', 'product_id');
        }
        if (!in_array('product_article', $itemCols, true)) {
            $this->addColumn('{{%order_item}}', 'product_article', $this->string(100)->null()->after('product_name'));
        }
        if (!in_array('color', $itemCols, true)) {
            $this->addColumn('{{%order_item}}', 'color', $this->string(100)->null()->after('size'));
        }

        // ---- order_history ----
        $histCols = array_keys($this->db->schema->getTableSchema('{{%order_history}}', true)->columns);

        if (!in_array('action', $histCols, true)) {
            $this->addColumn('{{%order_history}}', 'action', $this->string(100)->null()->after('order_id'));
        }
        if (!in_array('field_name', $histCols, true)) {
            $this->addColumn('{{%order_history}}', 'field_name', $this->string(100)->null()->after('action'));
        }
        if (!in_array('old_value', $histCols, true)) {
            $this->addColumn('{{%order_history}}', 'old_value', $this->text()->null()->after('new_status'));
        }
        if (!in_array('new_value', $histCols, true)) {
            $this->addColumn('{{%order_history}}', 'new_value', $this->text()->null()->after('old_value'));
        }
        if (!in_array('user_name', $histCols, true)) {
            $this->addColumn('{{%order_history}}', 'user_name', $this->string(255)->null()->after('changed_by'));
        }
        if (!in_array('user_role', $histCols, true)) {
            $this->addColumn('{{%order_history}}', 'user_role', $this->string(255)->null()->after('user_name'));
        }
        if (!in_array('ip_address', $histCols, true)) {
            $this->addColumn('{{%order_history}}', 'ip_address', $this->string(45)->null()->after('user_role'));
        }

        $indexes = array_column(
            $this->db->createCommand('SHOW INDEX FROM {{%order_history}}')->queryAll(),
            'Key_name'
        );
        if (!in_array('idx_order_history_order_created', $indexes, true)) {
            $this->createIndex('idx_order_history_order_created', '{{%order_history}}', ['order_id', 'created_at']);
        }
    }

    public function safeDown()
    {
        // order_history
        $histCols = array_keys($this->db->schema->getTableSchema('{{%order_history}}', true)->columns);
        $indexes  = array_column(
            $this->db->createCommand('SHOW INDEX FROM {{%order_history}}')->queryAll(),
            'Key_name'
        );

        if (in_array('idx_order_history_order_created', $indexes, true)) {
            $this->dropIndex('idx_order_history_order_created', '{{%order_history}}');
        }
        foreach (['ip_address', 'user_role', 'user_name', 'new_value', 'old_value', 'field_name', 'action'] as $col) {
            if (in_array($col, $histCols, true)) {
                $this->dropColumn('{{%order_history}}', $col);
            }
        }

        // order_item
        $itemCols = array_keys($this->db->schema->getTableSchema('{{%order_item}}', true)->columns);
        foreach (['color', 'product_article'] as $col) {
            if (in_array($col, $itemCols, true)) {
                $this->dropColumn('{{%order_item}}', $col);
            }
        }
        if (in_array('product_id', $itemCols, true)) {
            try {
                $this->dropIndex('idx-order_item-product_id', '{{%order_item}}');
            } catch (\Throwable $e) {
                // Index may not exist; ignore.
            }
            $this->dropColumn('{{%order_item}}', 'product_id');
        }
    }
}
