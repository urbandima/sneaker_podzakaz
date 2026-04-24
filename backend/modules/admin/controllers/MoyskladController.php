<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderHistory;

/**
 * Admin controller for МойСклад bidirectional sync.
 * Routes: /admin/plugin/moysklad/*
 */
class MoyskladController extends BaseAdminController
{
    protected bool $adminOnly = true;

    /** GET /admin/plugin/moysklad */
    public function actionIndex()
    {
        $syncStats = $this->getSyncStats();

        // Status mapping for tab 2
        $siteStatuses = [
            'new'                    => 'Новый',
            'paid'                   => 'Оплачен',
            'confirmed_and_paid'     => 'Подтверждён и оплачен',
            'ordered'                => 'Заказано у поставщика',
            'awaiting_warehouse'     => 'Ожидает склад',
            'international_delivery' => 'Международная доставка',
            'at_warehouse'           => 'На складе',
            'local_delivery'         => 'В доставке',
            'delivered'              => 'Выдан',
            'canceled'               => 'Отменен',
        ];

        // Load saved status mapping
        $statusMapping = [];
        foreach ($siteStatuses as $key => $label) {
            $statusMapping[$key] = Yii::$app->settings->get('moysklad', 'status_map_' . $key, '');
        }

        // Field mappings from DB schema
        $orderMsFields = [
            'moysklad_id'              => ['label' => 'UUID заказа в МС',          'type' => 'string'],
            'ms_number'                => ['label' => '№ заказа в МС',             'type' => 'string'],
            'ms_external_code'         => ['label' => 'Внешний код',               'type' => 'string'],
            'ms_applicable'            => ['label' => 'Проведён',                  'type' => 'bool'],
            'ms_payed_sum'             => ['label' => 'Оплачено',                  'type' => 'money'],
            'ms_shipped_sum'           => ['label' => 'Отгружено',                 'type' => 'money'],
            'ms_invoiced_sum'          => ['label' => 'Выставлено счетов',         'type' => 'money'],
            'ms_reserved_sum'          => ['label' => 'Зарезервировано',           'type' => 'money'],
            'ms_vat_sum'               => ['label' => 'НДС',                       'type' => 'money'],
            'ms_rate_value'            => ['label' => 'Курс валюты',               'type' => 'number'],
            'ms_rate_currency'         => ['label' => 'Валюта',                    'type' => 'string'],
            'ms_sales_channel'         => ['label' => 'Канал продаж',              'type' => 'string'],
            'ms_store_name'            => ['label' => 'Склад',                     'type' => 'string'],
            'ms_shipment_address_json' => ['label' => 'Адрес доставки (JSON)',     'type' => 'json'],
            'ms_attributes_json'       => ['label' => 'Доп. атрибуты (JSON)',      'type' => 'json'],
            'ms_updated'               => ['label' => 'Обновлён в МС',            'type' => 'datetime'],
            'ms_created'               => ['label' => 'Создан в МС',              'type' => 'datetime'],
            'ms_lead_source'           => ['label' => 'Источник лида',             'type' => 'string'],
            'ms_delivery_type'         => ['label' => 'Тип доставки',              'type' => 'string'],
            'ms_deal_link'             => ['label' => 'Ссылка на сделку',          'type' => 'text'],
            'ms_amo_created_at'        => ['label' => 'Создан в AMO',              'type' => 'datetime'],
            'ms_order_size'            => ['label' => 'Размер (из МС)',            'type' => 'string'],
            'ms_via_widget'            => ['label' => 'Через виджет',              'type' => 'bool'],
            'ms_passport_transferred'  => ['label' => 'Паспорт передан',           'type' => 'bool'],
            'ms_cancel_reason'         => ['label' => 'Причина отмены',            'type' => 'text'],
            'ms_waybill_link'          => ['label' => 'Ссылка на накладную',       'type' => 'text'],
            'ms_cancel_date'           => ['label' => 'Дата отмены',               'type' => 'string'],
            'ms_pickup_date'           => ['label' => 'Дата выдачи',               'type' => 'string'],
            'ms_organization_id'       => ['label' => 'ID организации',            'type' => 'string'],
            'ms_organization_name'     => ['label' => 'Организация',               'type' => 'string'],
            'ms_contract_id'           => ['label' => 'ID договора',               'type' => 'string'],
            'ms_contract_name'         => ['label' => 'Договор',                   'type' => 'string'],
            'ms_project_id'            => ['label' => 'ID проекта',                'type' => 'string'],
            'ms_project_name'          => ['label' => 'Проект',                    'type' => 'string'],
            'ms_delivery_planned_moment' => ['label' => 'Плановая дата доставки', 'type' => 'datetime'],
            'ms_vat_enabled'           => ['label' => 'НДС включён',               'type' => 'bool'],
            'ms_vat_included'          => ['label' => 'НДС в цене',                'type' => 'bool'],
            'ms_printed'               => ['label' => 'Напечатан',                 'type' => 'bool'],
            'ms_published'             => ['label' => 'Опубликован',               'type' => 'bool'],
            'ms_linked_payments_json'  => ['label' => 'Связанные платежи (JSON)',  'type' => 'json'],
            'ms_demands_json'          => ['label' => 'Отгрузки (JSON)',           'type' => 'json'],
            'ms_invoices_out_json'     => ['label' => 'Исходящие счета (JSON)',    'type' => 'json'],
            'ms_positions_count'       => ['label' => 'Кол-во позиций',            'type' => 'number'],
            'ms_agent_company_type'    => ['label' => 'Тип контрагента',           'type' => 'string'],
            'ms_agent_legal_title'     => ['label' => 'Юр. наименование',          'type' => 'string'],
            'ms_agent_actual_address'  => ['label' => 'Фактический адрес',         'type' => 'text'],
        ];

        $productMsFields = [
            'ms_code'                 => ['label' => 'Код в МС',                'type' => 'string'],
            'ms_external_code'        => ['label' => 'Внешний код',             'type' => 'string'],
            'ms_volume'               => ['label' => 'Объём',                   'type' => 'number'],
            'ms_images_json'          => ['label' => 'Изображения (JSON)',      'type' => 'json'],
            'ms_attributes_json'      => ['label' => 'Атрибуты (JSON)',         'type' => 'json'],
            'ms_characteristics_json' => ['label' => 'Характеристики (JSON)',   'type' => 'json'],
            'ms_min_price'            => ['label' => 'Мин. цена',               'type' => 'money'],
            'ms_supplier_name'        => ['label' => 'Поставщик',               'type' => 'string'],
            'ms_archived'             => ['label' => 'Архивирован',             'type' => 'bool'],
            'ms_no_export'            => ['label' => 'Не экспортировать',       'type' => 'bool'],
            'ms_size_grid'            => ['label' => 'Сетка размеров',          'type' => 'string'],
            'ms_purpose'              => ['label' => 'Назначение',              'type' => 'string'],
            'ms_sole_height'          => ['label' => 'Высота подошвы',          'type' => 'string'],
            'ms_sole_color'           => ['label' => 'Цвет подошвы',            'type' => 'string'],
            'ms_inner_material'       => ['label' => 'Внутренний материал',     'type' => 'string'],
            'ms_site_link'            => ['label' => 'Ссылка на сайте',         'type' => 'string'],
            'ms_price_full'           => ['label' => 'Полная цена',             'type' => 'money'],
            'ms_price_rub'            => ['label' => 'Цена в руб.',             'type' => 'money'],
            'ms_price_sale'           => ['label' => 'Акционная цена',          'type' => 'money'],
            'ms_price_competitor'     => ['label' => 'Цена конкурентов',        'type' => 'money'],
            'ms_path_name'            => ['label' => 'Путь в каталоге',         'type' => 'string'],
        ];

        $customerMsFields = [
            'ms_external_code'        => ['label' => 'Внешний код МС',          'type' => 'string'],
            'company_type'            => ['label' => 'Тип контрагента',         'type' => 'string'],
            'fax'                     => ['label' => 'Факс',                    'type' => 'string'],
            'notes'                   => ['label' => 'Заметки',                 'type' => 'text'],
            'discount_card_number'    => ['label' => '№ дисконтной карты',      'type' => 'string'],
            'bonus_points'            => ['label' => 'Бонусные баллы',          'type' => 'number'],
            'tags_json'               => ['label' => 'Теги (JSON)',             'type' => 'json'],
            'actual_address'          => ['label' => 'Фактический адрес',       'type' => 'text'],
            'legal_address'           => ['label' => 'Юридический адрес',       'type' => 'text'],
            'legal_title'             => ['label' => 'Юр. наименование',        'type' => 'string'],
            'kpp'                     => ['label' => 'КПП',                     'type' => 'string'],
            'ogrn'                    => ['label' => 'ОГРН',                    'type' => 'string'],
            'ogrnip'                  => ['label' => 'ОГРНИП',                  'type' => 'string'],
            'okpo'                    => ['label' => 'ОКПО',                    'type' => 'string'],
        ];

        // Sync log
        $syncLog = json_decode(Yii::$app->settings->get('moysklad', 'sync_log', '[]'), true) ?: [];

        // Saved field mappings (ms_field => db_column)
        $savedMappingOrders    = json_decode(Yii::$app->settings->get('moysklad', 'mapping_orders',    '{}'), true) ?: [];
        $savedMappingProducts  = json_decode(Yii::$app->settings->get('moysklad', 'mapping_products',  '{}'), true) ?: [];
        $savedMappingCustomers = json_decode(Yii::$app->settings->get('moysklad', 'mapping_customers', '{}'), true) ?: [];

        return $this->render('/plugin/moysklad', [
            'syncStats'             => $syncStats,
            'lastSyncAt'            => Yii::$app->settings->get('moysklad', 'last_sync_at', ''),
            'webhookUrl'            => Yii::$app->urlManager->createAbsoluteUrl(['/admin/moysklad/webhook']),
            'siteStatuses'          => $siteStatuses,
            'statusMapping'         => $statusMapping,
            'orderMsFields'         => $orderMsFields,
            'productMsFields'       => $productMsFields,
            'customerMsFields'      => $customerMsFields,
            'syncLog'               => $syncLog,
            'savedMappingOrders'    => $savedMappingOrders,
            'savedMappingProducts'  => $savedMappingProducts,
            'savedMappingCustomers' => $savedMappingCustomers,
        ]);
    }

