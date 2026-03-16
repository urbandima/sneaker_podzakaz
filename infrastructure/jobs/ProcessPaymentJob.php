<?php

namespace app\infrastructure\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\backend\payment\PaymentService;

/**
 * Задача обработки платежа
 */
class ProcessPaymentJob extends BaseObject implements JobInterface
{
    public int $orderId;
    public string $gateway;
    public array $paymentData = [];

    public function execute($queue)
    {
        $paymentService = \Yii::$container->get(PaymentService::class);
        
        $result = $paymentService->processPayment(
            $this->orderId,
            $this->gateway,
            $this->paymentData
        );

        if (!$result->isSuccess()) {
            // Логируем ошибку
            \Yii::error([
                'order_id' => $this->orderId,
                'gateway' => $this->gateway,
                'error' => $result->getError(),
            ], 'payment');
            
            // Повторная попытка через 5 минут
            throw new \Exception('Платёж не прошёл: ' . $result->getError());
        }

        return true;
    }

    /**
     * Создать задачу обработки платежа
     */
    public static function create(int $orderId, string $gateway, array $paymentData = []): self
    {
        return new self([
            'orderId' => $orderId,
            'gateway' => $gateway,
            'paymentData' => $paymentData,
        ]);
    }
}
