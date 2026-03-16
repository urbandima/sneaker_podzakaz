<?php

namespace app\backend\payment;

use app\backend\modules\checkout\models\Order;
use app\backend\payment\Gateways\PaymentGatewayInterface;

/**
 * Сервис платежей
 */
class PaymentService
{
    private array $gateways = [];

    public function __construct(array $gateways = [])
    {
        foreach ($gateways as $name => $gateway) {
            $this->registerGateway($name, $gateway);
        }
    }

    /**
     * Зарегистрировать платежный шлюз
     */
    public function registerGateway(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    /**
     * Получить шлюз
     */
    public function getGateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Платежный шлюз '{$name}' не найден");
        }
        return $this->gateways[$name];
    }

    /**
     * Создать платёж
     */
    public function createPayment(Order $order, string $gatewayName): PaymentResult
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->createPayment($order);
    }

    /**
     * Обработать платёж
     */
    public function processPayment(int $orderId, string $gatewayName, array $paymentData): PaymentResult
    {
        $order = Order::findOne($orderId);
        if (!$order) {
            return new PaymentResult(false, 'Заказ не найден');
        }

        $gateway = $this->getGateway($gatewayName);
        return $gateway->processPayment($order, $paymentData);
    }

    /**
     * Обработать вебхук
     */
    public function handleWebhook(string $gatewayName, array $data): PaymentResult
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->handleWebhook($data);
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $gatewayName, string $paymentId): PaymentStatus
    {
        $gateway = $this->getGateway($gatewayName);
        return $gateway->getPaymentStatus($paymentId);
    }
}
