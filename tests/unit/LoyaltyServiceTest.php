<?php

namespace tests\unit;

use app\backend\modules\loyalty\services\LoyaltyService;
use PHPUnit\Framework\TestCase;

/**
 * Unit тесты для LoyaltyService
 */
class LoyaltyServiceTest extends TestCase
{
    /**
     * @var LoyaltyService
     */
    protected $service;

    protected function setUp(): void
    {
        $this->service = new LoyaltyService();
    }

    /**
     * Тест расчёта начисляемых баллов
     */
    public function testCalculateEarnPoints()
    {
        // 100 BYN * 10 баллов = 1000 баллов
        $points = $this->service->calculateEarnPoints(1, 100);
        $this->assertEquals(1000, $points);
    }

    /**
     * Тест расчёта скидки при списании баллов
     */
    public function testCalculateRedeemDiscount()
    {
        // 1000 баллов * 0.01 = 10 BYN
        $discount = $this->service->calculateRedeemDiscount(1000);
        $this->assertEquals(10, $discount);
    }

    /**
     * Тест минимального количества баллов для списания
     */
    public function testMinPointsToRedeem()
    {
        $this->assertGreaterThan(0, $this->service->minPointsToRedeem);
        $this->assertEquals(100, $this->service->minPointsToRedeem);
    }

    /**
     * Тест максимального процента списания
     */
    public function testMaxRedeemPercent()
    {
        $this->assertGreaterThan(0, $this->service->maxRedeemPercent);
        $this->assertLessThanOrEqual(100, $this->service->maxRedeemPercent);
        $this->assertEquals(50, $this->service->maxRedeemPercent);
    }

    /**
     * Тест стоимости балла
     */
    public function testPointValue()
    {
        $this->assertGreaterThan(0, $this->service->pointValue);
        $this->assertEquals(0.01, $this->service->pointValue);
    }

    /**
     * Тест расчёта максимального количества баллов для списания
     */
    public function testGetMaxRedeemPoints()
    {
        // Для заказа на 100 BYN можно списать максимум 50% = 50 BYN = 5000 баллов
        $maxPoints = $this->service->getMaxRedeemPoints(1, 100);
        $this->assertEquals(5000, $maxPoints);
    }
}
