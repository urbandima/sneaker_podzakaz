<?php

namespace tests\unit;

use app\backend\modules\checkout\services\ShippingService;
use Codeception\Test\Unit;

/**
 * Unit тесты для ShippingService
 */
class ShippingServiceTest extends Unit
{
    /**
     * @var ShippingService
     */
    protected $service;

    protected function _before()
    {
        $this->service = new ShippingService();
    }

    /**
     * Тест расчёта стоимости курьерской доставки
     */
    public function testCalculateCourierCost()
    {
        $result = $this->service->calculateShippingCost('courier_minsk', 'belarus', null, 50);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cost', $result);
        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('estimatedDays', $result);
        $this->assertEquals('BYN', $result['currency']);
        $this->assertGreaterThan(0, $result['cost']);
    }

    /**
     * Тест бесплатной доставки при превышении минимальной суммы
     */
    public function testFreeShippingThreshold()
    {
        // Заказ на 150 BYN - должна быть бесплатная доставка
        $result = $this->service->calculateShippingCost('courier_minsk', 'belarus', null, 150);
        
        $this->assertEquals(0, $result['cost']);
    }

    /**
     * Тест самовывоза (всегда бесплатно)
     */
    public function testPickupIsFree()
    {
        $result = $this->service->calculateShippingCost('pickup_minsk', 'belarus', null, 10);
        
        $this->assertEquals(0, $result['cost']);
    }

    /**
     * Тест получения доступных методов доставки
     */
    public function testGetAvailableMethods()
    {
        $methods = $this->service->getAvailableMethods('belarus', 50);
        
        $this->assertIsArray($methods);
        $this->assertNotEmpty($methods);
        
        foreach ($methods as $method) {
            $this->assertArrayHasKey('key', $method);
            $this->assertArrayHasKey('name', $method);
            $this->assertArrayHasKey('cost', $method);
            $this->assertArrayHasKey('estimatedDays', $method);
        }
    }

    /**
     * Тест валидации адреса
     */
    public function testValidateAddress()
    {
        // Корректный адрес
        $result = $this->service->validateAddress('г. Минск, ул. Ленина, д. 10, кв. 5', 'belarus');
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['errors']);

        // Некорректный адрес (слишком короткий)
        $result = $this->service->validateAddress('Минск', 'belarus');
        $this->assertFalse($result['isValid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Тест проверки доступности метода для страны
     */
    public function testIsMethodAvailable()
    {
        // Курьер доступен для Беларуси
        $this->assertTrue($this->service->isMethodAvailable('courier_minsk', 'belarus'));
        
        // Курьер недоступен для России
        $this->assertFalse($this->service->isMethodAvailable('courier_minsk', 'russia'));
        
        // СДЭК доступен для обеих стран
        $this->assertTrue($this->service->isMethodAvailable('sdek', 'belarus'));
        $this->assertTrue($this->service->isMethodAvailable('sdek', 'russia'));
    }
}
