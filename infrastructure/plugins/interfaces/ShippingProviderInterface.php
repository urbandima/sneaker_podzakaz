<?php

namespace app\infrastructure\plugins\interfaces;

interface ShippingProviderInterface extends PluginInterface
{
    /**
     * Расчёт стоимости доставки
     */
    public function calculateShippingCost(array $data): array;
    
    /**
     * Создание заявки на доставку
     */
    public function createShipment(array $data): array;
    
    /**
     * Отслеживание посылки
     */
    public function trackShipment(string $trackingNumber): array;
    
    /**
     * Отмена доставки
     */
    public function cancelShipment(string $shipmentId): bool;
    
    /**
     * Получение пунктов выдачи
     */
    public function getPickupPoints(string $city): array;
    
    /**
     * Поддерживаемые города
     */
    public function getSupportedCities(): array;
    
    /**
     * Время доставки (в днях)
     */
    public function getDeliveryTime(string $fromCity, string $toCity): int;
}
