<?php

namespace app\backend\payment\Gateways;

use app\backend\modules\checkout\models\Order;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;

/**
 * Платежный шлюз Stripe
 */
class StripeGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $webhookSecret;

    public function __construct(string $secretKey, string $webhookSecret)
    {
        $this->secretKey = $secretKey;
        $this->webhookSecret = $webhookSecret;
        Stripe::setApiKey($secretKey);
    }

    public function createPayment(Order $order): PaymentResult
    {
        try {
            $intent = PaymentIntent::create([
                'amount' => $order->total * 100, // в центах
                'currency' => 'byn',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            return new PaymentResult(true, 'Платёж создан', [
                'payment_intent' => $intent->id,
                'client_secret' => $intent->client_secret,
            ]);
        } catch (\Exception $e) {
            return new PaymentResult(false, $e->getMessage());
        }
    }

    public function processPayment(Order $order, array $paymentData): PaymentResult
    {
        try {
            $intent = PaymentIntent::retrieve($paymentData['payment_intent']);

            if ($intent->status === 'succeeded') {
                $order->updateStatus(Order::STATUS_PAID);
                return new PaymentResult(true, 'Оплата успешна');
            }

            return new PaymentResult(false, 'Оплата не завершена');
        } catch (\Exception $e) {
            return new PaymentResult(false, $e->getMessage());
        }
    }

    public function handleWebhook(array $data): PaymentResult
    {
        try {
            $event = Webhook::constructEvent(
                file_get_contents('php://input'),
                $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '',
                $this->webhookSecret
            );

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $orderId = $paymentIntent->metadata->order_id;
                    $order = Order::findOne($orderId);
                    if ($order) {
                        $order->updateStatus(Order::STATUS_PAID);
                    }
                    break;
            }

            return new PaymentResult(true, 'Вебхук обработан');
        } catch (\Exception $e) {
            return new PaymentResult(false, $e->getMessage());
        }
    }

    public function getPaymentStatus(string $paymentId): PaymentStatus
    {
        $intent = PaymentIntent::retrieve($paymentId);
        
        return new PaymentStatus(
            $intent->id,
            $intent->status,
            $intent->amount / 100
        );
    }

    public function refundPayment(string $paymentId, float $amount): PaymentResult
    {
        try {
            \Stripe\Refund::create([
                'payment_intent' => $paymentId,
                'amount' => $amount * 100,
            ]);

            return new PaymentResult(true, 'Возврат выполнен');
        } catch (\Exception $e) {
            return new PaymentResult(false, $e->getMessage());
        }
    }
}
