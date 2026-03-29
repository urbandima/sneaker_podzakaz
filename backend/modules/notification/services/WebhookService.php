<?php

/**
 * WebhookService — Сервис webhook-уведомлений для внешних систем
 * 
 * НАЗНАЧЕНИЕ:
 * Отправка webhook-уведомлений при событиях: заказ создан, статус изменён, возврат
 */
namespace app\backend\modules\notification\services;

use Yii;
use yii\base\Component;
use yii\helpers\Json;

class WebhookService extends Component
{
    /** @var array URL вебхуков */
    public $endpoints = [];

    /** @var string Секрет для подписи */
    public $secret;

    /**
     * Отправить webhook о событии
     * 
     * @param string $event Тип события
     * @param array $data Данные
     * @return array Результаты отправки
     */
    public function send(string $event, array $data): array
    {
        $results = [];
        $payload = $this->buildPayload($event, $data);

        foreach ($this->endpoints as $name => $config) {
            if (!$this->shouldSendForEvent($config, $event)) {
                continue;
            }

            $result = $this->dispatch($config['url'], $payload);
            $results[$name] = $result;

            // Логируем
            $this->logWebhook($event, $config['url'], $result);
        }

        return $results;
    }

    /**
     * Отправить webhook о заказе
     */
    public function sendOrderCreated($order): bool
    {
        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total_amount,
            'status' => $order->status,
            'customer' => [
                'name' => $order->client_name,
                'phone' => $order->client_phone,
                'email' => $order->client_email,
            ],
            'items' => [],
        ];

        foreach ($order->orderItems as $item) {
            $data['items'][] = [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        }

        $results = $this->send('order.created', $data);
        return !empty($results);
    }

    /**
     * Отправить webhook об изменении статуса
     */
    public function sendOrderStatusChanged($order, string $oldStatus, string $newStatus): bool
    {
        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_at' => time(),
        ];

        $results = $this->send('order.status_changed', $data);
        return !empty($results);
    }

    /**
     * Отправить webhook о возврате
     */
    public function sendReturnCreated($return): bool
    {
        $data = [
            'return_id' => $return->id,
            'return_number' => $return->return_number,
            'order_id' => $return->order_id,
            'status' => $return->status,
            'refund_amount' => $return->refund_amount,
        ];

        $results = $this->send('return.created', $data);
        return !empty($results);
    }

    /**
     * Построить payload
     */
    private function buildPayload(string $event, array $data): array
    {
        return [
            'event' => $event,
            'timestamp' => time(),
            'data' => $data,
        ];
    }

    /**
     * Отправить запрос
     */
    private function dispatch(string $url, array $payload): array
    {
        $json = Json::encode($payload);
        $signature = $this->sign($json);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Webhook-Signature: ' . $signature,
            'X-Webhook-Event: ' . $payload['event'],
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $error = curl_error($ch);
        curl_close($ch);

        $success = $httpCode >= 200 && $httpCode < 300;

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'duration_ms' => $duration,
            'response' => $response,
            'error' => $error,
        ];
    }

    /**
     * Подписать payload
     */
    private function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret ?? 'default-secret');
    }

    /**
     * Проверить, нужно ли отправлять для события
     */
    private function shouldSendForEvent(array $config, string $event): bool
    {
        if (empty($config['events'])) {
            return true; // Все события
        }

        // Поддержка wildcard: order.*
        foreach ($config['events'] as $pattern) {
            if (fnmatch($pattern, $event)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Логировать webhook
     */
    private function logWebhook(string $event, string $url, array $result): void
    {
        $level = $result['success'] ? 'info' : 'error';
        Yii::$level([
            'event' => $event,
            'url' => $url,
            'success' => $result['success'],
            'http_code' => $result['http_code'],
            'duration_ms' => $result['duration_ms'],
        ], 'webhook');
    }
}