    /** POST /admin/plugin/moysklad/save-status-mapping — JSON: save full status mapping */
    public function actionSaveStatusMapping()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: [];

        // Save as individual settings AND as a single JSON blob
        foreach ($data as $ourStatus => $msName) {
            Yii::$app->settings->set('moysklad', 'status_map_' . $ourStatus, (string)$msName);
        }
        // Also save the whole mapping as one setting for easy retrieval
        Yii::$app->settings->set('moysklad', 'status_mapping', json_encode($data, JSON_UNESCAPED_UNICODE));

        return ['success' => true, 'message' => 'Маппинг статусов сохранён'];
    }

    /** POST /admin/plugin/moysklad/save-settings — JSON: save integration settings */
    public function actionSaveSettings()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: [];

        $fields = [
            'auto_sync_interval', 'sync_orders', 'sync_products', 'sync_customers',
            'sync_stock', 'sync_prices', 'sync_ms_to_site', 'sync_site_to_ms',
        ];
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                Yii::$app->settings->set('moysklad', $f, (string)$data[$f]);
            }
        }

        return ['success' => true, 'message' => 'Настройки сохранены'];
    }

    /** GET /admin/plugin/moysklad/sync-log — JSON: return full sync log */
    public function actionSyncLog()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $logs = json_decode(Yii::$app->settings->get('moysklad', 'sync_log', '[]'), true) ?: [];
        return ['success' => true, 'logs' => $logs];
    }

    /** POST /admin/plugin/moysklad/test-connection — JSON */
    public function actionTestConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $msg = Yii::$app->moysklad->testConnection();
            return ['success' => true, 'message' => $msg];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** POST /admin/plugin/moysklad/save-credentials — JSON */
    public function actionSaveCredentials()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data     = json_decode(Yii::$app->request->getRawBody(), true) ?: [];

        // Allow saving sync direction toggles without touching credentials
        if (!empty($data['_directions_only'])) {
            Yii::$app->settings->set('moysklad', 'sync_ms_to_site', $data['sync_ms_to_site'] ?? '1');
            Yii::$app->settings->set('moysklad', 'sync_site_to_ms', $data['sync_site_to_ms'] ?? '1');
            return ['success' => true, 'message' => 'Настройки синхронизации сохранены'];
        }

        $login    = trim($data['login']    ?? '');
        $password = trim($data['password'] ?? '');
        $apiKey   = trim($data['api_key']  ?? '');

        if ($apiKey) {
            Yii::$app->settings->set('moysklad', 'api_key', $apiKey);
        } elseif ($login && $password) {
            Yii::$app->settings->set('moysklad', 'login',    $login);
            Yii::$app->settings->set('moysklad', 'password', $password);
            Yii::$app->settings->set('moysklad', 'api_key',  '');
        } else {
            return ['success' => false, 'message' => 'Введите API-ключ или логин+пароль'];
        }
        // Clear cached org_id so next request re-fetches
        Yii::$app->settings->set('moysklad', 'org_id', '');

        return ['success' => true, 'message' => 'Данные сохранены'];
    }

    /** POST /admin/plugin/moysklad/save-mapping — JSON */
    public function actionSaveMapping()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        foreach ($data as $ourStatus => $msName) {
            Yii::$app->settings->set('moysklad', 'status_map_' . $ourStatus, (string)$msName);
        }
        return ['success' => true, 'message' => 'Маппинг сохранён'];
    }

    /** POST /admin/plugin/moysklad/push-all — JSON: push all unsynced orders */
    public function actionPushAll()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $result = Yii::$app->moysklad->pushAllOrders(50);
            $this->saveSyncLog('push_all', $result);
            Yii::$app->settings->set('moysklad', 'last_sync_at', date('Y-m-d H:i:s'));
            return ['success' => true] + $result;
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** POST /admin/plugin/moysklad/periodic-sync — JSON: pull recently updated orders from МС */
    public function actionPeriodicSync()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data  = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $since = (int)($data['since'] ?? 60); // minutes

        try {
            $result = Yii::$app->moysklad->periodicSync($since);
            $this->saveSyncLog('periodic_sync', $result);
            return ['success' => true] + $result;
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** GET /admin/plugin/moysklad/webhook-status — JSON: check if webhook is registered */
    public function actionWebhookStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $status = Yii::$app->moysklad->getWebhookStatus();
            $hooks  = Yii::$app->moysklad->listWebhooks();
            return ['success' => true, 'status' => $status, 'count' => count($hooks)];
        } catch (\Throwable $e) {
            return ['success' => false, 'status' => 'unknown', 'message' => $e->getMessage()];
        }
    }

    /** POST /admin/plugin/moysklad/pull — JSON: pull orders from МС */
    public function actionPull()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data   = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $limit  = min((int)($data['limit']  ?? 100), 1000);
        $offset = (int)($data['offset'] ?? 0);

        try {
            $result = Yii::$app->moysklad->pullOrders($limit, $offset);
            $this->saveSyncLog('pull', $result);
            Yii::$app->settings->set('moysklad', 'last_sync_at', date('Y-m-d H:i:s'));
            return ['success' => true] + $result;
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** POST /admin/plugin/moysklad/push-order — JSON: push single order */
    public function actionPushOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data  = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $id    = (int)($data['id'] ?? 0);
        $order = Order::findOne($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }
        try {
            $result = Yii::$app->moysklad->pushOrder($order);
            return ['success' => true, 'ms_id' => $result['id'] ?? null, 'message' => 'Заказ отправлен в МойСклад'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** GET /admin/plugin/moysklad/sync-info — JSON: org info + states */
    public function actionSyncInfo()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            return ['success' => true] + Yii::$app->moysklad->getSyncInfo();
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** GET /admin/plugin/moysklad/webhooks — JSON */
    public function actionWebhooks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $hooks = Yii::$app->moysklad->listWebhooks();
            return ['success' => true, 'webhooks' => $hooks];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** POST /admin/plugin/moysklad/register-webhook — JSON */
    public function actionRegisterWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $webhookUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/moysklad/webhook']);
        try {
            $result = Yii::$app->moysklad->registerWebhook($webhookUrl);
            return ['success' => true, 'id' => $result['id'] ?? null, 'url' => $webhookUrl];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** POST /admin/plugin/moysklad/delete-webhook — JSON */
    public function actionDeleteWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $id   = $data['id'] ?? '';
        if (!$id) return ['success' => false, 'message' => 'Не указан ID вебхука'];
        try {
            Yii::$app->moysklad->deleteWebhook($id);
            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * GET /admin/moysklad/get-db-columns?table=orders|products|customers
     * Returns list of column names for the given table.
     */
    public function actionGetDbColumns()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $allowed = ['orders' => 'order', 'products' => 'product', 'customers' => 'customer'];
        $table   = Yii::$app->request->get('table', '');

        if (!isset($allowed[$table])) {
            return ['success' => false, 'message' => 'Недопустимое имя таблицы'];
        }

        $dbTable = $allowed[$table];

        try {
            $rows    = Yii::$app->db->createCommand('SHOW COLUMNS FROM `' . $dbTable . '`')->queryAll();
            $columns = array_column($rows, 'Field');
            return ['success' => true, 'columns' => $columns];
        } catch (\Throwable $e) {
            Yii::error('getDbColumns: ' . $e->getMessage(), 'moysklad');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * POST /admin/moysklad/save-moysklad-mapping — JSON
     * Body: {"entity": "orders"|"products"|"customers", "mapping": {"ms_field": "db_column", ...}}
     * Saves to app_setting section=moysklad, key=mapping_orders / mapping_products / mapping_customers
     */
    public function actionSaveMoyskladMapping()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data   = json_decode(Yii::$app->request->getRawBody(), true) ?: [];
        $entity = $data['entity']  ?? '';
        $map    = $data['mapping'] ?? [];

        $allowed = ['orders', 'products', 'customers'];
        if (!in_array($entity, $allowed, true)) {
            return ['success' => false, 'message' => 'Недопустимый entity'];
        }
        if (!is_array($map)) {
            return ['success' => false, 'message' => 'Неверный формат mapping'];
        }

        $key = 'mapping_' . $entity;
        try {
            Yii::$app->settings->set('moysklad', $key, json_encode($map, JSON_UNESCAPED_UNICODE));
            return ['success' => true, 'message' => 'Маппинг сохранён'];
        } catch (\Throwable $e) {
            Yii::error('saveMoyskladMapping: ' . $e->getMessage(), 'moysklad');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * POST /admin/moysklad/webhook — receives push from МойСклад when order changes there.
     * МС sends: {"events": [{"meta": {"href": "...customerorder/UUID"}, "action": "UPDATE", "uid": "..."}]}
     */
    public function actionWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        // Disable CSRF for webhook
        $this->enableCsrfValidation = false;

        $body   = Yii::$app->request->getRawBody();
        $data   = json_decode($body, true) ?? [];
        $events = $data['events'] ?? [];

        $processed = 0;
        foreach ($events as $event) {
            $href   = $event['meta']['href'] ?? '';
            $action = $event['action']       ?? '';

            // Extract UUID from href
            if (!preg_match('/customerorder\/([a-f0-9\-]{36})/', $href, $m)) {
                continue;
            }
            $msId = $m[1];

            // Find our order
            $order = Order::find()->where(['moysklad_id' => $msId])->one();
            if (!$order) continue;

            if ($action === 'UPDATE') {
                try {
                    $oldStatus = $order->status;
                    $result2   = Yii::$app->moysklad->syncOrderFromMS($msId);
                    if (($result2['status'] ?? '') === 'updated' && $order->status !== $result2['our_status']) {
                        OrderHistory::log($order->id, 'status_changed', 'status', $oldStatus, $result2['our_status'], 'МойСклад webhook');
                    }
                    $processed++;
                } catch (\Throwable $e) {
                    Yii::error('MoySklad webhook: ' . $e->getMessage(), 'moysklad');
                }
            } elseif ($action === 'DELETE') {
                Order::updateAll(['moysklad_id' => null], ['moysklad_id' => $msId]);
                $processed++;
            }
        }

        return ['success' => true, 'processed' => $processed];
    }

    // ─── MS image proxy ──────────────────────────────────────────────────────

    /**
     * GET /admin/moysklad/ms-images?product_id=N
     * Fetches the images collection for a product and returns JSON array of
     * {miniature, tiny, filename} objects (URLs proxied via ms-image action).
     */
    public function actionMsImages(int $product_id): string
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $row = \Yii::$app->db->createCommand(
            'SELECT ms_images_json FROM product WHERE id = :id', [':id' => $product_id]
        )->queryOne();

        if (!$row || empty($row['ms_images_json'])) {
            return json_encode([]);
        }

        $data = json_decode($row['ms_images_json'], true);
        if (!$data) {
            return json_encode([]);
        }

        $collectionUrl = $data['__collection__'] ?? null;
        if (!$collectionUrl) {
            return json_encode([]);
        }

        try {
            // Cannot use MoySkladService::request() here — it adds "Accept: application/json"
            // which the images endpoint rejects with HTTP 400.
            $apiKey = Yii::$app->settings->get('moysklad', 'api_key', '');
            $authHeader = $apiKey
                ? 'Bearer ' . $apiKey
                : 'Basic ' . base64_encode(
                    Yii::$app->settings->get('moysklad', 'login', 'admin@sneakerculture')
                    . ':' . Yii::$app->settings->get('moysklad', 'password', 'NorTwe1534')
                );
            $ch = curl_init($collectionUrl . '?limit=20');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_HTTPHEADER     => ['Authorization: ' . $authHeader, 'Accept-Encoding: gzip'],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING       => 'gzip',
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code !== 200) {
                return json_encode([]);
            }
            $resp = json_decode($body, true) ?? [];
            $rows = $resp['rows'] ?? [];
            $out  = [];
            foreach ($rows as $img) {
                // miniature.downloadHref is on miniature-prod.moysklad.ru — publicly accessible
                $miniHref = $img['miniature']['downloadHref'] ?? null;
                // tiny.href is on tinyimage-prod.moysklad.ru — also publicly accessible
                $tinyHref = $img['tiny']['href'] ?? null;
                // Fallback to api.moysklad.ru URL proxied through our endpoint
                if (!$miniHref && !empty($img['miniature']['href'])) {
                    $proxy    = \yii\helpers\Url::to(['/admin/moysklad/ms-image']);
                    $miniHref = $proxy . '?url=' . urlencode($img['miniature']['href']);
                }
                if (!$miniHref) continue;
                $out[] = [
                    'filename'  => $img['filename'] ?? '',
                    'miniature' => $miniHref,
                    'tiny'      => $tinyHref,
                ];
            }
            return json_encode($out);
        } catch (\Throwable $e) {
            Yii::error('MS images: ' . $e->getMessage(), 'moysklad');
            return json_encode([]);
        }
    }

    /**
     * GET /admin/moysklad/ms-image?url={encoded_ms_download_url}
     * Proxy-streams one image from MoySklad API with auth headers.
     * Only proxies URLs from api.moysklad.ru.
     */
    public function actionMsImage(): void
    {
        $url = Yii::$app->request->get('url', '');
        if (!$url || !str_starts_with($url, 'https://api.moysklad.ru/')) {
            Yii::$app->response->statusCode = 400;
            Yii::$app->response->send();
            return;
        }

        $authHeader = 'Basic ' . base64_encode(
            (Yii::$app->settings->get('moysklad', 'login', 'admin@sneakerculture'))
            . ':'
            . (Yii::$app->settings->get('moysklad', 'password', 'NorTwe1534'))
        );
        $apiKey = Yii::$app->settings->get('moysklad', 'api_key', '');
        if ($apiKey) {
            $authHeader = 'Bearer ' . $apiKey;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $authHeader],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => 'gzip',
        ]);
        $body    = curl_exec($ch);
        $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false || $code !== 200) {
            Yii::$app->response->statusCode = 502;
            Yii::$app->response->send();
            return;
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type',  $ctype ?: 'image/jpeg');
        Yii::$app->response->headers->set('Cache-Control', 'public, max-age=3600');
        Yii::$app->response->content = $body;
        Yii::$app->response->send();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getSyncStats(): array
    {
        $total   = Order::find()->count();
        $synced  = Order::find()->where(['IS NOT', 'moysklad_id', null])->count();
        $unsynced = $total - $synced;
        return ['total' => $total, 'synced' => $synced, 'unsynced' => $unsynced];
    }

    private function saveSyncLog(string $operation, array $result): void
    {
        $logs = json_decode(Yii::$app->settings->get('moysklad', 'sync_log', '[]'), true) ?: [];
        array_unshift($logs, [
            'at'        => date('Y-m-d H:i:s'),
            'operation' => $operation,
            'result'    => $result,
        ]);
        // Keep last 20 entries
        $logs = array_slice($logs, 0, 20);
        Yii::$app->settings->set('moysklad', 'sync_log', json_encode($logs, JSON_UNESCAPED_UNICODE));
    }
}
