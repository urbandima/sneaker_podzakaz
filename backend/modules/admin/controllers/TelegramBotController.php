<?php
/**
 * TelegramBotController — Интеграция с Telegram
 *
 * B10.1: Уведомления клиентам о смене статуса заказа
 * B10.2: Команды бота /status, /support
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\checkout\models\Order;

class TelegramBotController extends BaseAdminController
{
    public $enableCsrfValidation = false;

    /**
     * Webhook для входящих сообщений от Telegram
     */
    public function actionWebhook()
    {
        $update = json_decode(Yii::$app->request->getRawBody(), true);
        if (!$update) return;

        $message = $update['message'] ?? null;
        $callback = $update['callback_query'] ?? null;

        if ($message) {
            $this->handleMessage($message);
        } elseif ($callback) {
            $this->handleCallback($callback);
        }

        return 'OK';
    }

    /**
     * Обработка текстовых сообщений
     */
    private function handleMessage($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $command = strtolower(explode(' ', $text)[0]);

        switch ($command) {
            case '/start':
                $this->sendMessage($chatId, 
                    "👋 Добро пожаловать в СНИКЕРХЭД!\n\n" .
                    "Отправьте номер заказа для проверки статуса\n" .
                    "или используйте команды:\n" .
                    "/status НОМЕР — проверить статус заказа\n" .
                    "/support — связаться с поддержкой");
                break;

            case '/status':
                $orderNumber = trim(substr($text, 7));
                $this->sendOrderStatus($chatId, $orderNumber);
                break;

            case '/support':
                $this->sendMessage($chatId,
                    "📞 Контакты поддержки:\n" .
                    "Телефон: +375 (29) 123-45-67\n" .
                    "Email: info@sneakerhead.by\n" .
                    "Время работы: Пн-Вс 10:00-22:00");
                break;

            default:
                // Пробуем найти заказ по номеру
                if (preg_match('/^\d+$/', $text)) {
                    $this->sendOrderStatus($chatId, $text);
                } else {
                    $this->sendMessage($chatId,
                        "❓ Не понял команду.\n" .
                        "Отправьте номер заказа или используйте /start");
                }
        }
    }

    /**
     * Отправить статус заказа
     */
    private function sendOrderStatus($chatId, $orderNumber)
    {
        if (empty($orderNumber)) {
            $this->sendMessage($chatId, "❌ Укажите номер заказа\nПример: /status 12345");
            return;
        }

        $order = Order::find()
            ->where(['order_number' => $orderNumber])
            ->one();

        if (!$order) {
            $this->sendMessage($chatId, "❌ Заказ №{$orderNumber} не найден");
            return;
        }

        $statusEmoji = [
            'new' => '📝',
            'paid' => '💰',
            'confirmed_and_paid' => '✅',
            'ordered' => '📦',
            'awaiting_warehouse' => '🏭',
            'international_delivery' => '✈️',
            'at_warehouse' => '🏠',
            'local_delivery' => '🚚',
            'delivered' => '🎉',
            'canceled' => '❌',
        ];

        $emoji = $statusEmoji[$order->status] ?? '📋';
        $statusLabel = $order->getStatusLabel();

        $message = "{$emoji} Заказ №{$order->order_number}\n\n";
        $message .= "Статус: {$statusLabel}\n";
        $message .= "Клиент: {$order->client_name}\n";
        $message .= "Сумма: " . Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') . "\n";
        
        if ($order->track_number) {
            $message .= "\nТрек-номер: {$order->track_number}\n";
            $message .= "Отслеживание: https://t.belpost.by/#{$order->track_number}";
        }

        $this->sendMessage($chatId, $message);
    }

    /**
     * Отправить сообщение в Telegram
     */
    public function sendMessage($chatId, $text, $keyboard = null)
    {
        $botToken = Yii::$app->settings->get('telegram', 'bot_token', '');
        if (empty($botToken)) return false;

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * B10.1: Отправить уведомление о смене статуса
     */
    public function actionNotifyStatus($orderId, $newStatus)
    {
        $order = Order::findOne($orderId);
        if (!$order || empty($order->client_telegram)) return;

        $statusMessages = [
            'paid' => '💰 Оплата получена. Ожидаем паспортные данные.',
            'confirmed_and_paid' => '✅ Заказ подтвержден! Заказаем товар у поставщика.',
            'ordered' => '📦 Товар заказан у поставщика. Ожидаем доставку.',
            'awaiting_warehouse' => '🏭 Товар ожидается на международном складе.',
            'international_delivery' => '✈️ Заказ в международной доставке!',
            'at_warehouse' => '🏠 Заказ на складе в Беларуси.',
            'local_delivery' => '🚚 Заказ передан в доставку!',
            'delivered' => '🎉 Заказ выдан! Спасибо за покупку!',
        ];

        $message = $statusMessages[$newStatus] ?? 'Статус заказа обновлен.';
        $message .= "\n\nЗаказ №{$order->order_number}\n";
        $message .= "Статус: {$order->getStatusLabel()}";

        if ($order->track_number) {
            $message .= "\n\nТрек-номер: {$order->track_number}";
        }

        $this->sendMessage($order->client_telegram, $message);
    }

    /**
     * Установить webhook
     */
    public function actionSetWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $botToken = Yii::$app->settings->get('telegram', 'bot_token', '');
        $webhookUrl = Yii::$app->urlManager->createAbsoluteUrl(['/admin/telegram-bot/webhook']);

        if (empty($botToken)) {
            return ['success' => false, 'message' => 'Bot token не настроен'];
        }

        $url = "https://api.telegram.org/bot{$botToken}/setWebhook";
        $response = file_get_contents($url . '?url=' . urlencode($webhookUrl));
        $result = json_decode($response, true);

        return [
            'success' => $result['ok'] ?? false,
            'message' => $result['description'] ?? 'Unknown error',
            'webhook_url' => $webhookUrl,
        ];
    }

    private function handleCallback($callback)
    {
        // Обработка inline кнопок
    }
}
