<?php

use yii\db\Migration;

/**
 * Создание демо-пользователей для тестирования
 */
class m260318_100000_create_demo_customers extends Migration
{
    public function safeUp()
    {
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
            'address' => 'г. Минск, ул. Независимости, 1',
            'is_active' => 1,
            'created_at' => new \yii\db\Expression('NOW()'),
            'updated_at' => new \yii\db\Expression('NOW()'),
        ]);
        
        // VIP пользователь
        $this->insert('{{%customer}}', [
            'email' => 'vip@sneakerhead.by',
            'phone' => '+375337654321',
            'password_hash' => $vipHash,
            'first_name' => 'VIP',
            'last_name' => 'Клиент',
            'address' => 'г. Минск, пр. Независимости, 100',
            'is_active' => 1,
            'created_at' => new \yii\db\Expression('NOW()'),
            'updated_at' => new \yii\db\Expression('NOW()'),
        ]);
        
        // Администратор
        $this->insert('{{%customer}}', [
            'email' => 'admin@sneakerhead.by',
            'phone' => '+375449876543',
            'password_hash' => $adminHash,
            'first_name' => 'Админ',
            'last_name' => 'Системы',
            'address' => 'г. Минск, ул. Интернациональная, 50',
            'is_active' => 1,
            'created_at' => new \yii\db\Expression('NOW()'),
            'updated_at' => new \yii\db\Expression('NOW()'),
        ]);
    }

    public function safeDown()
    {
        $this->delete('{{%customer}}', ['email' => 'demo@sneakerhead.by']);
        $this->delete('{{%customer}}', ['email' => 'vip@sneakerhead.by']);
        $this->delete('{{%customer}}', ['email' => 'admin@sneakerhead.by']);
    }
}
