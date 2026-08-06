<?php

use yii\db\Migration;

/**
 * Создание демо-пользователей для тестирования
 */
class m260318_100000_create_demo_customers extends Migration
{
    public function safeUp()
    {
        if (YII_ENV_PROD) {
            echo "    > skipped: демо-аккаунты (demo123/vip123/admin123) не создаются на production\n";
            return true;
        }

        $time = time();

        // Хеши паролей
        $demoHash = '$2y$12$zQo1DZMj3eLLVtpT4KZVz.k3gr.9AQK8R/EbQc7U128M83p4a1RJS'; // demo123
        $vipHash = '$2y$12$nmHVEfbybt2iJxwIjT0AwOFNBMlxx7UERokWfd0xwI0J2GUiq2eYK'; // vip123
        $adminHash = '$2y$12$CRPqAtwnKRLuoOxURR2iF.X4/IQJj9WaVXmoBAjyKx8q8L8JRxzvy'; // admin123
        
        // Обычный пользователь
        $this->insert('{{%customer}}', [
            'email' => 'demo@sneakerhead.by',
            'phone' => '+375291234567',
            'password_hash' => $demoHash,
            'first_name' => 'Демо',
            'last_name' => 'Пользователь',
            'default_address' => 'г. Минск, ул. Независимости, 1',
            'status' => 10,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        
        // VIP пользователь
        $this->insert('{{%customer}}', [
            'email' => 'vip@sneakerhead.by',
            'phone' => '+375337654321',
            'password_hash' => $vipHash,
            'first_name' => 'VIP',
            'last_name' => 'Клиент',
            'default_address' => 'г. Минск, пр. Независимости, 100',
            'status' => 10,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        
        // Администратор
        $this->insert('{{%customer}}', [
            'email' => 'admin@sneakerhead.by',
            'phone' => '+375449876543',
            'password_hash' => $adminHash,
            'first_name' => 'Админ',
            'last_name' => 'Системы',
            'default_address' => 'г. Минск, ул. Интернациональная, 50',
            'status' => 10,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%customer}}', ['email' => 'demo@sneakerhead.by']);
        $this->delete('{{%customer}}', ['email' => 'vip@sneakerhead.by']);
        $this->delete('{{%customer}}', ['email' => 'admin@sneakerhead.by']);
    }
}
