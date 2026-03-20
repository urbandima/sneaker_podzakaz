<?php

namespace tests\unit;

use app\backend\modules\coupon\models\Coupon;
use Codeception\Test\Unit;

/**
 * Unit тесты для модели Coupon
 */
class CouponTest extends Unit
{
    /**
     * Тест создания купона
     */
    public function testCreateCoupon()
    {
        $coupon = new Coupon();
        $coupon->code = 'TEST2024';
        $coupon->name = 'Тестовый купон';
        $coupon->type = Coupon::TYPE_PERCENTAGE;
        $coupon->value = 10;
        $coupon->is_active = 1;

        $this->assertTrue($coupon->validate());
    }

    /**
     * Тест валидации купона - код обязателен
     */
    public function testCodeRequired()
    {
        $coupon = new Coupon();
        $coupon->name = 'Тестовый купон';
        $coupon->type = Coupon::TYPE_PERCENTAGE;
        $coupon->value = 10;

        $this->assertFalse($coupon->validate());
        $this->assertArrayHasKey('code', $coupon->errors);
    }

    /**
     * Тест расчёта процентной скидки
     */
    public function testCalculatePercentageDiscount()
    {
        $coupon = new Coupon();
        $coupon->type = Coupon::TYPE_PERCENTAGE;
        $coupon->value = 10; // 10%

        $discount = $coupon->calculateDiscount(100);
        $this->assertEquals(10, $discount);
    }

    /**
     * Тест расчёта фиксированной скидки
     */
    public function testCalculateFixedDiscount()
    {
        $coupon = new Coupon();
        $coupon->type = Coupon::TYPE_FIXED;
        $coupon->value = 15; // 15 BYN

        $discount = $coupon->calculateDiscount(100);
        $this->assertEquals(15, $discount);
    }

    /**
     * Тест максимальной скидки для процентного купона
     */
    public function testMaxDiscount()
    {
        $coupon = new Coupon();
        $coupon->type = Coupon::TYPE_PERCENTAGE;
        $coupon->value = 50; // 50%
        $coupon->max_discount = 20; // Максимум 20 BYN

        $discount = $coupon->calculateDiscount(100);
        $this->assertEquals(20, $discount); // Должно быть 20, а не 50
    }

    /**
     * Тест валидации купона по минимальной сумме заказа
     */
    public function testMinOrderAmount()
    {
        $coupon = new Coupon();
        $coupon->code = 'MIN100';
        $coupon->type = Coupon::TYPE_PERCENTAGE;
        $coupon->value = 10;
        $coupon->min_order_amount = 100;
        $coupon->is_active = 1;

        list($valid, $error) = $coupon->validate(50);
        $this->assertFalse($valid);
        $this->assertStringContainsString('минимальная сумма', $error);

        list($valid, $error) = $coupon->validate(100);
        $this->assertTrue($valid);
    }

    /**
     * Тест применения купона (увеличение счётчика)
     */
    public function testApplyCoupon()
    {
        $coupon = new Coupon();
        $coupon->current_uses = 5;
        $coupon->max_uses = 10;

        $initialUses = $coupon->current_uses;
        $coupon->apply();

        $this->assertEquals($initialUses + 1, $coupon->current_uses);
    }
}
