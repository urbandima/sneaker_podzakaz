<?php

namespace app\infrastructure\plugins\payment;

use app\infrastructure\plugins\base\BasePlugin;
use app\infrastructure\plugins\interfaces\PaymentGatewayInterface;

class YooKassaPaymentPlugin extends BasePlugin implements PaymentGatewayInterface
{
    protected $id = 'yookassa';
    protected $name = 'ЮKassa (Яндекс.Касса)';
    protected $description = 'Приём платежей через ЮKassa для РБ и РФ';
    protected $version = '1.0.0';
    protected $author = 'Sneakerhead Team';
    
    public function init(): void
    {
        // Инициализация YooKassa SDK
    }
    
    public function createPayment(array $data): array
    {
        $shopId = $this->getSetting('shop_id');
        $secretKey = $this->getSetting('secret_key');
        $amount = $data['amount'];
        $currency = $data['currency'] ?? 'BYN';
        
        return [
            'success' => true,
            'payment_id' => uniqid('yk_'),
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency,
        ];
    }
    
    public function checkPaymentStatus(string $paymentId): array
    {
        return [
            'success' => true,
            'status' => 'succeeded',
            'payment_id' => $paymentId,
        ];
    }
    
    public function refundPayment(string $paymentId, float $amount): array
    {
        return [
            'success' => true,
            'refund_id' => uniqid('ref_'),
            'amount' => $amount,
        ];
    }
    
    public function getPaymentUrl(string $paymentId): string
    {
        return 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=' . $paymentId;
    }
    
    public function handleWebhook(array $data): array
    {
        $event = $data['event'] ?? '';
        
        switch ($event) {
            case 'payment.succeeded':
                return ['status' => 'completed'];
            case 'payment.canceled':
                return ['status' => 'canceled'];
            default:
                return ['status' => 'unknown'];
        }
    }
    
    public function getSupportedCurrencies(): array
    {
        return ['RUB', 'BYN'];
    }
    
    public function getMinAmount(): float
    {
        return 1.0;
    }
    
    public function getMaxAmount(): float
    {
        return 500000.0;
    }
}
