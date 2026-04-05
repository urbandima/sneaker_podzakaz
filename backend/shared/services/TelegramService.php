<?php

namespace app\backend\shared\services;

/**
 * TelegramService — отправка уведомлений через Telegram Bot API.
 *
 * Использование:
 *   TelegramService::send('Текст сообщения');
 *   TelegramService::newOrder($order);
 *   TelegramService::paymentConfirmed($order);
 */
class TelegramService
{
    /**
     * Отправить произвольное сообщение во все настроенные чаты.
     */
    public static function send(string $message): bool
    {
        $token   = \Yii::$app->settings->get('telegram', 'bot_token', '');
        $chatIds = explode(',', \Yii::$app->settings->get('telegram', 'chat_ids', ''));

        if (empty($token)) {
            return false;
        }

        foreach ($chatIds as $chatId) {
            $chatId = trim($chatId);
            if (!$chatId) {
                continue;
            }

            $url  = "https://api.telegram.org/bot{$token}/sendMessage";
            $data = [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            curl_close($ch);
        }

        return true;
    }

    /**
     * Уведомление о новом заказе.
     */
    public static function newOrder($order): void
    {
        $msg  = "🛍 <b>Новый заказ #{$order->order_number}</b>\n";
        $msg .= "Клиент: {$order->client_name}\n";
        $msg .= "Сумма: " . number_format($order->total_amount, 2) . " BYN\n";
        $msg .= "Ссылка: " . \yii\helpers\Url::to(['/admin/order/view', 'id' => $order->id], true);
        self::send($msg);
    }

    /**
     * Уведомление о подтверждении оплаты.
     */
    public static function paymentConfirmed($order): void
    {
        $msg  = "💰 <b>Оплата подтверждена</b>\n";
        $msg .= "Заказ: #{$order->order_number}\n";
        $msg .= "Клиент: {$order->client_name}\n";
        $msg .= "Сумма: " . number_format($order->total_amount, 2) . " BYN\n";
        $msg .= "→ Требует закупки на Poizon\n";
        $msg .= \yii\helpers\Url::to(['/admin/order/view', 'id' => $order->id], true);
        self::send($msg);
    }

    /**
     * Тестовое сообщение для проверки настроек.
     */
    public static function sendTest(): bool
    {
        $msg = "✅ <b>Тест уведомлений SneakerShop</b>\n"
             . "Бот подключён и работает корректно.\n"
             . "Время: " . date('d.m.Y H:i:s');
        return self::send($msg);
    }
}
