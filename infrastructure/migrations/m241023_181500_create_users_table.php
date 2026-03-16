<?php

use yii\db\Migration;

class m241023_181500_create_users_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull()->unique(),
            'email' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'role' => $this->string(50)->notNull()->defaultValue('manager'),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Создаем индексы
        $this->createIndex('idx-user-username', '{{%user}}', 'username');
        $this->createIndex('idx-user-email', '{{%user}}', 'email');
        $this->createIndex('idx-user-role', '{{%user}}', 'role');

        // Вставляем тестовых пользователей
        $this->insert('{{%user}}', [
            'username' => 'admin',
            'email' => 'admin@sneakerculture.by',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'role' => 'admin',
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%user}}', [
            'username' => 'manager',
            'email' => 'manager@sneakerculture.by',
            'password_hash' => Yii::$app->security->generatePasswordHash('manager123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'role' => 'manager',
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->insert('{{%user}}', [
            'username' => 'logist',
            'email' => 'logist@sneakerculture.by',
            'password_hash' => Yii::$app->security->generatePasswordHash('logist123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'role' => 'logist',
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}
