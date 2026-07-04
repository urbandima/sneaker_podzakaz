<?php

use yii\db\Migration;

/**
 * Seeds real status mappings for Europochta and Belpochta
 * based on live API responses:
 *   BY080054655812 — Europochta
 *   VV413770842BY  — Belpochta
 *
 * Europochta statuses (CheckxTo codes from API response):
 *   0  → Заявка зарегистрирована (waiting)
 *  10  → Принято на ОПС (received)
 *  20  → Оплачено на ОПС (processing)
 *  21  → Подготовлено к доставке в сортировочный пункт (processing)
 *  25  → Прибыло на сортировочный пункт (in_transit)
 *  29  → Подготовлено к доставке на ОПС назначения (in_transit)
 *  80  → Прибыло на ОПС выдачи (in_delivery)
 *  90  → Выдано получателю (delivered — final)
 *
 * Belpochta statuses (code field from steps array):
 *   4  → Принято от отправителя (received)
 *   6  → Поступило в обработку (processing)
 *  15  → Отправлено (in_transit)
 *  70  → Поступило в учреждение доставки (in_delivery)
 *  21  → Вручено (delivered — final)
 */
class m260415_110000_seed_europochta_belpochta_status_mappings extends Migration
{
    public function safeUp()
    {
        // Ensure delivery_provider table exists — skip if not
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

        // Upsert providers (INSERT IGNORE so we don't fail if already seeded)
        Yii::$app->db->createCommand("
            INSERT IGNORE INTO {{%delivery_provider}}
                (code, name, type, api_url, is_active, created_at, updated_at)
            VALUES
                ('europochta', 'Европочта',  'local', NULL, 1, {$now}, {$now}),
                ('belpochta',  'Белпочта',   'local', 'https://api.belpost.by', 1, {$now}, {$now})
        ")->execute();

        $epId = Yii::$app->db->createCommand(
            "SELECT id FROM {{%delivery_provider}} WHERE code = 'europochta' LIMIT 1"
        )->queryScalar();

        $bpId = Yii::$app->db->createCommand(
            "SELECT id FROM {{%delivery_provider}} WHERE code = 'belpochta' LIMIT 1"
        )->queryScalar();

        if (!$epId || !$bpId) {
            echo "Could not find provider IDs — skipping status seeding\n";
            return;
        }

        // Ensure delivery_status_mapping table exists
        $mapSchema = Yii::$app->db->getTableSchema('{{%delivery_status_mapping}}');
        if (!$mapSchema) {
            echo "delivery_status_mapping table not found — skipping\n";
            return;
        }

        // Europochta real statuses (CheckxTo code → internal status)
        $epStatuses = [
            // [provider_status_id, provider_status_name, internal_status, display_name, est_days, sort, is_final]
            ['0',  'Заявка на почтовое отправление зарегистрирована',                           'waiting',     'Заявка создана',                      null, 10, 0],
            ['10', 'Почтовое отправление принято на ОПС',                                        'received',    'Принято на ОПС',                      null, 20, 0],
            ['20', 'Оплачено на ОПС',                                                            'processing',  'Оплачено, готовится к отправке',      null, 30, 0],
            ['21', 'Подготовлено в ОПС к доставке на сортировочный пункт',                      'processing',  'Готовится к отправке',                null, 35, 0],
            ['25', 'Прибыло на сортировочный пункт',                                             'in_transit',  'На сортировочном пункте',             3,   40, 0],
            ['29', 'Подготовлено в сортировочном пункте к доставке на ОПС назначения',          'in_transit',  'Направлено в ОПС выдачи',             2,   45, 0],
            ['80', 'Прибыло на ОПС выдачи',                                                     'in_delivery', 'Прибыло в пункт выдачи',              1,   50, 0],
            ['90', 'Почтовое отправление выдано',                                                'delivered',   'Вручено получателю',                  0,   60, 1],
        ];

        // Belpochta real statuses (code field → internal status)
        $bpStatuses = [
            // [provider_status_id, provider_status_name, internal_status, display_name, est_days, sort, is_final]
            ['4',  'Принято от отправителя',             'received',    'Принято от отправителя',      null, 10, 0],
            ['6',  'Поступило в обработку',              'processing',  'В обработке',                 null, 20, 0],
            ['15', 'Отправлено',                         'in_transit',  'Отправлено',                  3,   30, 0],
            ['70', 'Поступило в учреждение доставки',   'in_delivery', 'Прибыло в отделение доставки', 1,   40, 0],
            ['21', 'Вручено',                            'delivered',   'Вручено получателю',          0,   50, 1],
        ];

        foreach ($epStatuses as $row) {
            Yii::$app->db->createCommand("
                INSERT IGNORE INTO {{%delivery_status_mapping}}
                    (provider_id, provider_status_id, provider_status_name, internal_status, display_name, estimated_days, sort_order, is_final)
                VALUES
                    (:pid, :psid, :psname, :is, :dn, :ed, :so, :fin)
            ", [
                ':pid'    => $epId,
                ':psid'   => $row[0],
                ':psname' => $row[1],
                ':is'     => $row[2],
                ':dn'     => $row[3],
                ':ed'     => $row[4],
                ':so'     => $row[5],
                ':fin'    => $row[6],
            ])->execute();
        }

        foreach ($bpStatuses as $row) {
            Yii::$app->db->createCommand("
                INSERT IGNORE INTO {{%delivery_status_mapping}}
                    (provider_id, provider_status_id, provider_status_name, internal_status, display_name, estimated_days, sort_order, is_final)
                VALUES
                    (:pid, :psid, :psname, :is, :dn, :ed, :so, :fin)
            ", [
                ':pid'    => $bpId,
                ':psid'   => $row[0],
                ':psname' => $row[1],
                ':is'     => $row[2],
                ':dn'     => $row[3],
                ':ed'     => $row[4],
                ':so'     => $row[5],
                ':fin'    => $row[6],
            ])->execute();
        }

        echo "Seeded " . count($epStatuses) . " Europochta statuses (provider_id={$epId})\n";
        echo "Seeded " . count($bpStatuses) . " Belpochta statuses (provider_id={$bpId})\n";
    }

    public function safeDown()
    {
        try {
            $epId = Yii::$app->db->createCommand(
                "SELECT id FROM {{%delivery_provider}} WHERE code = 'europochta' LIMIT 1"
            )->queryScalar();
            $bpId = Yii::$app->db->createCommand(
                "SELECT id FROM {{%delivery_provider}} WHERE code = 'belpochta' LIMIT 1"
            )->queryScalar();

            if ($epId) {
                Yii::$app->db->createCommand(
                    "DELETE FROM {{%delivery_status_mapping}} WHERE provider_id = :id",
                    [':id' => $epId]
                )->execute();
            }
            if ($bpId) {
                Yii::$app->db->createCommand(
                    "DELETE FROM {{%delivery_status_mapping}} WHERE provider_id = :id",
                    [':id' => $bpId]
                )->execute();
            }
        } catch (\Exception $e) {
            // ignore
        }
    }
}
