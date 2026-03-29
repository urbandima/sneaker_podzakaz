<?php

namespace app\infrastructure\plugins\interfaces;

interface PaymentGatewayInterface extends PluginInterface
{
    /**
     * Создание платежа
     */
    public function createPayment(array $data): array;
    
    /**
     * Проверка статуса платежа
     */
    public function checkPaymentStatus(string $paymentId): array;
    
    /**
     * Возврат платежа
     */
    public function refundPayment(string $paymentId, float $amount): array;
    
    /**
     * Получение URL для оплаты
     */
    public function getPaymentUrl(string $paymentId): string;
    
    /**
     * Обработка webhook от платёжной системы
     */
    public function handleWebhook(array $data): array;
    
    /**
     * Поддерживаемые валюты
     */
    public function getSupportedCurrencies(): array;
    
    /**
     * Минимальная сумма платежа
     */
    public function getMinAmount(): float;
    
    /**
     * Максимальная сумма платежа
     */
    public function getMaxAmount(): float;
}
