<?php

namespace app\infrastructure\plugins\shipping;

use app\infrastructure\plugins\base\BasePlugin;
use app\infrastructure\plugins\interfaces\ShippingProviderInterface;

class BelpostShippingPlugin extends BasePlugin implements ShippingProviderInterface
{
    protected $id = 'belpost';
    protected $name = 'Белпочта';
    protected $description = 'Доставка через Белпочту по всей Беларуси';
    protected $version = '1.0.0';
    protected $author = 'Sneakerhead Team';
    
    public function init(): void
    {
        // Инициализация API Белпочты
    }
    
    public function calculateShippingCost(array $data): array
    {
        $fromCity = $data['from_city'] ?? 'Минск';
        $toCity = $data['to_city'];
        $weight = $data['weight'] ?? 1.0;
        
        $baseCost = 5.0;
        $weightCost = $weight * 2.0;
        $distanceCost = ($fromCity !== $toCity) ? 3.0 : 0;
        
        $totalCost = $baseCost + $weightCost + $distanceCost;
        
        return [
            'success' => true,
            'cost' => $totalCost,
            'currency' => 'BYN',
            'delivery_time' => $this->getDeliveryTime($fromCity, $toCity),
        ];
    }
    
    public function createShipment(array $data): array
    {
        return [
            'success' => true,
            'shipment_id' => 'BP' . time() . rand(1000, 9999),
            'tracking_number' => 'BY' . rand(100000000, 999999999) . 'BY',
            'status' => 'created',
        ];
    }
    
    public function trackShipment(string $trackingNumber): array
    {
        return [
            'success' => true,
            'tracking_number' => $trackingNumber,
            'status' => 'in_transit',
            'current_location' => 'Минск, сортировочный центр',
            'events' => [
                ['date' => date('Y-m-d H:i:s'), 'status' => 'Принято к отправке', 'location' => 'Минск'],
                ['date' => date('Y-m-d H:i:s', strtotime('-1 day')), 'status' => 'Создано', 'location' => 'Минск'],
            ],
        ];
    }
    
    public function cancelShipment(string $shipmentId): bool
    {
        return true;
    }
    
    public function getPickupPoints(string $city): array
    {
        $points = [
            'Минск' => [
                ['id' => 1, 'name' => 'Отделение №1', 'address' => 'пр. Независимости, 10', 'phone' => '+375 17 200-00-01'],
                ['id' => 2, 'name' => 'Отделение №5', 'address' => 'ул. Ленина, 25', 'phone' => '+375 17 200-00-05'],
            ],
            'Гомель' => [
                ['id' => 10, 'name' => 'Отделение №1', 'address' => 'пр. Ленина, 5', 'phone' => '+375 232 70-00-01'],
            ],
        ];
        
        return $points[$city] ?? [];
    }
    
    public function getSupportedCities(): array
    {
        return ['Минск', 'Гомель', 'Брест', 'Витебск', 'Гродно', 'Могилёв'];
    }
    
    public function getDeliveryTime(string $fromCity, string $toCity): int
    {
        if ($fromCity === $toCity) {
            return 2;
        }
        
        return 5;
    }
}
