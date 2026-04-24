<?php

/**
 * AmocrmController — REST API for the AmoCRM marketplace widget.
 *
 * GET  /api/amocrm/order?external_id=<lead_id>  — order info by AmoCRM lead id
 * POST /api/amocrm/sync                          — sync order → AmoCRM lead
 */

namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;

class AmocrmController extends Controller
{
    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (!$this->checkWidgetAuth()) {
            Yii::$app->response->statusCode = 401;
            Yii::$app->response->data = ['error' => 'Unauthorized'];
            Yii::$app->response->send();
            Yii::$app->end();
        }
        return parent::beforeAction($action);
    }

    /**
     * GET /api/amocrm/order?external_id=<lead_id>
     */
    public function actionOrder(): array
    {
        $leadId = (int)Yii::$app->request->get('external_id', 0);
        if (!$leadId) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'external_id required'];
        }

        $order = Order::findOne(['amocrm_lead_id' => $leadId]);
        if (!$order) {
            return ['found' => false];
        }

        return [
            'found'          => true,
            'order_id'       => $order->id,
            'status'         => $order->status,
            'total'          => (float)($order->total ?? 0),
            'recipient_name' => $order->recipient_name ?? '',
            'phone'          => $order->recipient_phone ?? '',
            'created_at'     => $order->created_at,
            'last_sync_at'   => $order->amocrm_last_sync_at,
        ];
    }

    /**
     * POST /api/amocrm/sync — push order to AmoCRM (create/update lead)
     */
    public function actionSync(): array
    {
        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            return ['error' => 'POST required'];
        }

        $raw  = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true) ?: [];

        $orderId = (int)($data['order_id'] ?? 0);
        $leadId  = (int)($data['lead_id']  ?? 0);

        if (!$orderId && !$leadId) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'order_id or lead_id required'];
        }

        $order = $orderId
            ? Order::findOne($orderId)
            : Order::findOne(['amocrm_lead_id' => $leadId]);

        if (!$order) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Order not found'];
        }

        /** @var \app\backend\shared\components\AmocrmClient $amo */
        $amo = Yii::$app->amocrm;
        if (!$amo->isConfigured()) {
            return ['success' => false, 'message' => 'AmoCRM not configured'];
        }

        if ($order->amocrm_lead_id) {
            $amo->updateLead($order->amocrm_lead_id, [
                'price' => (int)($order->total ?? 0),
            ]);
            $order->amocrm_last_sync_at = time();
            $order->save(false);
            return ['success' => true, 'lead_id' => $order->amocrm_lead_id, 'action' => 'updated'];
        }

        $lead = $amo->createLead([
            'name'  => 'Заказ #' . $order->id . ($order->recipient_name ? ' — ' . $order->recipient_name : ''),
            'price' => (int)($order->total ?? 0),
        ]);
        if (!$lead || empty($lead['id'])) {
            return ['success' => false, 'message' => 'Lead creation failed'];
        }
        $order->amocrm_lead_id      = $lead['id'];
        $order->amocrm_last_sync_at = time();
        $order->save(false);

        return ['success' => true, 'lead_id' => $lead['id'], 'action' => 'created'];
    }

    private function checkWidgetAuth(): bool
    {
        $authHeader = Yii::$app->request->headers->get('Authorization', '');
        if (strncmp($authHeader, 'Bearer ', 7) === 0) {
            $token = substr($authHeader, 7);
            $stored = Yii::$app->settings->get('amocrm', 'widget_api_token', '');
            if ($stored && hash_equals($stored, $token)) return true;
        }
        // Allow from same server (internal calls)
        $ip = Yii::$app->request->userIP;
        if ($ip === '127.0.0.1' || $ip === '::1') return true;

        return false;
    }
}
