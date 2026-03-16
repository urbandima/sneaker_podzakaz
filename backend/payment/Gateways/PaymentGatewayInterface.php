<?php

namespace app\backend\payment\Gateways;

use app\backend\modules\checkout\models\Order;

/**
 * Интерфейс платежного шлюза
 */
interface PaymentGatewayInterface
{
    /**
     * Создать платёж
     */
    public function createPayment(Order $order): PaymentResult;

    /**
     * Обработать платёж
     */
    public function processPayment(Order $order, array $paymentData): PaymentResult;

    /**
     * Обработать вебхук
     */
    public function handleWebhook(array $data): PaymentResult;

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): PaymentStatus;

    /**
     * Вернуть платёж
     */
    public function refundPayment(string $paymentId, float $amount): PaymentResult;
}
