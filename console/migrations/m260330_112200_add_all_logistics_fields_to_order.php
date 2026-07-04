<?php

use yii\db\Migration;

/**
 * Добавляет все недостающие поля логистики в таблицу order
 */
class m260330_112200_add_all_logistics_fields_to_order extends Migration
{
    public function safeUp()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        // Паспортные данные получателя
        $fields = [
            'recipient_last_name' => $this->string(100),
            'recipient_first_name' => $this->string(100),
            'recipient_middle_name' => $this->string(100),
            'passport_series' => $this->string(50),
            'passport_number' => $this->string(100),
            'passport_issue_date' => $this->date(),
            'birth_date' => $this->date(),
            'inn' => $this->string(50),
            
            // Логистика
            'china_track_number' => $this->string(100),
            'shipment_value_cny' => $this->decimal(10, 2),
            'customs_description' => $this->text(),
            'item_quantity' => $this->integer(),
            'item_price_cny' => $this->decimal(10, 2),
            'product_link' => $this->string(500),
            'dobropost_tariff' => $this->string(100),
            'sneakerhead_order_link' => $this->string(500),
            'ms_number' => $this->string(50),
            
            // Статусы
            'is_processed' => $this->boolean()->defaultValue(false),
            'is_shipped' => $this->boolean()->defaultValue(false),
            'customs_cleared' => $this->boolean()->defaultValue(false),
            
            // Финансы
            'product_price' => $this->decimal(10, 2),
            'logistics_price' => $this->decimal(10, 2),
            'commission_price' => $this->decimal(10, 2),
            
            // Прочее
            'assigned_logist' => $this->integer(),
            'source' => $this->string(50),
            'source_id' => $this->integer(),
            'delivery_date' => $this->string(255),
            'offer_accepted' => $this->boolean()->defaultValue(false),
            'offer_accepted_at' => $this->integer(),
            'payment_uploaded_at' => $this->integer(),
        ];
        
        foreach ($fields as $field => $type) {
            if (!$tableSchema->getColumn($field)) {
                $this->addColumn('{{%order}}', $field, $type);
            }
        }
    }

    public function safeDown()
    {
        $tableSchema = $this->db->getTableSchema('{{%order}}');
        
        $fields = [
            'payment_uploaded_at', 'offer_accepted_at', 'offer_accepted', 'delivery_date',
            'source_id', 'source', 'assigned_logist', 'commission_price', 'logistics_price',
            'product_price', 'customs_cleared', 'is_shipped', 'is_processed', 'ms_number',
            'sneakerhead_order_link', 'dobropost_tariff', 'product_link', 'item_price_cny',
            'item_quantity', 'customs_description', 'shipment_value_cny', 'china_track_number',
            'inn', 'birth_date', 'passport_issue_date', 'passport_number', 'passport_series',
            'recipient_middle_name', 'recipient_first_name', 'recipient_last_name'
        ];
        
        foreach ($fields as $field) {
            if ($tableSchema->getColumn($field)) {
                $this->dropColumn('{{%order}}', $field);
            }
        }
    }
}
