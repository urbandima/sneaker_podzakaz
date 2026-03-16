<?php

namespace app\backend\payment\Gateways;

use app\backend\modules\checkout\models\Order;
use YooKassa\Client;

/**
 * Платежный шлюз Яндекс.Касса (YooKassa)
 */
class YandexKassaGateway implements PaymentGatewayInterface
{
    private Client $client;

    public function __construct(string $shopId, string $secretKey)
    {
        $this->client = new Client();
        $this->client->setAuth($shopId, $secretKey);
    }

    public function createPayment(Order $order): PaymentResult
    {
        try {
            $payment = $this->client->createPayment([
                'amount' => [
                    'value' => $order->total,
                    'currency' => 'BYN',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => \Yii::$app->params['siteUrl'] . '/checkout/success',
                ],
                'capture' => true,
                'description' => "Заказ #{$order->order_number}",
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ], uniqid('', true));

            return new PaymentResult(true, 'Платёж создан', [
                'payment_id' => $payment->getId(),
                'confirmation_url' => $payment->getConfirmation()->getConfirmationUrl(),
            ]);
        } catch (\Exception $e) {
            return new PaymentResult(false, $e->getMessage());
        }
    }

    public function processPayment(Order $order, array $paymentData): PaymentResult
    {
        try {
            $payment = $this->client->getPaymentInfo($paymentData['payment_id']);

            if ($payment->getStatus() === 'succeeded') {
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
            $payment = $this->client->getPaymentInfo($data['object']['id']);
            
            if ($payment->getStatus() === 'succeeded') {
                $orderId = $payment->getMetadata()['order_id'];
                $order = Order::findOne($orderId);
                if ($order) {
                    $order->updateStatus(Order::STATUS_PAID);
                }
            }

            return new PaymentResult(true, 'Вебхук обработан');
        } catch (\Exception $e) {
            return new PaymentResult(false, $e->getMessage());
        }
    }

    public function getPaymentStatus(string $paymentId): PaymentStatus
    {
        $payment = $this->client->getPaymentInfo($paymentId);
        
        return new PaymentStatus(
            $payment->getId(),
            $payment->getStatus(),
            $payment->getAmount()->getValue()
        );
    }

    public function refundPayment(string $paymentId, float $amount): PaymentResult
    {
        try {
            $this->client->createRefund([
                'payment_id' => $paymentId,
                'amount' => [
                    'value' => $amount,
                    'currency' => 'BYN',
                ],
            ]);

            return new PaymentResult(true, 'Возврат выполнен');
        } catch (\Exception $e) {
            return new PaymentResult(false, $e->getMessage());
        }
    }
}
