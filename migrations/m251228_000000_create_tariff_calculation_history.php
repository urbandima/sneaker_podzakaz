<?php

use yii\db\Migration;

/**
 * Создает таблицу истории расчетов тарифов
 */
class m251228_000000_create_tariff_calculation_history extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // Проверяем существование таблицы
        if ($this->db->schema->getTableSchema('{{%tariff_calculation}}') !== null) {
            return true;
        }

        $this->createTable('{{%tariff_calculation}}', [
            'id' => $this->primaryKey(),
            'tariff_id' => $this->integer()->notNull(),
            'tariff_name' => $this->string(100)->notNull(),
            'price_cny' => $this->decimal(10, 2)->notNull(),
            'weight_kg' => $this->decimal(5, 2)->notNull()->defaultValue(0.5),
            'commission_cny' => $this->decimal(10, 2)->notNull(),
            'delivery_cost_cny' => $this->decimal(10, 2)->notNull(),
            'insurance_cny' => $this->decimal(10, 2)->notNull(),
            'total_cny' => $this->decimal(10, 2)->notNull(),
            'exchange_rate' => $this->decimal(10, 4)->notNull(),
            'total_local' => $this->decimal(10, 2)->notNull(),
            'currency' => $this->string(3)->notNull()->defaultValue('BYN'),
            'user_id' => $this->integer(),
            'note' => $this->text(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // Индексы
        $this->createIndex('idx-tariff_calculation-tariff_id', '{{%tariff_calculation}}', 'tariff_id');
        $this->createIndex('idx-tariff_calculation-user_id', '{{%tariff_calculation}}', 'user_id');
        $this->createIndex('idx-tariff_calculation-created_at', '{{%tariff_calculation}}', 'created_at');

        // Внешние ключи
        $this->addForeignKey(
            'fk-tariff_calculation-tariff_id',
            '{{%tariff_calculation}}',
            'tariff_id',
            '{{%tariff}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-tariff_calculation-user_id',
            '{{%tariff_calculation}}',
            'user_id',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        return true;
    }

    public function safeDown()
    {
        $this->dropTable('{{%tariff_calculation}}');
        return true;
    }
}
