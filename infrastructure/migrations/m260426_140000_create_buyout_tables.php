<?php

use yii\db\Migration;

/**
 * Создаёт таблицы модуля "Выкупы товаров":
 * buyout, buyout_order_link, buyout_history
 */
class m260426_140000_create_buyout_tables extends Migration
{
    public function safeUp()
    {
        // ── 1. buyout ─────────────────────────────────────────────────────────
        $this->createTable('{{%buyout}}', [
            'id'                 => $this->primaryKey(),
            'source'             => "ENUM('poizon','lamoda','aliexpress','supplier','manual') NOT NULL DEFAULT 'manual'",
            'source_url'         => $this->string(1024)->null()->comment('URL источника'),
            'external_id'        => $this->string(255)->null()->comment('ID товара у источника'),
            'product_id'         => $this->integer()->null()->comment('Ссылка на Product (если сопоставлен)'),
            'product_snapshot'   => $this->json()->null()->comment('Снапшот данных товара на момент выкупа'),
            'size'               => $this->string(30)->null()->comment('Размер'),
            'qty'                => $this->integer()->notNull()->defaultValue(1)->comment('Количество единиц'),
            'unit_cost_source'   => $this->decimal(12, 2)->null()->comment('Цена единицы в валюте источника'),
            'source_currency'    => $this->char(3)->notNull()->defaultValue('CNY')->comment('Валюта источника'),
            'exchange_rate'      => $this->decimal(10, 4)->null()->comment('Курс на момент выкупа'),
            'unit_cost_byn'      => $this->decimal(12, 2)->null()->comment('Цена единицы в BYN'),
            'shipping_cost'      => $this->decimal(12, 2)->null()->defaultValue(0)->comment('Стоимость доставки (BYN)'),
            'fees'               => $this->decimal(12, 2)->null()->defaultValue(0)->comment('Комиссии/пошлины (BYN)'),
            'total_cost_byn'     => $this->decimal(12, 2)->null()->comment('Итого BYN (unit_cost_byn*qty + shipping + fees)'),
            'status'             => "ENUM('draft','ordered','in_transit','arrived','accepted','cancelled','refunded') NOT NULL DEFAULT 'draft'",
            'ordered_at'         => $this->dateTime()->null()->comment('Дата заказа на источнике'),
            'arrived_at'         => $this->dateTime()->null()->comment('Дата прибытия на склад'),
            'accepted_at'        => $this->dateTime()->null()->comment('Дата приёмки'),
            'buyer_user_id'      => $this->integer()->null()->comment('Закупщик — user.id'),
            'notes'              => $this->text()->null()->comment('Внутренние заметки'),
            'receipt_url'        => $this->string(1024)->null()->comment('Чек/инвойс (путь или URL)'),
            'tracking_number'    => $this->string(100)->null()->comment('Трек-номер'),
            'carrier'            => $this->string(100)->null()->comment('Перевозчик'),
            'receiving_id'       => $this->integer()->null()->comment('Ссылка на приёмку (purchase_order.id)'),
            'created_at'         => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'         => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-buyout-status',   '{{%buyout}}', 'status');
        $this->createIndex('idx-buyout-source',   '{{%buyout}}', 'source');
        $this->createIndex('idx-buyout-product',  '{{%buyout}}', 'product_id');
        $this->createIndex('idx-buyout-buyer',    '{{%buyout}}', 'buyer_user_id');
        $this->createIndex('idx-buyout-ordered',  '{{%buyout}}', 'ordered_at');

        // ── 2. buyout_order_link ──────────────────────────────────────────────
        $this->createTable('{{%buyout_order_link}}', [
            'buyout_id'      => $this->integer()->notNull(),
            'order_id'       => $this->integer()->notNull(),
            'order_item_id'  => $this->integer()->null()->comment('NULL = весь заказ'),
            'qty'            => $this->integer()->notNull()->defaultValue(1),
        ]);
        $this->addPrimaryKey('pk-buyout_order_link', '{{%buyout_order_link}}', ['buyout_id', 'order_id', 'order_item_id']);
        $this->createIndex('idx-bol-order',  '{{%buyout_order_link}}', 'order_id');
        $this->createIndex('idx-bol-buyout', '{{%buyout_order_link}}', 'buyout_id');

        // ── 3. buyout_history ─────────────────────────────────────────────────
        $this->createTable('{{%buyout_history}}', [
            'id'         => $this->primaryKey(),
            'buyout_id'  => $this->integer()->notNull(),
            'user_id'    => $this->integer()->null(),
            'action'     => $this->string(100)->notNull()->comment('status_changed, note_added, order_linked, …'),
            'old_value'  => $this->json()->null(),
            'new_value'  => $this->json()->null(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-bh-buyout', '{{%buyout_history}}', 'buyout_id');
        $this->createIndex('idx-bh-user',   '{{%buyout_history}}', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%buyout_history}}');
        $this->dropTable('{{%buyout_order_link}}');
        $this->dropTable('{{%buyout}}');
    }
}
