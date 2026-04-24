<?php

use yii\db\Migration;

/**
 * Adds local_track_number and pickup_point to order table.
 * - local_track_number: track number from local carrier (Europochta, Belpochta, CDEK)
 * - pickup_point: selected PVZ address for Europochta
 */
class m260414_120000_add_delivery_tracking_fields_to_order extends Migration
{
    public function up()
    {
        $columns = Yii::$app->db->getTableSchema('order')->columnNames;

        if (!in_array('local_track_number', $columns)) {
            $this->addColumn('order', 'local_track_number', $this->string(100)->null()->after('dp_track_number'));
        }

        if (!in_array('pickup_point', $columns)) {
            $this->addColumn('order', 'pickup_point', $this->string(500)->null()->after('delivery_address'));
        }
    }

    public function down()
    {
        $columns = Yii::$app->db->getTableSchema('order')->columnNames;

        if (in_array('local_track_number', $columns)) {
            $this->dropColumn('order', 'local_track_number');
        }

        if (in_array('pickup_point', $columns)) {
            $this->dropColumn('order', 'pickup_point');
        }
    }
}
