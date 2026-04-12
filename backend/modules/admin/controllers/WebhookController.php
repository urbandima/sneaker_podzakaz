<?php
/**
 * WebhookController - обработка webhook от внешних сервисов
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\DeliveryProvider;
use app\backend\modules\checkout\models\DeliveryStatusMapping;

class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Проверяет HMAC-подпись входящего вебхука.
     * Секрет берётся из настроек: settings.get('webhook', 'secret').
     */
    private function verifySignature(string $payload, string $headerName = 'X-Webhook-Signature'): bool
    {
        $secret = Yii::$app->settings->get('webhook', 'secret', '');
        if (empty($secret)) {
            // Если секрет не настроен — логируем предупреждение, но пропускаем
            Yii::warning('Webhook secret not configured — signature check skipped', 'webhook');
            return true;
        }
        $provided = Yii::$app->request->headers->get($headerName, '');
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $provided);
    }

    // =========================================================================
    // Таможня:ДП webhook
    // =========================================================================

    /**
     * POST /api/webhook/dobropost
     *
     * Принимает два типа событий от Таможня:ДП:
     *  1. Обновление статуса: { shipmentId, DPTrackNumber, statusDate, status }
     *  2. Результат проверки паспорта: { shipmentId, statusDate, passportValidationStatus }
     */
    public function actionDobropost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->statusCode = 200;

        $rawBody = file_get_contents('php://input');

        Yii::info('Таможня:ДП webhook: ' . $rawBody, 'dp-webhook');

        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            Yii::warning('Таможня:ДП webhook: некорректный JSON', 'dp-webhook');
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'error' => 'Invalid JSON'];
        }

        $shipmentId = $data['shipmentId'] ?? null;
        if (!$shipmentId) {
            Yii::warning('Таможня:ДП webhook: отсутствует shipmentId', 'dp-webhook');
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'error' => 'Missing shipmentId'];
        }

        /** @var Order|null $order */
        $order = Order::find()->where(['dp_shipment_id' => (int) $shipmentId])->one();
        if (!$order) {
            Yii::warning('Таможня:ДП webhook: заказ с dp_shipment_id=' . $shipmentId . ' не найден', 'dp-webhook');
            // Отвечаем 200 чтобы ДП не повторял хук
            return ['success' => true, 'message' => 'Order not found, ignored'];
        }

        // --- Тип 1: Проверка паспорта ---
        if (array_key_exists('passportValidationStatus', $data)) {
            $validated = (bool) $data['passportValidationStatus'];
            $order->passport_validated     = $validated ? 1 : 0;
            $order->passport_submitted_at  = $order->passport_submitted_at ?: time();

            if (!$order->save(false)) {
                Yii::error('Таможня:ДП webhook: ошибка сохранения passport_validated для заказа #' . $order->id, 'dp-webhook');
                Yii::$app->response->statusCode = 500;
                return ['success' => false, 'error' => 'Save failed'];
            }

            Yii::info(
                sprintf('Таможня:ДП webhook: паспорт заказа #%d %s', $order->id, $validated ? 'подтверждён' : 'отклонён'),
                'dp-webhook'
            );
            return ['success' => true];
        }

        // --- Тип 2: Обновление статуса ---
        $providerStatus = (string) ($data['status'] ?? '');
        $statusDate     = $data['statusDate']     ?? null;
        $trackNumber    = $data['DPTrackNumber']  ?? null;

        if ($trackNumber && empty($order->dp_track_number)) {
            $order->dp_track_number = $trackNumber;
        }

        if ($providerStatus !== '') {
            $order->dp_status = $providerStatus;
        }

        if ($statusDate) {
            try {
                $order->dp_status_date = date('Y-m-d H:i:s', strtotime($statusDate));
            } catch (\Throwable $e) {
                // Некорректная дата — игнорируем
            }
        }

        // Пытаемся вычислить estimated_delivery_date через маппинг
        // Ищем провайдера Таможня:ДП для получения provider_id
        $dpProvider = DeliveryProvider::findByCode('dobropost');
        if ($dpProvider) {
            $mapping = $dpProvider->mapStatus($providerStatus);
            if ($mapping && $mapping->estimated_days !== null) {
                $order->estimated_delivery_date = date('Y-m-d', strtotime('+' . $mapping->estimated_days . ' days'));
            }
        }

        if (!$order->save(false)) {
            Yii::error('Таможня:ДП webhook: ошибка сохранения статуса заказа #' . $order->id, 'dp-webhook');
            Yii::$app->response->statusCode = 500;
            return ['success' => false, 'error' => 'Save failed'];
        }

        Yii::info(
            sprintf('Таможня:ДП webhook: статус заказа #%d обновлён → %s', $order->id, $providerStatus),
            'dp-webhook'
        );
        return ['success' => true];
    }

    // =========================================================================

    /**
     * Webhook от МойСклад
     */
    public function actionMoysklad()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $payload = file_get_contents('php://input');

        if (!$this->verifySignature($payload)) {
            Yii::$app->response->statusCode = 403;
            return ['success' => false, 'error' => 'Invalid signature'];
        }

        $data = json_decode($payload, true);

        // Логирование
        Yii::info('МойСклад webhook: ' . $payload, 'moysklad');

        if (!$data) {
            return ['success' => false, 'error' => 'Invalid JSON'];
        }
        
        // Обработка события
        $action = $data['action'] ?? null;
        $entityType = $data['meta']['type'] ?? null;
        
        try {
            switch ($action) {
                case 'CREATE':
                    return $this->handleMoyskladCreate($entityType, $data);
                case 'UPDATE':
                    return $this->handleMoyskladUpdate($entityType, $data);
                case 'DELETE':
                    return $this->handleMoyskladDelete($entityType, $data);
                default:
                    return ['success' => false, 'error' => 'Unknown action'];
            }
        } catch (\Exception $e) {
            Yii::error('МойСклад webhook error: ' . $e->getMessage(), 'moysklad');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Обработка создания сущности в МойСклад
     */
    private function handleMoyskladCreate($type, $data)
    {
        if ($type === 'customerorder') {
            // Создан новый заказ в МойСклад - синхронизируем
            $externalId = $data['id'] ?? null;
            
            // Проверяем, не создан ли уже заказ
            $order = Order::find()->where(['external_id' => $externalId])->one();
            if ($order) {
                return ['success' => true, 'message' => 'Order already exists'];
            }
            
            // Создаем новый заказ
            // ... логика создания заказа
            
            return ['success' => true, 'message' => 'Order created'];
        }
        
        return ['success' => true, 'message' => 'Event processed'];
    }
    
    /**
     * Обработка обновления сущности в МойСклад
     */
    private function handleMoyskladUpdate($type, $data)
    {
        if ($type === 'customerorder') {
            $externalId = $data['id'] ?? null;
            $order = Order::find()->where(['external_id' => $externalId])->one();
            
            if ($order) {
                // Обновляем статус заказа
                $state = $data['state']['name'] ?? null;
                if ($state) {
                    $order->status = $this->mapMoyskladStatus($state);
                    $order->save();
                }
            }
            
            return ['success' => true, 'message' => 'Order updated'];
        }
        
        return ['success' => true, 'message' => 'Event processed'];
    }
    
    /**
     * Обработка удаления сущности в МойСклад
     */
    private function handleMoyskladDelete($type, $data)
    {
        return ['success' => true, 'message' => 'Delete event processed'];
    }
    
    /**
     * Маппинг статусов МойСклад -> наша система
     */
    private function mapMoyskladStatus($moyskladStatus)
    {
        $map = [
            'Новый' => 'new',
            'Подтвержден' => 'confirmed_and_paid',
            'Оплачен' => 'paid',
            'Заказан' => 'ordered',
            'Ожидается на складе' => 'awaiting_warehouse',
            'В доставке' => 'international_delivery',
            'На складе' => 'at_warehouse',
            'Доставка по РБ' => 'local_delivery',
            'Доставлен' => 'delivered',
            'Отменен' => 'canceled',
        ];
        
        return $map[$moyskladStatus] ?? 'new';
    }
    
    /**
     * Webhook от AmoCRM
     */
    public function actionAmocrm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        Yii::info('AmoCRM webhook: ' . $payload, 'amocrm');
        
        // Обработка события AmoCRM
        // ...
        
        return ['success' => true];
    }
    
    /**
     * Webhook от Telegram
     */
    public function actionTelegram()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $payload = file_get_contents('php://input');
        $update = json_decode($payload, true);
        
        Yii::info('Telegram webhook: ' . $payload, 'telegram');
        
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            
            // Обработка команд
            if (strpos($text, '/') === 0) {
                return $this->handleTelegramCommand($chatId, $text);
            }
        }
        
        return ['ok' => true];
    }
    
    /**
     * Обработка команд Telegram
     */
    private function handleTelegramCommand($chatId, $command)
    {
        switch ($command) {
            case '/start':
                $this->sendTelegramMessage($chatId, 'Добро пожаловать! Используйте /help для списка команд.');
                break;
            case '/orders':
                $newOrders = Order::find()->where(['status' => 'new'])->count();
                $this->sendTelegramMessage($chatId, "Новых заказов: {$newOrders}");
                break;
            case '/stats':
                $today = Order::find()->where(['>=', 'created_at', strtotime('today')])->count();
                $this->sendTelegramMessage($chatId, "Заказов сегодня: {$today}");
                break;
            case '/help':
                $help = "Доступные команды:\n";
                $help .= "/orders - Список новых заказов\n";
                $help .= "/stats - Статистика за сегодня\n";
                $help .= "/help - Эта справка";
                $this->sendTelegramMessage($chatId, $help);
                break;
        }
        
        return ['ok' => true];
    }
    
    /**
     * Отправка сообщения в Telegram
     */
    private function sendTelegramMessage($chatId, $text)
    {
        $botToken = Yii::$app->settings->get('telegram', 'bot_token');
        if (!$botToken) {
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
}
