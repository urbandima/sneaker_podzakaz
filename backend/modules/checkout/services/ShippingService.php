<?php

/**
 * ShippingService — Сервис расчёта доставки
 * 
 * НАЗНАЧЕНИЕ:
 * Расчёт стоимости доставки, получение доступных методов доставки,
 * валидация адреса, интеграция с транспортными компаниями.
 * 
 * ФУНКЦИИ:
 * - calculateShippingCost() - расчёт стоимости доставки
 * - getAvailableMethods() - получение доступных способов доставки
 * - validateAddress() - валидация адреса доставки
 * - getDeliveryTime() - расчёт времени доставки
 */
namespace app\backend\modules\checkout\services;

use Yii;
use yii\base\Component;

class ShippingService extends Component
{
    /** @var array Конфигурация методов доставки */
    private $shippingMethods;

    /**
     * Инициализация сервиса
     */
    public function init()
    {
        parent::init();
        
        // Загружаем конфигурацию из params
        $this->shippingMethods = Yii::$app->params['shipping']['methods'] ?? $this->getDefaultMethods();
    }

    /**
     * Рассчитать стоимость доставки
     * 
     * @param string $method Метод доставки
     * @param string $country Страна доставки
     * @param string|null $city Город
     * @param float $orderAmount Сумма заказа
     * @param float $weight Вес заказа (кг)
     * @return array [cost, currency, estimatedDays]
     */
    public function calculateShippingCost(
        string $method,
        string $country = 'belarus',
        ?string $city = null,
        float $orderAmount = 0,
        float $weight = 1.0
    ): array {
        $methodConfig = $this->shippingMethods[$method] ?? null;
        
        if (!$methodConfig) {
            return [
                'cost' => 0,
                'currency' => 'BYN',
                'estimatedDays' => 0,
                'error' => 'Метод доставки не найден'
            ];
        }

        // Проверяем доступность метода для страны
        if (!in_array($country, $methodConfig['countries'])) {
            return [
                'cost' => 0,
                'currency' => 'BYN',
                'estimatedDays' => 0,
                'error' => 'Метод недоступен для данной страны'
            ];
        }

        $cost = $methodConfig['baseCost'];

        // Бесплатная доставка при превышении минимальной суммы
        if (isset($methodConfig['freeFrom']) && $orderAmount >= $methodConfig['freeFrom']) {
            $cost = 0;
        }

        // Расчёт по весу (если указано)
        if (isset($methodConfig['costPerKg']) && $weight > 1) {
            $cost += ($weight - 1) * $methodConfig['costPerKg'];
        }

        // Расчёт по зонам (для курьерской доставки)
        if ($method === 'courier_minsk' && $city) {
            $cost = $this->calculateCourierCost($city, $orderAmount);
        }

        return [
            'cost' => round($cost, 2),
            'currency' => 'BYN',
            'estimatedDays' => $methodConfig['estimatedDays'] ?? 3,
            'method' => $methodConfig['name'],
        ];
    }

    /**
     * Расчёт стоимости курьерской доставки по Минску
     * 
     * @param string $city Город
     * @param float $orderAmount Сумма заказа
     * @return float
     */
    protected function calculateCourierCost(string $city, float $orderAmount): float
    {
        $zones = Yii::$app->params['shipping']['courierZones'] ?? [
            'center' => ['cost' => 5, 'freeFrom' => 100],
            'suburbs' => ['cost' => 10, 'freeFrom' => 150],
            'outside' => ['cost' => 15, 'freeFrom' => 200],
        ];

        // Определяем зону по городу (упрощённая логика)
        $zone = 'center';
        if (stripos($city, 'пригород') !== false || stripos($city, 'район') !== false) {
            $zone = 'suburbs';
        }

        $zoneConfig = $zones[$zone];
        
        if ($orderAmount >= $zoneConfig['freeFrom']) {
            return 0;
        }

        return $zoneConfig['cost'];
    }

