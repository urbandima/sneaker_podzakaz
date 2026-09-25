<?php

/**
 * AmoCrmController — Интеграция с AmoCRM
 *
 * B13.1: Создание контакта и сделки при заказе
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\filters\VerbFilter;
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
     * CMP-418: actionCreateDeal takes only $orderId from the route, writes
     * $order->amocrm_deal_id unconditionally, and creates a live contact+deal in
     * AmoCRM — no isPost/VerbFilter guard, GET-CSRF exploitable via a bare link.
     * actionUpdateStatus restricted for the same reason (live external CRM write
     * tied to a real order, driven only by route $orderId).
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs']['actions']['create-deal'] = ['POST'];
        $behaviors['verbs']['actions']['update-status'] = ['POST'];
        return $behaviors;
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
        // CMP-470-B: this action returned a bare PHP array with the response
        // format never set to JSON — Yii's default HTML formatter can't render an
        // array, so EVERY call (regardless of AmoCRM credentials) 500'd with a
        // generic "Ошибка сервера" page instead of ever reaching the caller with
        // a usable response (confirmed live). Fixed below, matching
        // actionCreateDeal's pattern in this same file.
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($orderId);
        if (!$order || !$order->amocrm_deal_id) {
            return ['success' => false, 'message' => 'Сделка не найдена'];
        }

        $accessToken = Yii::$app->settings->get('amocrm', 'access_token', '');
        if (empty($accessToken)) {
            // CMP-470-B: previously absent — with no token configured, execution
            // fell straight through to curl_init()/curl_exec() against
            // "{$this->apiUrl}/leads" (an invalid host when no subdomain is
            // configured) instead of failing fast like actionCreateDeal does.
            return ['success' => false, 'message' => 'AmoCRM не настроена'];
        }
        $pipelineId = Yii::$app->settings->get('amocrm', 'pipeline_id', '');

        $statusMap = [
            'new' => 1,
            'paid' => 2,
            'confirmed_and_paid' => 3,
            'ordered' => 4,
            'awaiting_warehouse' => 5,
            'international_delivery' => 6,
            'at_warehouse' => 7,
            'local_delivery' => 8,
            'delivered' => 9,
            'canceled' => 10,
        ];

        $statusId = $statusMap[$order->status] ?? 1;

        $data = [
            'id' => (int)$order->amocrm_deal_id,
            'status_id' => $statusId,
            'pipeline_id' => (int)$pipelineId,
        ];

        try {
            $ch = curl_init($this->apiUrl . '/leads');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([$data]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            // CMP-470-B: previously curl_exec()===false (network error/timeout)
            // and any 4xx/5xx from AmoCRM were both reported back as
            // ['success' => true, 'response' => null] — a silent "nothing
            // happened" success. Now both are surfaced as failures.
            if ($response === false) {
                return ['success' => false, 'message' => 'Сетевая ошибка AmoCRM: ' . $curlErr];
            }
            if ($httpCode >= 400) {
                return ['success' => false, 'message' => "AmoCRM {$httpCode}: " . mb_substr($response, 0, 300)];
            }

            return ['success' => true, 'response' => json_decode($response, true)];
        } catch (\Throwable $e) {
            Yii::error('AmoCRM updateStatus error: ' . $e->getMessage(), 'admin');
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
     * Страница настроек AmoCRM переехала в PluginController (admin/plugin/amocrm) —
     * там же живут поля, пайплайны, синхронизация и авторизация. У этого экшена
     * никогда не было вида (settings.php не существовал → 500 при заходе), маршрут
     * нигде не выведен в меню/ссылках. Оставляем как redirect, а не удаляем
     * контроллер целиком — actionCreateDeal/actionUpdateStatus остаются нетронутыми,
     * это отдельный вопрос архитектуры (дублирование с AmocrmClient/PluginController).
     */
    public function actionSettings()
    {
        return $this->redirect(['/admin/plugin/amocrm']);
    }
}
