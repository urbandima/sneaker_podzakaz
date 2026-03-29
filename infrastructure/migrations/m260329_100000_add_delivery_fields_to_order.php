<?php

use yii\db\Migration;

/**
 * Добавление недостающих полей доставки в таблицу order
 */
class m260329_100000_add_delivery_fields_to_order extends Migration
{
    public function safeUp()
    {
        // Поля доставки, которые используются в модели но отсутствуют в БД
        try {
            $this->addColumn('{{%order}}', 'delivery_method', $this->string(50)->after('client_email'));
            echo "✓ Добавлено поле delivery_method\n";
        } catch (\Exception $e) {
            echo "⚠ Поле delivery_method уже существует\n";
        }
        
        try {
            $this->addColumn('{{%order}}', 'delivery_country', $this->string(50)->after('delivery_method'));
            echo "✓ Добавлено поле delivery_country\n";
        } catch (\Exception $e) {
            echo "⚠ Поле delivery_country уже существует\n";
        }
        
        try {
            $this->addColumn('{{%order}}', 'delivery_address', $this->text()->after('delivery_country'));
            echo "✓ Добавлено поле delivery_address\n";
        } catch (\Exception $e) {
            echo "⚠ Поле delivery_address уже существует\n";
        }
        
        try {
            $this->addColumn('{{%order}}', 'delivery_cost', $this->decimal(10, 2)->defaultValue(0)->after('delivery_address'));
            echo "✓ Добавлено поле delivery_cost\n";
        } catch (\Exception $e) {
            echo "⚠ Поле delivery_cost уже существует\n";
        }
        
        try {
            $this->addColumn('{{%order}}', 'comment', $this->text()->after('delivery_cost'));
            echo "✓ Добавлено поле comment\n";
        } catch (\Exception $e) {
            echo "⚠ Поле comment уже существует\n";
        }

        // Создаем индексы для оптимизации
        try {
            $this->createIndex('idx-order-delivery_method', '{{%order}}', 'delivery_method');
        } catch (\Exception $e) {
            echo "⚠ Индекс idx-order-delivery_method уже существует\n";
        }
        
        try {
            $this->createIndex('idx-order-delivery_country', '{{%order}}', 'delivery_country');
        } catch (\Exception $e) {
            echo "⚠ Индекс idx-order-delivery_country уже существует\n";
        }
        
        echo "✓ Все поля доставки успешно добавлены\n";
    }

    public function safeDown()
    {
        // Удаляем индексы
        $this->dropIndex('idx-order-delivery_country', '{{%order}}');
        $this->dropIndex('idx-order-delivery_method', '{{%order}}');
        
        // Удаляем поля
        $this->dropColumn('{{%order}}', 'comment');
        $this->dropColumn('{{%order}}', 'delivery_cost');
        $this->dropColumn('{{%order}}', 'delivery_address');
        $this->dropColumn('{{%order}}', 'delivery_country');
        $this->dropColumn('{{%order}}', 'delivery_method');
        
        echo "✓ Поля доставки успешно удалены\n";
    }
}
