<?php

use yii\db\Migration;

/**
 * Создание таблицы customer для учетных записей покупателей
 */
class m241228_213000_create_customer_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%customer}}', [
            'id' => $this->primaryKey(),
            'email' => $this->string(255)->notNull()->unique(),
            'phone' => $this->string(50)->null(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(64)->null(),
            'password_reset_token' => $this->string(255)->null()->unique(),
            'verification_token' => $this->string(255)->null(),
            
            // Профиль
            'first_name' => $this->string(100)->null(),
            'last_name' => $this->string(100)->null(),
            'middle_name' => $this->string(100)->null(),
            'birth_date' => $this->date()->null(),
            'gender' => $this->string(10)->null(), // male, female
            
            // Адрес доставки по умолчанию
            'default_country' => $this->string(50)->defaultValue('BY'),
            'default_city' => $this->string(100)->null(),
            'default_address' => $this->text()->null(),
            'default_postal_code' => $this->string(20)->null(),
            
            // Паспортные данные (для таможни)
            'passport_series' => $this->string(10)->null(),
            'passport_number' => $this->string(20)->null(),
            'passport_issue_date' => $this->date()->null(),
            'inn' => $this->string(20)->null(),
            
            // Статистика
            'orders_count' => $this->integer()->defaultValue(0),
            'total_spent' => $this->decimal(12, 2)->defaultValue(0),
            'last_order_at' => $this->integer()->null(),
            
            // Статус и настройки
            'status' => $this->smallInteger()->defaultValue(10), // 0-deleted, 9-inactive, 10-active
            'email_verified' => $this->boolean()->defaultValue(false),
            'phone_verified' => $this->boolean()->defaultValue(false),
            'subscribe_news' => $this->boolean()->defaultValue(true),
            'subscribe_promo' => $this->boolean()->defaultValue(true),
            
            // Метаданные
            'last_login_at' => $this->integer()->null(),
            'last_login_ip' => $this->string(45)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Индексы
        $this->createIndex('idx-customer-email', '{{%customer}}', 'email');
        $this->createIndex('idx-customer-phone', '{{%customer}}', 'phone');
        $this->createIndex('idx-customer-status', '{{%customer}}', 'status');
        $this->createIndex('idx-customer-created_at', '{{%customer}}', 'created_at');

        // Добавляем customer_id в таблицу order
        $this->addColumn('{{%order}}', 'customer_id', $this->integer()->null()->after('id'));
        $this->createIndex('idx-order-customer_id', '{{%order}}', 'customer_id');
        $this->addForeignKey(
            'fk-order-customer_id',
            '{{%order}}',
            'customer_id',
            '{{%customer}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-order-customer_id', '{{%order}}');
        $this->dropIndex('idx-order-customer_id', '{{%order}}');
        $this->dropColumn('{{%order}}', 'customer_id');
        
        $this->dropTable('{{%customer}}');
    }
}
