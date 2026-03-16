<?php

use yii\db\Migration;

/**
 * Migration for order tracking functionality
 */
class m240315_120200_create_tracking_tables extends Migration
{
    public function safeUp()
    {
        // Delivery tracking table
        $this->createTable('{{%delivery_tracking}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'tracking_number' => $this->string(100),
            'carrier' => "enum('pickup','courier','cdek','belpost','other') NOT NULL DEFAULT 'pickup'",
            'status' => "enum('pending','picked_up','in_transit','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending'",
            'status_description' => $this->string(255),
            'location' => $this->string(255),
            'estimated_delivery' => $this->date(),
            'actual_delivery' => $this->date(),
            'tracking_events' => $this->text(),
            'last_check_at' => $this->timestamp(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        $this->addForeignKey('fk_delivery_tracking_order', '{{%delivery_tracking}}', 'order_id', '{{%order}}', 'id', 'CASCADE');
        
        // Add index for tracking number
        $this->createIndex('idx_delivery_tracking_number', '{{%delivery_tracking}}', 'tracking_number');
    }

    public function safeDown()
    {
        $this->dropTable('{{%delivery_tracking}}');
    }
}
