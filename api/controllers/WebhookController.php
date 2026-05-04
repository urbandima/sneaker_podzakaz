<?php

/**
 * WebhookController — Обработка входящих webhook-уведомлений
 *
 * ENDPOINTS:
 * - POST /api/webhook/dobropost — Webhook от Таможня:ДП (обновление статуса/паспорта)
 */
namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\DeliveryProvider;
use app\backend\modules\admin\services\OrderFromLeadService;
use app\backend\modules\admin\services\AmocrmStatusMapper;

class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        // Логируем все входящие запросы
        Yii::info(
            sprintf(
                '[Webhook] %s %s | IP: %s | Body: %s',
                Yii::$app->request->method,
                Yii::$app->request->url,
                Yii::$app->request->userIP,
                Yii::$app->request->getRawBody()
            ),
            'webhook'
        );
        return parent::beforeAction($action);
    }

    /**
     * POST /api/webhook/dobropost
     *
     * Принимает два типа payload от Таможня:ДП (согласно документации API):
     *
     * 1. Проверка паспорта:
     *    { "shipmentId": 0, "statusDate": "string", "passportValidationStatus": true }
     *
     * 2. Обновление статуса:
     *    { "shipmentId": 0, "DPTrackNumber": "string", "statusDate": "string", "status": "string" }
     */
    public function actionDobropost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        // Всегда возвращаем 200 — Таможня:ДП иначе будет повторять хук
        Yii::$app->response->statusCode = 200;

        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            return ['error' => 'Method Not Allowed'];
        }

        // Опциональная проверка HMAC-подписи
        $secret = env('DP_WEBHOOK_SECRET', '');
        $rawBody = Yii::$app->request->getRawBody();

        if ($secret) {
            $signature = Yii::$app->request->headers->get('X-DP-Signature', '');
            $expected  = hash_hmac('sha256', $rawBody, $secret);

            if (!hash_equals($expected, $signature)) {
                Yii::warning('[Webhook DP] Неверная подпись. IP: ' . Yii::$app->request->userIP, 'dp-webhook');
                Yii::$app->response->statusCode = 401;
                return ['error' => 'Invalid signature'];
            }
        }

        $data = json_decode($rawBody, true);

        if (!is_array($data)) {
            Yii::warning('[Webhook DP] Невалидный JSON: ' . $rawBody, 'dp-webhook');
            Yii::$app->response->statusCode = 400;
            return ['error' => 'Invalid JSON'];
        }

        $shipmentId = $data['shipmentId'] ?? null;
        if (!$shipmentId) {
            Yii::warning('[Webhook DP] Отсутствует shipmentId', 'dp-webhook');
            Yii::$app->response->statusCode = 400;
            return ['error' => 'Missing shipmentId'];
        }

        try {
            // --- Тип 1: Проверка паспорта (поле passportValidationStatus присутствует) ---
            if (array_key_exists('passportValidationStatus', $data)) {
                return $this->handlePassportValidation($shipmentId, $data);
            }

            // --- Тип 2: Обновление статуса ---
            return $this->handleStatusUpdate($shipmentId, $data);

        } catch (\Exception $e) {
            Yii::error('[Webhook DP] Исключение: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'dp-webhook');
            Yii::$app->response->statusCode = 500;
            return ['error' => 'Internal error'];
        }
    }

    /**
     * POST /api/webhook/amocrm  (alias: /webhook/amocrm/event)
     * POST /webhook/amocrm/lead-status-changed
     *
     * Принимает события от AmoCRM: leads[status], leads[add], leads[delete].
     * AmoCRM может слать и form-encoded, и JSON.
     */
    public function actionAmocrm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->statusCode = 200;

        $raw  = Yii::$app->request->getRawBody();

        // AmoCRM sends form-encoded webhooks; try to parse both
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            parse_str($raw, $data);
        }
        if (!is_array($data)) $data = [];

        // Test probe
        if (!empty($data['_test'])) {
            return ['ok' => true, 'message' => 'webhook endpoint is reachable'];
        }

        // HMAC verification (optional — only if secret is set in env)
        $secret = env('AMOCRM_SECRET_KEY') ?: '';
        if ($secret) {
            $sig = Yii::$app->request->headers->get('X-Signature', '');
            if ($sig && !hash_equals(hash_hmac('sha256', $raw, $secret), $sig)) {
                Yii::warning('[Webhook AMO] Invalid HMAC signature', 'amocrm');
                // Don't reject — AmoCRM doesn't always send signature; just log
            }
        }

        try {
            $this->logAmoCrmWebhook($raw);

            $service = new OrderFromLeadService();

            // leads[status] — create order if trigger status, or update existing
            if (!empty($data['leads']['status'])) {
                foreach ($data['leads']['status'] as $lead) {
                    $this->handleLeadStatus($lead, $service);
                }
            }

            // leads[add] — new lead added, create order if create_on_add setting is enabled
            if (!empty($data['leads']['add'])) {
                $createOnAdd = (bool)Yii::$app->settings->get('amocrm', 'create_order_on_lead_add', false);
                if ($createOnAdd) {
                    foreach ($data['leads']['add'] as $lead) {
                        $leadId = (int)($lead['id'] ?? 0);
                        if (!$leadId) continue;
                        try {
                            $service->createFromLeadId($leadId);
                        } catch (\Throwable $e) {
                            Yii::error('[Webhook AMO] leads[add] create error: ' . $e->getMessage(), 'amocrm');
                        }
                    }
                }
            }

            // leads[delete] — clear amocrm_lead_id
            if (!empty($data['leads']['delete'])) {
                foreach ($data['leads']['delete'] as $lead) {
                    $order = Order::findOne(['amocrm_lead_id' => (int)($lead['id'] ?? 0)]);
                    if ($order) {
                        $order->amocrm_lead_id = null;
                        $order->save(false);
                    }
                }
            }

        } catch (\Exception $e) {
            Yii::error('[Webhook AMO] ' . $e->getMessage(), 'amocrm');
        }

        return ['ok' => true];
    }

    /** Alias for /webhook/amocrm/lead-status-changed */
    public function actionLeadStatusChanged()
    {
        return $this->actionAmocrm();
    }

    private function handleLeadStatus(array $lead, OrderFromLeadService $service): void
    {
        $leadId     = (int)($lead['id'] ?? 0);
        $statusId   = (int)($lead['status_id'] ?? 0);
        $pipelineId = (int)($lead['pipeline_id'] ?? 0);
        $statusName = $lead['status_name'] ?? null;
        if (!$leadId) return;

        $order = Order::findOne(['amocrm_lead_id' => $leadId]);

        // If no order and this status should trigger order creation — create it
        if (!$order && AmocrmStatusMapper::shouldCreateOrder($statusId, $statusName)) {
            try {
                $order = $service->createFromLeadId($leadId);
                Yii::info('[Webhook AMO] Order created for lead #' . $leadId, 'amocrm');
            } catch (\Throwable $e) {
                Yii::error('[Webhook AMO] Create order failed: ' . $e->getMessage(), 'amocrm');
            }
            return;
        }

        if (!$order) return;

        // Map AmoCRM status → our order status (pipeline-aware: same status_id can mean
        // different things across pipelines, so always pass pipeline_id when available).
        $newOrderStatus = AmocrmStatusMapper::fromAmocrm($pipelineId, $statusId, $statusName);

        if ($newOrderStatus && $newOrderStatus !== $order->status) {
            $oldStatus = $order->status;
            $order->status = $newOrderStatus;
            $order->amocrm_last_sync_at = time();
            $order->save(false);
            \app\backend\modules\checkout\models\OrderHistory::log(
                $order->id, 'status_changed', null,
                $oldStatus, $newOrderStatus,
                'AmoCRM webhook: pipeline=' . $pipelineId . ' status_id=' . $statusId
            );
            Yii::info('[Webhook AMO] Order #' . $order->id . ' status: ' . $oldStatus . ' → ' . $newOrderStatus, 'amocrm');
        }
    }

    private function logAmoCrmWebhook(string $body): void
    {
        try {
            Yii::$app->db->createCommand()->insert('{{%amocrm_log}}', [
                'direction'   => 'incoming',
                'event'       => 'webhook',
                'status'      => 'ok',
                'payload'     => mb_substr($body, 0, 4096),
                'response'    => null,
                'response_ms' => 0,
                'created_at'  => time(),
            ])->execute();
        } catch (\Exception $e) {}
    }

    /**
     * Обработка результата проверки паспорта по DaData.
     * Payload: { shipmentId, statusDate, passportValidationStatus }
     */
    private function handlePassportValidation(int $shipmentId, array $data): array
    {
        $order = Order::findOne(['dp_shipment_id' => $shipmentId]);
        if (!$order) {
            Yii::warning('[Webhook DP] Паспорт: заказ с dp_shipment_id=' . $shipmentId . ' не найден', 'dp-webhook');
            return ['ok' => true, 'note' => 'Order not found, ignored'];
        }

        $validated = (bool) $data['passportValidationStatus'];
        $order->passport_validated    = $validated ? 1 : 0;
        $order->passport_submitted_at = $order->passport_submitted_at ?: time();
        $order->save(false);

        Yii::info(
            sprintf('[Webhook DP] Паспорт заказа #%d: %s', $order->id, $validated ? 'подтверждён' : 'отклонён'),
            'dp-webhook'
        );

        return ['ok' => true, 'order_id' => $order->id, 'passport_validated' => $validated];
    }

    /**
     * Обработка обновления статуса отправления.
     * Payload: { shipmentId, DPTrackNumber, statusDate, status }
     */
    private function handleStatusUpdate(int $shipmentId, array $data): array
    {
        $order = Order::findOne(['dp_shipment_id' => $shipmentId]);
        if (!$order) {
            Yii::warning('[Webhook DP] Статус: заказ с dp_shipment_id=' . $shipmentId . ' не найден', 'dp-webhook');
            return ['ok' => true, 'note' => 'Order not found, ignored'];
        }

        $oldStatus  = $order->dp_status;
        $newStatus  = (string) ($data['status'] ?? '');
        $trackNum   = $data['DPTrackNumber'] ?? null;
        $statusDate = $data['statusDate']    ?? null;

        if ($newStatus !== '') {
            $order->dp_status = $newStatus;
        }
        if ($statusDate) {
            $ts = strtotime($statusDate);
            if ($ts) {
                $order->dp_status_date = date('Y-m-d H:i:s', $ts);
            }
        }
        if ($trackNum && empty($order->dp_track_number)) {
            $order->dp_track_number = $trackNum;
        }

        // Рассчитываем estimated_delivery_date через таблицу маппинга статусов
        if ($newStatus !== '') {
            $dpProvider = DeliveryProvider::findByCode('dobropost');
            if ($dpProvider) {
                $mapping = $dpProvider->mapStatus($newStatus);
                if ($mapping && $mapping->estimated_days !== null) {
                    $order->estimated_delivery_date = date('Y-m-d', strtotime('+' . $mapping->estimated_days . ' days'));
                }
            }
        }

        $order->save(false);

        Yii::info(
            sprintf('[Webhook DP] Статус заказа #%d обновлён: %s → %s', $order->id, $oldStatus, $newStatus),
            'dp-webhook'
        );

        return ['ok' => true, 'order_id' => $order->id, 'status' => $newStatus];
    }
}
