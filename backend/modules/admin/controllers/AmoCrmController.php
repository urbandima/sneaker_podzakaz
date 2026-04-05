<?php
/**
 * AmoCrmController — Интеграция с AmoCRM
 *
 * B13.1: Создание контакта и сделки при заказе
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;

class AmoCrmController extends BaseAdminController
{
    private $apiUrl = 'https://';
    private $subdomain;

    public function init()
    {
        parent::init();
        $this->subdomain = Yii::$app->settings->get('amocrm', 'subdomain', '');
        $this->apiUrl = "https://{$this->subdomain}.amocrm.ru/api/v4";
    }

    /**
     * Создание контакта и сделки при заказе
     */
    public function actionCreateDeal($orderId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $accessToken = Yii::$app->settings->get('amocrm', 'access_token', '');
        if (empty($accessToken)) {
            return ['success' => false, 'message' => 'AmoCRM не настроена'];
        }

        try {
            // Создаем или находим контакт
            $contactId = $this->createOrFindContact($accessToken, $order);

            // Создаем сделку
            $dealId = $this->createDeal($accessToken, $order, $contactId);

            // Сохраняем ID сделки в заказе
            $order->amocrm_deal_id = $dealId;
            $order->save(false);

            return [
                'success' => true,
                'message' => 'Сделка создана в AmoCRM',
                'deal_id' => $dealId,
                'contact_id' => $contactId,
            ];

        } catch (\Exception $e) {
            Yii::error('AmoCRM error: ' . $e->getMessage(), 'admin');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Обновление статуса сделки в AmoCRM
     */
    public function actionUpdateStatus($orderId)
    {
        $order = Order::findOne($orderId);
        if (!$order || !$order->amocrm_deal_id) {
            return ['success' => false, 'message' => 'Сделка не найдена'];
        }

        $accessToken = Yii::$app->settings->get('amocrm', 'access_token', '');
        $pipelineId = Yii::$app->settings->get('amocrm', 'pipeline_id', '');

        $statusMap = [
            'created' => 1,
            'confirmed' => 2,
            'paid' => 3,
            'ordered' => 4,
            'shipped' => 5,
            'delivered' => 6,
            'completed' => 7,
        ];

        $statusId = $statusMap[$order->status] ?? 1;

        $data = [
            'id' => (int)$order->amocrm_deal_id,
            'status_id' => $statusId,
            'pipeline_id' => (int)$pipelineId,
        ];

        $ch = curl_init($this->apiUrl . '/leads');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$data]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return ['success' => true, 'response' => json_decode($response, true)];
    }

    /**
     * Создать или найти контакт
     */
    private function createOrFindContact($token, $order)
    {
        // Поиск по телефону
        $searchUrl = $this->apiUrl . '/contacts?query=' . urlencode($order->client_phone);

        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (!empty($data['_embedded']['contacts'])) {
            return $data['_embedded']['contacts'][0]['id'];
        }

        // Создаем новый контакт
        $contactData = [
            'name' => $order->client_name,
            'custom_fields_values' => [
                [
                    'field_code' => 'PHONE',
                    'values' => [['value' => $order->client_phone]],
                ],
                [
                    'field_code' => 'EMAIL',
                    'values' => [['value' => $order->client_email ?? '']],
                ],
            ],
        ];

        $ch = curl_init($this->apiUrl . '/contacts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$contactData]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['_embedded']['contacts'][0]['id'] ?? null;
    }

    /**
     * Создать сделку
     */
    private function createDeal($token, $order, $contactId)
    {
        $pipelineId = Yii::$app->settings->get('amocrm', 'pipeline_id', '');

        $dealData = [
            'name' => 'Заказ #' . $order->order_number,
            'price' => (float)$order->total_amount,
            'pipeline_id' => (int)$pipelineId,
            'status_id' => 1,
            'contacts_id' => [$contactId],
            'custom_fields_values' => [
                [
                    'field_id' => 1,
                    'values' => [['value' => $order->id]],
                ],
            ],
        ];

        $ch = curl_init($this->apiUrl . '/leads');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$dealData]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['_embedded']['leads'][0]['id'] ?? null;
    }

    /**
     * Страница настроек
     */
    public function actionSettings()
    {
        return $this->render('settings');
    }
}
