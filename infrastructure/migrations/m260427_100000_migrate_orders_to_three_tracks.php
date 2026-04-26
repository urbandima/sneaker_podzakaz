<?php

use yii\db\Migration;

/**
 * #11 Three-track status model:
 *   payment_status:   not_paid | paid | refunded
 *   logistics_status: awaiting_buyout | bought_at_source | in_transit | at_warehouse
 *   delivery_status:  NULL | ready_to_ship | shipping | delivered
 */
class m260427_100000_migrate_orders_to_three_tracks extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('order');
        $cols   = $schema ? $schema->columnNames : [];

        if (!in_array('payment_status', $cols)) {
            $this->addColumn('order', 'payment_status',
                $this->string(32)->null()->after('status'));
        }
        if (!in_array('logistics_status', $cols)) {
            $this->addColumn('order', 'logistics_status',
                $this->string(32)->null()->after('payment_status'));
        }
        if (!in_array('delivery_status', $cols)) {
            $this->addColumn('order', 'delivery_status',
                $this->string(32)->null()->after('logistics_status'));
        }

        $this->createIndex('idx_order_payment_status',   'order', 'payment_status');
        $this->createIndex('idx_order_logistics_status', 'order', 'logistics_status');
        $this->createIndex('idx_order_delivery_status',  'order', 'delivery_status');

        // Backfill from legacy status column
        $this->execute("
            UPDATE `order` SET
                payment_status = CASE `status`
                    WHEN 'paid'                   THEN 'paid'
                    WHEN 'confirmed_and_paid'     THEN 'paid'
                    WHEN 'ordered'                THEN 'paid'
                    WHEN 'awaiting_buyout'        THEN 'paid'
                    WHEN 'bought_at_source'       THEN 'paid'
                    WHEN 'awaiting_warehouse'     THEN 'paid'
                    WHEN 'international_delivery' THEN 'paid'
                    WHEN 'in_transit_from_source' THEN 'paid'
                    WHEN 'in_transit'             THEN 'paid'
                    WHEN 'arrived_at_warehouse'   THEN 'paid'
                    WHEN 'at_warehouse'           THEN 'paid'
                    WHEN 'ready_to_ship'          THEN 'paid'
                    WHEN 'local_delivery'         THEN 'paid'
                    WHEN 'shipped'                THEN 'paid'
                    WHEN 'delivered'              THEN 'paid'
                    WHEN 'processing'             THEN 'paid'
                    WHEN 'refunded'               THEN 'refunded'
                    WHEN 'return'                 THEN 'refunded'
                    ELSE 'not_paid'
                END,
                logistics_status = CASE `status`
                    WHEN 'ordered'                THEN 'bought_at_source'
                    WHEN 'bought_at_source'       THEN 'bought_at_source'
                    WHEN 'awaiting_warehouse'     THEN 'in_transit'
                    WHEN 'international_delivery' THEN 'in_transit'
                    WHEN 'in_transit_from_source' THEN 'in_transit'
                    WHEN 'in_transit'             THEN 'in_transit'
                    WHEN 'arrived_at_warehouse'   THEN 'at_warehouse'
                    WHEN 'at_warehouse'           THEN 'at_warehouse'
                    WHEN 'ready_to_ship'          THEN 'at_warehouse'
                    WHEN 'local_delivery'         THEN 'at_warehouse'
                    WHEN 'shipped'                THEN 'at_warehouse'
                    WHEN 'delivered'              THEN 'at_warehouse'
                    ELSE 'awaiting_buyout'
                END,
                delivery_status = CASE `status`
                    WHEN 'at_warehouse'    THEN 'ready_to_ship'
                    WHEN 'ready_to_ship'   THEN 'ready_to_ship'
                    WHEN 'local_delivery'  THEN 'shipping'
                    WHEN 'shipped'         THEN 'shipping'
                    WHEN 'delivered'       THEN 'delivered'
                    ELSE NULL
                END
        ");

        // Verify: should be 0 nulls after backfill
        $nullCount = $this->db->createCommand(
            'SELECT COUNT(*) FROM `order` WHERE payment_status IS NULL OR logistics_status IS NULL'
        )->queryScalar();

        if ($nullCount > 0) {
            throw new \Exception("Backfill incomplete: {$nullCount} rows still have NULL track statuses");
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx_order_delivery_status',  'order');
        $this->dropIndex('idx_order_logistics_status', 'order');
        $this->dropIndex('idx_order_payment_status',   'order');
        $this->dropColumn('order', 'delivery_status');
        $this->dropColumn('order', 'logistics_status');
        $this->dropColumn('order', 'payment_status');
    }
}
