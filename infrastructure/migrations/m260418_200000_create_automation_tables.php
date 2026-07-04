<?php

use yii\db\Migration;

/**
 * Создаёт таблицы системы автоматизации: automation_trigger, automation_log, sms_template
 */
class m260418_200000_create_automation_tables extends Migration
{
    public function safeUp()
    {
        // 1. Trigger definitions
        $this->createTable('{{%automation_trigger}}', [
            'id'              => $this->primaryKey(),
            'name'            => $this->string(255)->notNull()->comment('Название триггера'),
            'description'     => $this->text()->comment('Описание'),
            'event_code'      => $this->string(100)->notNull()->comment('Код события (order.created, order.status_changed, ...)'),
            'conditions'      => $this->json()->comment('Условия в виде JSON-массива'),
            'actions'         => $this->json()->notNull()->comment('Действия в виде JSON-массива'),
            'is_active'       => $this->tinyInteger(1)->defaultValue(1)->comment('Активен ли триггер'),
            'priority'        => $this->integer()->defaultValue(0)->comment('Приоритет выполнения (меньше — раньше)'),
            'execution_count' => $this->integer()->defaultValue(0)->comment('Количество выполнений'),
            'last_executed_at'=> $this->dateTime()->null()->comment('Последнее выполнение'),
            'created_at'      => $this->integer()->notNull(),
            'updated_at'      => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-automation_trigger-event_code', '{{%automation_trigger}}', 'event_code');
        $this->createIndex('idx-automation_trigger-is_active',  '{{%automation_trigger}}', 'is_active');

        // 2. Execution log
        $this->createTable('{{%automation_log}}', [
            'id'               => $this->primaryKey(),
            'trigger_id'       => $this->integer()->notNull(),
            'event_code'       => $this->string(100)->comment('Код события'),
            'order_id'         => $this->integer()->null(),
            'customer_id'      => $this->integer()->null(),
            'conditions_met'   => $this->tinyInteger(1)->defaultValue(1),
            'actions_executed' => $this->json()->comment('Результаты выполнения действий'),
            'execution_time_ms'=> $this->integer()->null()->comment('Время выполнения в мс'),
            'created_at'       => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        $this->createIndex('idx-automation_log-trigger_id',  '{{%automation_log}}', 'trigger_id');
        $this->createIndex('idx-automation_log-event_code',  '{{%automation_log}}', 'event_code');
        $this->createIndex('idx-automation_log-order_id',    '{{%automation_log}}', 'order_id');
        $this->createIndex('idx-automation_log-created_at',  '{{%automation_log}}', 'created_at');
        $this->addForeignKey(
            'fk-automation_log-trigger_id',
            '{{%automation_log}}', 'trigger_id',
            '{{%automation_trigger}}', 'id',
            'CASCADE'
        );

        // 3. SMS templates
        $this->createTable('{{%sms_template}}', [
            'id'         => $this->primaryKey(),
            'code'       => $this->string(50)->notNull()->unique()->comment('Уникальный код шаблона'),
            'name'       => $this->string(255)->notNull()->comment('Название'),
            'template'   => $this->text()->notNull()->comment('Текст с переменными {order_number}, {total}, ...'),
            'variables'  => $this->json()->comment('Доступные переменные'),
            'is_active'  => $this->tinyInteger(1)->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // Seed SMS templates
        $now = time();
        $this->batchInsert('{{%sms_template}}', ['code','name','template','variables','is_active','created_at','updated_at'], [
            ['order_created',    'Заказ создан',            'Заказ №{order_number} принят! Сумма: {total} BYN. Спасибо за покупку!',            '["order_number","total","client_name"]',           1, $now, $now],
            ['payment_confirmed','Оплата подтверждена',     'Оплата по заказу №{order_number} подтверждена. Заполните паспорт: {passport_link}','["order_number","passport_link"]',                 1, $now, $now],
            ['sent_to_dp',       'Отправлен в ДП',         'Заказ №{order_number} передан на доставку. Трек ДП: {dp_track}',                   '["order_number","dp_track"]',                      1, $now, $now],
            ['delivered',        'Доставлен',              'Ваш заказ №{order_number} доставлен! Спасибо за покупку в СНИКЕРХЭД.',             '["order_number"]',                                 1, $now, $now],
            ['status_changed',   'Статус изменён',         'Статус заказа №{order_number} изменён: {new_status}',                              '["order_number","new_status","old_status"]',        1, $now, $now],
        ]);

        // Seed default triggers
        $this->batchInsert('{{%automation_trigger}}',
            ['name','description','event_code','conditions','actions','is_active','priority','execution_count','created_at','updated_at'],
            [
                [
                    'SMS: Заказ создан',
                    'Отправляет SMS клиенту при создании заказа',
                    'order.created',
                    '[]',
                    '[{"type":"send_sms","template":"order_created","to":"client"}]',
                    1, 10, 0, $now, $now,
                ],
                [
                    'SMS: Оплата подтверждена',
                    'Отправляет SMS с ссылкой на заполнение паспорта',
                    'order.payment_confirmed',
                    '[]',
                    '[{"type":"send_sms","template":"payment_confirmed","to":"client"}]',
                    1, 10, 0, $now, $now,
                ],
                [
                    'SMS: Отправлен в Таможня:ДП',
                    'Уведомляет клиента об отправке трека ДП',
                    'order.sent_to_dp',
                    '[]',
                    '[{"type":"send_sms","template":"sent_to_dp","to":"client"}]',
                    1, 10, 0, $now, $now,
                ],
                [
                    'SMS: Заказ доставлен',
                    'Поздравляет клиента с получением заказа',
                    'order.delivered',
                    '[]',
                    '[{"type":"send_sms","template":"delivered","to":"client"}]',
                    1, 10, 0, $now, $now,
                ],
                [
                    'Авто-создание клиента',
                    'Создаёт профиль клиента если его нет',
                    'order.created',
                    '[{"field":"order.customer_id","operator":"is_empty","value":null}]',
                    '[{"type":"create_customer"}]',
                    1, 5, 0, $now, $now,
                ],
                [
                    'Бонусы за покупку',
                    'Начисляет 100 бонусных баллов при доставке заказа',
                    'order.delivered',
                    '[]',
                    '[{"type":"earn_bonus","points":100,"description":"Бонусы за покупку"}]',
                    1, 20, 0, $now, $now,
                ],
            ]
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-automation_log-trigger_id', '{{%automation_log}}');
        $this->dropTable('{{%automation_log}}');
        $this->dropTable('{{%automation_trigger}}');
        $this->dropTable('{{%sms_template}}');
    }
}