    /**
     * Получить доступные методы доставки
     * 
     * @param string $country Страна доставки
     * @param float $orderAmount Сумма заказа
     * @return array
     */
    public function getAvailableMethods(string $country = 'belarus', float $orderAmount = 0): array
    {
        $available = [];

        foreach ($this->shippingMethods as $key => $method) {
            if (in_array($country, $method['countries']) && $method['enabled']) {
                $cost = $this->calculateShippingCost($key, $country, null, $orderAmount);
                
                $available[] = [
                    'key' => $key,
                    'name' => $method['name'],
                    'description' => $method['description'] ?? '',
                    'cost' => $cost['cost'],
                    'estimatedDays' => $cost['estimatedDays'],
                    'isFree' => $cost['cost'] == 0,
                ];
            }
        }

        return $available;
    }

    /**
     * Валидация адреса доставки
     * 
     * @param string $address Адрес
     * @param string $country Страна
     * @return array [isValid, errors]
     */
    public function validateAddress(string $address, string $country = 'belarus'): array
    {
        $errors = [];

        if (empty($address)) {
            $errors[] = 'Адрес не может быть пустым';
        }

        if (mb_strlen($address) < 10) {
            $errors[] = 'Адрес слишком короткий (минимум 10 символов)';
        }

        // Проверка наличия номера дома
        if (!preg_match('/\d+/', $address)) {
            $errors[] = 'Укажите номер дома';
        }

        // Для Беларуси проверяем наличие города
        if ($country === 'belarus') {
            $cities = ['минск', 'гомель', 'брест', 'витебск', 'гродно', 'могилёв'];
            $hasCity = false;
            foreach ($cities as $city) {
                if (stripos($address, $city) !== false) {
                    $hasCity = true;
                    break;
                }
            }
            if (!$hasCity) {
                $errors[] = 'Укажите город доставки';
            }
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Получить время доставки
     * 
     * @param string $method Метод доставки
     * @param string $country Страна
     * @return array [min, max, unit]
     */
    public function getDeliveryTime(string $method, string $country = 'belarus'): array
    {
        $methodConfig = $this->shippingMethods[$method] ?? null;
        
        if (!$methodConfig) {
            return ['min' => 0, 'max' => 0, 'unit' => 'days'];
        }

        $days = $methodConfig['estimatedDays'] ?? 3;

        return [
            'min' => max(1, $days - 1),
            'max' => $days + 1,
            'unit' => 'days',
            'description' => "Доставка за {$days} дней",
        ];
    }

    /**
     * Получить конфигурацию методов доставки по умолчанию
     * 
     * @return array
     */
    protected function getDefaultMethods(): array
    {
        return [
            'courier_minsk' => [
                'name' => 'Курьер по Минску',
                'description' => 'Доставка курьером в пределах Минска',
                'baseCost' => 10,
                'freeFrom' => 100,
                'estimatedDays' => 1,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'pickup_minsk' => [
                'name' => 'Самовывоз (Минск)',
                'description' => 'Самовывоз из пункта выдачи в Минске',
                'baseCost' => 0,
                'estimatedDays' => 1,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'europochta' => [
                'name' => 'Европочта',
                'description' => 'Доставка службой Европочта',
                'baseCost' => 5,
                'freeFrom' => 80,
                'estimatedDays' => 3,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'belpochta' => [
                'name' => 'Белпочта',
                'description' => 'Доставка почтой Беларуси',
                'baseCost' => 4,
                'freeFrom' => 70,
                'estimatedDays' => 5,
                'countries' => ['belarus'],
                'enabled' => true,
            ],
            'sdek' => [
                'name' => 'СДЭК',
                'description' => 'Доставка транспортной компанией СДЭК',
                'baseCost' => 8,
                'costPerKg' => 2,
                'freeFrom' => 120,
                'estimatedDays' => 3,
                'countries' => ['belarus', 'russia'],
                'enabled' => true,
            ],
        ];
    }

    /**
     * Получить информацию о методе доставки
     * 
     * @param string $method Ключ метода
     * @return array|null
     */
    public function getMethodInfo(string $method): ?array
    {
        return $this->shippingMethods[$method] ?? null;
    }

    /**
     * Проверить доступность метода для страны
     * 
     * @param string $method Метод доставки
     * @param string $country Страна
     * @return bool
     */
    public function isMethodAvailable(string $method, string $country): bool
    {
        $methodConfig = $this->shippingMethods[$method] ?? null;
        
        if (!$methodConfig || !$methodConfig['enabled']) {
            return false;
        }

        return in_array($country, $methodConfig['countries']);
    }
}
