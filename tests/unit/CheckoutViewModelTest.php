<?php

namespace tests\unit;

use app\backend\modules\checkout\viewmodels\CheckoutViewModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit тесты для CheckoutViewModel (CMP-388, CMP-371 п.3.3).
 *
 * Конструктор класса принимает живой Order AR и требует схему из БД
 * (orderItems relation, CompanySettings::getSettings()) — недоступной
 * в этом тестовом окружении (см. тот же паттерн в AmocrmOrchestratorTest).
 * Здесь покрыта чистая логика статус-флагов (awaiting-payment /
 * passport-needed), вынесенная в статические методы. Полное покрытие,
 * включая сборку CheckoutViewModel из реального Order, — функциональным
 * тестом / QA-прогоном на dev-окружении с БД.
 *
 * @group order-state
 */
class CheckoutViewModelTest extends TestCase
{
    public function testIsDoneStatus(): void
    {
        $this->assertTrue(CheckoutViewModel::isDoneStatus('delivered'));
        $this->assertTrue(CheckoutViewModel::isDoneStatus('canceled'));
        $this->assertTrue(CheckoutViewModel::isDoneStatus('cancelled'));
        $this->assertTrue(CheckoutViewModel::isDoneStatus('returned'));
        $this->assertTrue(CheckoutViewModel::isDoneStatus('refunded'));
        $this->assertFalse(CheckoutViewModel::isDoneStatus('new'));
        $this->assertFalse(CheckoutViewModel::isDoneStatus('paid'));
    }

    public function testAwaitingPaymentWhenNoProofAndNotDone(): void
    {
        $this->assertTrue(CheckoutViewModel::computeIsAwaitingPayment('new', false));
    }

    public function testNotAwaitingPaymentWhenProofPresent(): void
    {
        $this->assertFalse(CheckoutViewModel::computeIsAwaitingPayment('new', true));
    }

    public function testNotAwaitingPaymentWhenStatusIsDoneEvenWithoutProof(): void
    {
        $this->assertFalse(CheckoutViewModel::computeIsAwaitingPayment('delivered', false));
        $this->assertFalse(CheckoutViewModel::computeIsAwaitingPayment('cancelled', false));
    }

    public function testPassportNeededWhenPaidLikeStatusAndIncomplete(): void
    {
        $this->assertTrue(CheckoutViewModel::computeIsPassportNeeded('paid', false, true, false));
        $this->assertTrue(CheckoutViewModel::computeIsPassportNeeded('at_warehouse', false, true, false));
    }

    public function testPassportNeededWhenPaymentProofPresentEvenIfStatusIsNew(): void
    {
        $this->assertTrue(CheckoutViewModel::computeIsPassportNeeded('new', true, true, false));
    }

    public function testPassportNotNeededWhenAlreadyComplete(): void
    {
        $this->assertFalse(CheckoutViewModel::computeIsPassportNeeded('paid', true, true, true));
    }

    public function testPassportNotNeededWhenOrderCannotCheckPassport(): void
    {
        $this->assertFalse(CheckoutViewModel::computeIsPassportNeeded('paid', true, false, false));
    }

    public function testPassportNotNeededWhenStatusIsDone(): void
    {
        $this->assertFalse(CheckoutViewModel::computeIsPassportNeeded('delivered', true, true, false));
    }

    public function testPassportNotNeededWhenStatusIsNewAndUnpaid(): void
    {
        // Ещё не оплачен и не в статусах "оплачен/в пути" — паспорт рано спрашивать.
        $this->assertFalse(CheckoutViewModel::computeIsPassportNeeded('new', false, true, false));
    }
}
