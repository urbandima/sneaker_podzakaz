<?php

use yii\db\Migration;

class m260427_000001_supplier_grouping_fields extends Migration
{
    public function safeUp()
    {
        // Create supplier table if it doesn't exist
        if (!$this->db->schema->getTableSchema('{{%supplier}}')) {
            $this->createTable('{{%supplier}}', [
                'id'             => $this->primaryKey(),
                'name'           => $this->string(255)->notNull(),
                'contact_person' => $this->string(255)->null(),
                'phone'          => $this->string(50)->null(),
                'email'          => $this->string(255)->null(),
                'address'        => $this->text()->null(),
                'country'        => $this->string(2)->null(),
                'region'         => $this->string(64)->null(),
                'payment_terms'  => $this->text()->null(),
                'notes'          => $this->text()->null(),
                'brands'         => $this->text()->null()->comment('JSON array of brand_ids'),
                'contract_type'  => "ENUM('commission','stock','to_order','mixed') NULL",
                'contract_terms' => $this->text()->null(),
                'is_active'      => $this->tinyInteger(1)->defaultValue(1),
                'created_at'     => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
        } else {
            // Table exists — add new columns if missing
            $columns = array_keys($this->db->schema->getTableSchema('{{%supplier}}')->columns);

            if (!in_array('brands', $columns)) {
                $this->addColumn('{{%supplier}}', 'brands', $this->text()->null()->comment('JSON array of brand_ids') . ' AFTER `country`');
            }
            if (!in_array('region', $columns)) {
                $this->addColumn('{{%supplier}}', 'region', $this->string(64)->null() . ' AFTER `brands`');
            }
            if (!in_array('contract_type', $columns)) {
                $this->execute("ALTER TABLE {{%supplier}} ADD COLUMN `contract_type` ENUM('commission','stock','to_order','mixed') NULL AFTER `region`");
            }
            if (!in_array('contract_terms', $columns)) {
                $this->addColumn('{{%supplier}}', 'contract_terms', $this->text()->null() . ' AFTER `contract_type`');
            }

            // Resize country to VARCHAR(2) for ISO codes
            $this->execute("ALTER TABLE {{%supplier}} MODIFY COLUMN `country` VARCHAR(2) NULL");
        }

        // Create purchase_order table if missing
        if (!$this->db->schema->getTableSchema('{{%purchase_order}}')) {
            $this->createTable('{{%purchase_order}}', [
                'id'              => $this->primaryKey(),
                'purchase_number' => $this->string(50)->notNull()->unique(),
                'supplier_id'     => $this->integer()->null(),
                'order_type'      => $this->string(50)->defaultValue('stock'),
                'status'          => $this->string(50)->defaultValue('draft'),
                'exchange_rate'   => $this->decimal(10, 4)->null(),
                'order_id'        => $this->integer()->null(),
                'total_cny'       => $this->decimal(12, 2)->defaultValue(0),
                'total_byn'       => $this->decimal(12, 2)->defaultValue(0),
                'notes'           => $this->text()->null(),
                'ordered_at'      => $this->dateTime()->null(),
                'received_at'     => $this->dateTime()->null(),
                'created_by'      => $this->integer()->null(),
                'created_at'      => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
        }

        // Create purchase_order_item table if missing
        if (!$this->db->schema->getTableSchema('{{%purchase_order_item}}')) {
            $this->createTable('{{%purchase_order_item}}', [
                'id'                  => $this->primaryKey(),
                'purchase_order_id'   => $this->integer()->notNull(),
                'product_name'        => $this->string(255)->notNull(),
                'size'                => $this->string(50)->null(),
                'quantity'            => $this->integer()->defaultValue(1),
                'received_quantity'   => $this->integer()->defaultValue(0),
                'price_cny'           => $this->decimal(10, 2)->null(),
                'price_byn'           => $this->decimal(10, 2)->null(),
            ]);
        }

        // Create supplier_return table if missing
        if (!$this->db->schema->getTableSchema('{{%supplier_return}}')) {
            $this->createTable('{{%supplier_return}}', [
                'id'                => $this->primaryKey(),
                'return_number'     => $this->string(50)->notNull()->unique(),
                'purchase_order_id' => $this->integer()->null(),
                'supplier_id'       => $this->integer()->null(),
                'status'            => $this->string(50)->defaultValue('draft'),
                'reason'            => $this->text()->null(),
                'notes'             => $this->text()->null(),
                'total_amount'      => $this->decimal(12, 2)->defaultValue(0),
                'created_by'        => $this->integer()->null(),
                'created_at'        => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]);
        }

        // Create supplier_return_item table if missing
        if (!$this->db->schema->getTableSchema('{{%supplier_return_item}}')) {
            $this->createTable('{{%supplier_return_item}}', [
                'id'                     => $this->primaryKey(),
                'supplier_return_id'     => $this->integer()->notNull(),
                'product_name'           => $this->string(255)->notNull(),
                'size'                   => $this->string(50)->null(),
                'quantity'               => $this->integer()->defaultValue(1),
                'price_byn'              => $this->decimal(10, 2)->null(),
                'reason'                 => $this->text()->null(),
                'purchase_order_item_id' => $this->integer()->null(),
            ]);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%supplier_return_item}}');
        $this->dropTable('{{%supplier_return}}');
        $this->dropTable('{{%purchase_order_item}}');
        $this->dropTable('{{%purchase_order}}');
        $this->dropTable('{{%supplier}}');
    }
}
