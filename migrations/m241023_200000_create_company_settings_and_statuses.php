<?php

use yii\db\Migration;

class m241023_200000_create_company_settings_and_statuses extends Migration
{
    public function safeUp()
    {
        // Company settings
        $this->createTable('{{%company_settings}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'unp' => $this->string(50)->notNull(),
            'address' => $this->string(255)->notNull(),
            'bank' => $this->string(255)->notNull(),
            'bic' => $this->string(50)->notNull(),
            'account' => $this->string(64)->notNull(),
            'phone' => $this->string(50)->notNull(),
            'email' => $this->string(255)->notNull(),
            'offer_url' => $this->string(255),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Seed defaults (тестовые значения)
        $this->insert('{{%company_settings}}', [
            'name' => 'ООО "СникерКультура"',
            'unp' => '123456789',
            'address' => 'г. Минск, ул. Тестовая, д. 1',
            'bank' => 'ОАО "Тестовый банк"',
            'bic' => 'TESTBY2X',
            'account' => 'BY12TEST12345678901234567890',
            'phone' => '+375 29 123-45-67',
            'email' => 'info@sneakerculture.by',
            'offer_url' => null,
            'updated_at' => time(),
        ]);

        // Order statuses
        $this->createTable('{{%order_status}}', [
            'key' => $this->string(50)->notNull(),
            'label' => $this->string(100)->notNull(),
            'sort' => $this->integer()->notNull()->defaultValue(0),
            'logist_available' => $this->boolean()->notNull()->defaultValue(false),
            'PRIMARY KEY([[key]])',
        ]);

        $defaults = [
            'created' => 'Заказ составлен',
            'paid' => 'Заказ оплачен',
            'accepted' => 'Заказ принят',
            'ordered' => 'Заказан товар',
            'received' => 'Заказ получен',
            'issued' => 'Заказ выдан',
        ];
        $logist = ['ordered', 'received', 'issued'];
        $i = 0;
        foreach ($defaults as $key => $label) {
            $this->insert('{{%order_status}}', [
                'key' => $key,
                'label' => $label,
                'sort' => $i++,
                'logist_available' => in_array($key, $logist),
            ]);
        }
    }

    public function safeDown()
    {
        $this->dropTable('{{%order_status}}');
        $this->dropTable('{{%company_settings}}');
    }
}
