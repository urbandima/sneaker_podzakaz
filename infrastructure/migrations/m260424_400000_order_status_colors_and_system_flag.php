<?php

use yii\db\Migration;

class m260424_400000_order_status_colors_and_system_flag extends Migration
{
    public function up()
    {
        // W19: add is_system flag
        $this->addColumn('{{%order_status}}', 'is_system',
            $this->tinyInteger(1)->notNull()->defaultValue(0)->after('is_active'));

        // W18: assign distinct default colors
        $colorMap = [
            'new'           => 'secondary',
            'created'       => 'secondary',
            'pending'       => 'secondary',
            'paid'          => 'info',
            'confirmed'     => 'info',
            'confirmed_and_paid' => 'info',
            'processing'    => 'warning',
            'ordered'       => 'warning',
            'awaiting_warehouse' => 'warning',
            'international_delivery' => 'primary',
            'at_warehouse'  => 'primary',
            'packed'        => 'primary',
            'local_delivery' => 'primary',
            'shipped'       => 'primary',
            'delivered'     => 'success',
            'completed'     => 'success',
            'cancelled'     => 'danger',
            'canceled'      => 'danger',
            'refunded'      => 'warning',
            'returned'      => 'warning',
        ];

        foreach ($colorMap as $key => $color) {
            $this->update('{{%order_status}}', ['color' => $color], ['key' => $key]);
        }

        // W19: mark system statuses
        $systemKeys = ['new', 'paid', 'shipped', 'delivered', 'cancelled', 'canceled', 'refunded'];
        $this->update('{{%order_status}}', ['is_system' => 1], ['key' => $systemKeys]);
    }

    public function down()
    {
        $this->dropColumn('{{%order_status}}', 'is_system');
    }
}
