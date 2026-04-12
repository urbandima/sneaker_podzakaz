<?php

use yii\db\Migration;

/**
 * Создает таблицу delivery_provider и наполняет начальными данными
 */
class m260412_110000_create_delivery_provider extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%delivery_provider}}', [
            'id'               => $this->primaryKey(),
            'code'             => $this->string(50)->notNull()->unique()->comment('Уникальный код провайдера'),
            'name'             => $this->string(255)->notNull()->comment('Название провайдера'),
            'type'             => "ENUM('international','local') NOT NULL COMMENT 'Тип доставки'",
            'api_url'          => $this->string(500)->null()->comment('URL API'),
            'api_credentials'  => 'JSON NULL COMMENT \'Учётные данные API\'',
            'is_active'        => $this->tinyInteger(1)->defaultValue(1)->comment('Активен'),
            'settings'         => 'JSON NULL COMMENT \'Дополнительные настройки\'',
            'created_at'       => $this->integer()->null(),
            'updated_at'       => $this->integer()->null(),
        ]);

        $this->createIndex('idx-delivery_provider-code',      '{{%delivery_provider}}', 'code',      true);
        $this->createIndex('idx-delivery_provider-is_active', '{{%delivery_provider}}', 'is_active');
        $this->createIndex('idx-delivery_provider-type',      '{{%delivery_provider}}', 'type');

        $now = time();

        // Seed data
        $this->batchInsert('{{%delivery_provider}}', [
            'code', 'name', 'type', 'api_url', 'is_active', 'created_at', 'updated_at',
        ], [
            [
                'dobropost',
                'Таможня:ДП',
                'international',
                'https://api.dobropost.com',
                1,
                $now,
                $now,
            ],
            [
                'europochta',
                'Европочта',
                'local',
                null,
                1,
                $now,
                $now,
            ],
            [
                'belpochta',
                'Белпочта',
                'local',
                null,
                1,
                $now,
                $now,
            ],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%delivery_provider}}');
    }
}
