<?php

use yii\db\Migration;

/**
 * Добавляет поля логистики в таблицу заказов
 */
class m251222_000000_add_logistics_fields_to_order extends Migration
{
    public function safeUp()
    {
        // Китайский трек-номер
        try {
            $this->addColumn('{{%order}}', 'china_track_number', $this->string(100)->after('order_number'));
        } catch (\Exception $e) {
            echo "⚠ Колонка china_track_number уже существует\n";
        }
        
        // Ценность шипмента (общая, Y)
        try {
            $this->addColumn('{{%order}}', 'shipment_value_cny', $this->decimal(10, 2)->after('china_track_number'));
        } catch (\Exception $e) {
            echo "⚠ Колонка shipment_value_cny уже существует\n";
        }
        
        // Паспортные данные получателя
        try {
            $this->addColumn('{{%order}}', 'recipient_last_name', $this->string(100)->after('client_name'));
        } catch (\Exception $e) {
            echo "⚠ Колонка recipient_last_name уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'recipient_first_name', $this->string(100)->after('recipient_last_name'));
        } catch (\Exception $e) {
            echo "⚠ Колонка recipient_first_name уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'recipient_middle_name', $this->string(100)->after('recipient_first_name'));
        } catch (\Exception $e) {
            echo "⚠ Колонка recipient_middle_name уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'passport_series', $this->string(20)->after('recipient_middle_name'));
        } catch (\Exception $e) {
            echo "⚠ Колонка passport_series уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'passport_number', $this->string(50)->after('passport_series'));
        } catch (\Exception $e) {
            echo "⚠ Колонка passport_number уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'passport_issue_date', $this->date()->after('passport_number'));
        } catch (\Exception $e) {
            echo "⚠ Колонка passport_issue_date уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'birth_date', $this->date()->after('passport_issue_date'));
        } catch (\Exception $e) {
            echo "⚠ Колонка birth_date уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'inn', $this->string(20)->after('birth_date'));
        } catch (\Exception $e) {
            echo "⚠ Колонка inn уже существует\n";
        }
        
        // Адрес получателя (детализированный)
        try {
            $this->addColumn('{{%order}}', 'full_address', $this->text()->after('inn'));
        } catch (\Exception $e) {
            echo "⚠ Колонка full_address уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'city', $this->string(100)->after('full_address'));
        } catch (\Exception $e) {
            echo "⚠ Колонка city уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'region', $this->string(100)->after('city'));
        } catch (\Exception $e) {
            echo "⚠ Колонка region уже существует\n";
        }
        try {
            $this->addColumn('{{%order}}', 'postal_code', $this->string(20)->after('region'));
        } catch (\Exception $e) {
            echo "⚠ Колонка postal_code уже существует\n";
        }
        
        // Описание товара для таможни
        $directColumns = [
            ['customs_description', $this->text()->after('comment')],
            ['item_quantity',       $this->integer()->after('customs_description')],
            ['item_price_cny',      $this->decimal(10, 2)->after('item_quantity')],
            ['product_link',        $this->string(500)->after('item_price_cny')],
            ['dobropost_tariff',    $this->string(100)->after('product_link')],
            ['sneakerhead_order_link', $this->string(500)->after('dobropost_tariff')],
            ['is_processed',        $this->boolean()->defaultValue(false)->after('status')],
            ['is_shipped',          $this->boolean()->defaultValue(false)->after('is_processed')],
            ['customs_cleared',     $this->boolean()->defaultValue(false)->after('is_shipped')],
            ['ms_number',           $this->string(50)->after('customs_cleared')],
            ['product_price',       $this->decimal(10, 2)->after('total_amount')],
            ['logistics_price',     $this->decimal(10, 2)->after('product_price')],
            ['commission_price',    $this->decimal(10, 2)->after('logistics_price')],
        ];
        foreach ($directColumns as [$col, $def]) {
            try {
                $this->addColumn('{{%order}}', $col, $def);
            } catch (\Exception $e) {
                echo "⚠ Колонка {$col} уже существует\n";
            }
        }
        
        // Индексы для быстрого поиска
        $this->createIndex('idx-order-china_track', '{{%order}}', 'china_track_number');
        $this->createIndex('idx-order-is_processed', '{{%order}}', 'is_processed');
        $this->createIndex('idx-order-is_shipped', '{{%order}}', 'is_shipped');
        $this->createIndex('idx-order-customs_cleared', '{{%order}}', 'customs_cleared');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-order-customs_cleared', '{{%order}}');
        $this->dropIndex('idx-order-is_shipped', '{{%order}}');
        $this->dropIndex('idx-order-is_processed', '{{%order}}');
        $this->dropIndex('idx-order-china_track', '{{%order}}');
        
        $this->dropColumn('{{%order}}', 'commission_price');
        $this->dropColumn('{{%order}}', 'logistics_price');
        $this->dropColumn('{{%order}}', 'product_price');
        $this->dropColumn('{{%order}}', 'ms_number');
        $this->dropColumn('{{%order}}', 'customs_cleared');
        $this->dropColumn('{{%order}}', 'is_shipped');
        $this->dropColumn('{{%order}}', 'is_processed');
        $this->dropColumn('{{%order}}', 'sneakerhead_order_link');
        $this->dropColumn('{{%order}}', 'dobropost_tariff');
        $this->dropColumn('{{%order}}', 'product_link');
        $this->dropColumn('{{%order}}', 'item_price_cny');
        $this->dropColumn('{{%order}}', 'item_quantity');
        $this->dropColumn('{{%order}}', 'customs_description');
        $this->dropColumn('{{%order}}', 'postal_code');
        $this->dropColumn('{{%order}}', 'region');
        $this->dropColumn('{{%order}}', 'city');
        $this->dropColumn('{{%order}}', 'full_address');
        $this->dropColumn('{{%order}}', 'inn');
        $this->dropColumn('{{%order}}', 'birth_date');
        $this->dropColumn('{{%order}}', 'passport_issue_date');
        $this->dropColumn('{{%order}}', 'passport_number');
        $this->dropColumn('{{%order}}', 'passport_series');
        $this->dropColumn('{{%order}}', 'recipient_middle_name');
        $this->dropColumn('{{%order}}', 'recipient_first_name');
        $this->dropColumn('{{%order}}', 'recipient_last_name');
        $this->dropColumn('{{%order}}', 'shipment_value_cny');
        $this->dropColumn('{{%order}}', 'china_track_number');
    }
}
