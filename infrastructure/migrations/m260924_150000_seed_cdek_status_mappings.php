<?php

use yii\db\Migration;

/**
 * CMP-451: delivery_provider не содержит строки для СДЭК, поэтому таблица
 * «Маппинг статусов» на /admin/plugin/cdek всегда пустая, хотя СДЭК включён
 * как реальный метод доставки и трекинг реально ходит в api.cdek.ru
 * (см. CdekTrackingService::parseResponse — читает entity.statuses[].code).
 *
 * Коды статусов — официальные строковые коды CDEK API v2
 * (GET /v2/orders, поле entity.statuses[].code).
 */
class m260924_150000_seed_cdek_status_mappings extends Migration
{
    public function safeUp()
    {
        try {
            $schema = Yii::$app->db->getTableSchema('{{%delivery_provider}}');
        } catch (\Exception $e) {
            echo "delivery_provider table not found — skipping\n";
            return;
        }
        if (!$schema) {
            echo "delivery_provider table not found — skipping\n";
            return;
        }

        $now = time();

        Yii::$app->db->createCommand("
            INSERT IGNORE INTO {{%delivery_provider}}
                (code, name, type, api_url, is_active, created_at, updated_at)
            VALUES
                ('cdek', 'СДЭК', 'local', 'https://api.cdek.ru/v2', 1, {$now}, {$now})
        ")->execute();

        $providerId = Yii::$app->db->createCommand(
            "SELECT id FROM {{%delivery_provider}} WHERE code = 'cdek' LIMIT 1"
        )->queryScalar();

        if (!$providerId) {
            echo "Could not find cdek provider id — skipping status seeding\n";
            return;
        }

        $mapSchema = Yii::$app->db->getTableSchema('{{%delivery_status_mapping}}');
        if (!$mapSchema) {
            echo "delivery_status_mapping table not found — skipping\n";
            return;
        }

        // CDEK API v2 статусы (entity.statuses[].code → внутренний статус)
        $statuses = [
            // [provider_status_id, provider_status_name, internal_status, display_name, est_days, sort, is_final]
            ['CREATED',                                'Создан',                                            'waiting',     'Заявка создана',                 null, 10,  0],
            ['ACCEPTED',                               'Принят',                                            'received',    'Принят СДЭК',                    null, 20,  0],
            ['RECEIVED_AT_SHIPMENT_WAREHOUSE',         'Принят на склад отправителя',                       'received',    'На складе отправителя',          null, 30,  0],
            ['READY_FOR_SHIPMENT_IN_SENDER_CITY',      'Готов к отправке в городе отправителя',            'processing',  'Готовится к отправке',           null, 40,  0],
            ['TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY',  'Изъят перевозчиком для доставки в город отправителя', 'in_transit', 'В пути',                       null, 50,  0],
            ['SENT_TO_TRANSIT_CITY',                   'Отправлен в транзитный город',                      'in_transit',  'В пути (транзит)',               null, 60,  0],
            ['ACCEPTED_AT_TRANSIT_WAREHOUSE',          'Прибыл на склад транзита',                          'in_transit',  'На транзитном складе',           null, 70,  0],
            ['SENT_TO_RECIPIENT_CITY',                 'Отправлен в город получателя',                      'in_transit',  'В пути в город получателя',      3,    80,  0],
            ['ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE',   'Прибыл на склад в городе получателя',              'in_delivery', 'Прибыл в город получателя',      2,    90,  0],
            ['TAKEN_BY_TRANSPORTER_FROM_RECIPIENT_CITY', 'Изъят перевозчиком для доставки по городу получателя', 'in_delivery', 'Передан на доставку',      1,    100, 0],
            ['READY_FOR_DELIVERY_ON_WAREHOUSE',        'Ожидает получения адресатом',                       'in_delivery', 'Ожидает в пункте выдачи',        1,    110, 0],
            ['TAKEN_BY_COURIER',                       'Выдан на доставку курьеру',                         'in_delivery', 'Передан курьеру',                1,    120, 0],
            ['DELIVERED',                              'Вручена',                                           'delivered',   'Вручено получателю',             0,    130, 1],
            ['NOT_DELIVERED',                          'Не вручена',                                        'failed',      'Не вручено',                     null, 140, 1],
            ['RETURNED',                               'Возвращена',                                        'returned',    'Возврат отправителю',            null, 150, 1],
        ];

        foreach ($statuses as $row) {
            Yii::$app->db->createCommand("
                INSERT IGNORE INTO {{%delivery_status_mapping}}
                    (provider_id, provider_status_id, provider_status_name, internal_status, display_name, estimated_days, sort_order, is_final)
                VALUES
                    (:pid, :psid, :psname, :is, :dn, :ed, :so, :fin)
            ", [
                ':pid'    => $providerId,
                ':psid'   => $row[0],
                ':psname' => $row[1],
                ':is'     => $row[2],
                ':dn'     => $row[3],
                ':ed'     => $row[4],
                ':so'     => $row[5],
                ':fin'    => $row[6],
            ])->execute();
        }

        echo "Seeded " . count($statuses) . " CDEK statuses (provider_id={$providerId})\n";
    }

    public function safeDown()
    {
        try {
            $providerId = Yii::$app->db->createCommand(
                "SELECT id FROM {{%delivery_provider}} WHERE code = 'cdek' LIMIT 1"
            )->queryScalar();

            if ($providerId) {
                Yii::$app->db->createCommand(
                    "DELETE FROM {{%delivery_status_mapping}} WHERE provider_id = :id",
                    [':id' => $providerId]
                )->execute();
                Yii::$app->db->createCommand(
                    "DELETE FROM {{%delivery_provider}} WHERE id = :id",
                    [':id' => $providerId]
                )->execute();
            }
        } catch (\Exception $e) {
            // ignore
        }
    }
}
