<?php

use yii\db\Migration;

class m260412_160000_add_color_to_order_status extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%order_status}}');
        
        // Проверяем существование колонки color
        if ($tableSchema && !$tableSchema->getColumn('color')) {
            $this->addColumn('{{%order_status}}', 'color', $this->string(20)->notNull()->defaultValue('secondary'));

            $colorMap = [
                'new' => 'info',
                'paid' => 'success',
                'confirmed_and_paid' => 'primary',
                'ordered' => 'warning',
                'awaiting_warehouse' => 'info',
                'international_delivery' => 'primary',
                'at_warehouse' => 'success',
                'local_delivery' => 'primary',
                'delivered' => 'success',
                'canceled' => 'danger',
            ];
            foreach ($colorMap as $key => $color) {
                $this->update('{{%order_status}}', ['color' => $color], ['key' => $key]);
            }
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order_status}}', 'color');
    }
}
