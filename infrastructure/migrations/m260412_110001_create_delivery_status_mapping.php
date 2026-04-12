<?php

use yii\db\Migration;

/**
 * Создает таблицу delivery_status_mapping и наполняет статусами Таможня:ДП
 */
class m260412_110001_create_delivery_status_mapping extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%delivery_status_mapping}}', [
            'id'                   => $this->primaryKey(),
            'provider_id'          => $this->integer()->notNull()->comment('ID провайдера'),
            'provider_status_id'   => $this->string(20)->notNull()->comment('ID статуса у провайдера'),
            'provider_status_name' => $this->string(255)->null()->comment('Название статуса у провайдера'),
            'internal_status'      => $this->string(50)->null()->comment('Внутренний статус системы'),
            'display_name'         => $this->string(255)->null()->comment('Отображаемое название'),
            'estimated_days'       => $this->integer()->null()->comment('Ориентировочное количество дней'),
            'sort_order'           => $this->integer()->defaultValue(0)->comment('Порядок сортировки'),
            'is_final'             => $this->tinyInteger(1)->defaultValue(0)->comment('Финальный статус'),
        ]);

        $this->addForeignKey(
            'fk-delivery_status_mapping-provider_id',
            '{{%delivery_status_mapping}}',
            'provider_id',
            '{{%delivery_provider}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex(
            'idx-dsm-provider-status',
            '{{%delivery_status_mapping}}',
            ['provider_id', 'provider_status_id'],
            true
        );
        $this->createIndex('idx-dsm-internal_status', '{{%delivery_status_mapping}}', 'internal_status');

        // Получаем ID провайдера dobropost
        $dpId = $this->db->createCommand(
            "SELECT id FROM {{%delivery_provider}} WHERE code = 'dobropost' LIMIT 1"
        )->queryScalar();

        if (!$dpId) {
            throw new \RuntimeException('delivery_provider record for dobropost not found. Run migration m260412_110000 first.');
        }

        // Все статусы Таможня:ДП согласно документации API
        $statuses = [
            // id, provider_status_id, provider_status_name, internal_status, display_name, estimated_days, sort_order, is_final
            [$dpId, '1',      'Ожидается на складе',                                                                          'waiting',          'Ожидается на складе',                    null, 10,  0],
            [$dpId, '2',      'Получен от курьера',                                                                            'received',         'Получен от курьера',                     null, 20,  0],
            [$dpId, '3',      'Обработан на складе',                                                                           'processing',       'Обработан на складе',                    null, 30,  0],
            [$dpId, '4',      'Добавлен в мешок',                                                                              'processing',       'Добавлен в мешок',                       null, 40,  0],
            [$dpId, '5',      'Добавлен в реестр',                                                                             'processing',       'Добавлен в реестр',                      null, 50,  0],
            [$dpId, '6',      'Покинул склад в Китае',                                                                         'in_transit',       'Отправлен из Китая',                     14,   60,  0],
            [$dpId, '7',      'Поступил на таможню в Китае',                                                                   'in_transit',       'На таможне в Китае',                     10,   70,  0],
            [$dpId, '8',      'Поступил на таможню в России',                                                                  'customs',          'На таможне в России',                    7,    80,  0],
            [$dpId, '9',      'Передан партнеру',                                                                              'in_delivery',      'Передан в доставку',                     3,    90,  0],
            [$dpId, '270',    'Запрос на редактирование данных посылки',                                                       'edit_request',     'Запрос на изменение данных',             null, 270, 0],
            [$dpId, '271',    'Запрос на редактирование данных посылки отклонен',                                              'edit_rejected',    'Запрос на изменение отклонён',           null, 271, 0],
            [$dpId, '272',    'Произведено редактирование данных посылки',                                                     'edit_done',        'Данные посылки обновлены',               null, 272, 0],
            [$dpId, '500',    'Начало таможенного оформления',                                                                 'customs',          'Начало таможенного оформления',          null, 500, 0],
            [$dpId, '510',    'Требуется уплатить таможенные пошлины',                                                        'customs_duty',     'Требуется оплата таможенной пошлины',    null, 510, 0],
            [$dpId, '520',    'Выпуск товаров без уплаты таможенных платежей',                                                 'customs_cleared',  'Выпущено без оплаты пошлины',            null, 520, 0],
            [$dpId, '521',    'Выпуск товаров без уплаты таможенных платежей',                                                 'customs_cleared',  'Выпущено без оплаты пошлины',            null, 521, 0],
            [$dpId, '530',    'Выпуск товаров (таможенные платежи уплачены)',                                                  'customs_cleared',  'Таможня пройдена',                       null, 530, 0],
            [$dpId, '531',    'Требуется уплатить таможенные пошлины',                                                        'customs_duty',     'Требуется оплата таможенной пошлины',    null, 531, 0],
            [$dpId, '532',    'Выпуск товаров (таможенные платежи уплачены)',                                                  'customs_cleared',  'Таможня пройдена',                       null, 532, 0],
            [$dpId, '540',    'Ожидание обязательной оплаты таможенной пошлины',                                              'customs_duty',     'Ожидание оплаты таможенной пошлины',     null, 540, 0],
            [$dpId, '541',    'Отказ в выпуске: посылка признана коммерческой',                                               'customs_rejected', 'Отказ в выпуске посылки',                null, 541, 1],
            [$dpId, '542',    'Отказ в выпуске: отсутствуют необходимые документы',                                           'customs_rejected', 'Отказ: нет необходимых документов',      null, 542, 1],
            [$dpId, '543',    'Отказ в выпуске: некорректное описание товара',                                                'customs_rejected', 'Отказ: некорректное описание товара',    null, 543, 1],
            [$dpId, '544',    'Отказ в выпуске: некорректные паспортные данные',                                              'customs_rejected', 'Отказ: некорректные паспортные данные',  null, 544, 1],
            [$dpId, '545',    'Отказ в выпуске: паспорт не в списке достоверных',                                            'customs_rejected', 'Отказ: паспорт не прошёл проверку',      null, 545, 1],
            [$dpId, '546',    'Отказ в выпуске по другим причинам',                                                           'customs_rejected', 'Отказ в выпуске посылки',                null, 546, 1],
            [$dpId, '570',    'Продление времени обработки',                                                                   'customs',          'Продление обработки',                    null, 570, 0],
            [$dpId, '591',    'Начало таможенного оформления',                                                                 'customs',          'Начало таможенного оформления',          null, 591, 0],
            [$dpId, '600',    'Посылка не пришла',                                                                             'lost',             'Посылка не получена',                    null, 600, 1],
            [$dpId, '648',    'Подготовлено к отгрузке в доставку последней мили',                                             'prepared_for_delivery', 'Готовится к отправке в РФ',         2,    648, 0],
            [$dpId, '649',    'Покинула таможню и передана на доставку по РФ',                                                 'in_delivery',      'Передана в доставку по РФ',              3,    649, 0],
            [$dpId, '590204', 'Отказ в выпуске товаров с указанием кода причины отказа',                                      'customs_rejected', 'Отказ в выпуске (590204)',               null, 900, 1],
            [$dpId, '590401', 'Отказ в выпуске товаров с указанием кода причины отказа',                                      'customs_rejected', 'Отказ в выпуске (590401)',               null, 901, 1],
            [$dpId, '590404', 'Отказ в выпуске: не представлены документы и сведения',                                        'customs_rejected', 'Отказ: не представлены документы',       null, 902, 1],
            [$dpId, '590405', 'Отказ в выпуске товаров с указанием кода причины отказа',                                      'customs_rejected', 'Отказ в выпуске (590405)',               null, 903, 1],
            [$dpId, '590409', 'Отказ в выпуске товаров с указанием кода причины отказа',                                      'customs_rejected', 'Отказ в выпуске (590409)',               null, 904, 1],
            [$dpId, '590410', 'Отказ в выпуске: товар входит в перечень категорий',                                           'customs_rejected', 'Отказ: категория товаров запрещена',     null, 905, 1],
            [$dpId, '590413', 'Отказ в выпуске: не подана корректировка пп.2 п.1 ст.125 ТК ЕАЭС',                            'customs_rejected', 'Отказ: требуется корректировка ТК ЕАЭС', null, 906, 1],
            [$dpId, '590420', 'Отказ в выпуске товаров с указанием кода причины отказа',                                      'customs_rejected', 'Отказ в выпуске (590420)',               null, 907, 1],
            [$dpId, '590592', 'Отказ в выпуске товаров с указанием кода причины отказа',                                      'customs_rejected', 'Отказ в выпуске (590592)',               null, 908, 1],
        ];

        $this->batchInsert('{{%delivery_status_mapping}}', [
            'provider_id', 'provider_status_id', 'provider_status_name',
            'internal_status', 'display_name', 'estimated_days', 'sort_order', 'is_final',
        ], $statuses);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-delivery_status_mapping-provider_id', '{{%delivery_status_mapping}}');
        $this->dropTable('{{%delivery_status_mapping}}');
    }
}
