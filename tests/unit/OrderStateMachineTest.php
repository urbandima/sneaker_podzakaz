<?php

namespace tests\unit;

use app\backend\modules\checkout\services\OrderStateMachine;
use PHPUnit\Framework\TestCase;

/**
 * Unit тесты для OrderStateMachine
 *
 * @group order-state
 */
class OrderStateMachineTest extends TestCase
{
    public function testSameStatusIsAlwaysValid()
    {
        foreach (OrderStateMachine::getKnownStatuses() as $status) {
            $this->assertTrue(OrderStateMachine::isValidTransition($status, $status));
        }
    }

    public function testForwardProgressionIsValid()
    {
        $this->assertTrue(OrderStateMachine::isValidTransition('new', 'paid'));
        $this->assertTrue(OrderStateMachine::isValidTransition('paid', 'confirmed_and_paid'));
        $this->assertTrue(OrderStateMachine::isValidTransition('confirmed_and_paid', 'ordered'));
        $this->assertTrue(OrderStateMachine::isValidTransition('ordered', 'awaiting_warehouse'));
        $this->assertTrue(OrderStateMachine::isValidTransition('awaiting_warehouse', 'international_delivery'));
        $this->assertTrue(OrderStateMachine::isValidTransition('international_delivery', 'at_warehouse'));
        $this->assertTrue(OrderStateMachine::isValidTransition('at_warehouse', 'local_delivery'));
        $this->assertTrue(OrderStateMachine::isValidTransition('local_delivery', 'delivered'));
    }

    public function testSkippingIntermediateStatusesIsAllowed()
    {
        // Локальные заказы могут не проходить international_delivery.
        $this->assertTrue(OrderStateMachine::isValidTransition('new', 'at_warehouse'));
    }

    public function testCancelIsAllowedFromAnyNonTerminalStatus()
    {
        foreach (OrderStateMachine::getKnownStatuses() as $status) {
            if (OrderStateMachine::isTerminal($status)) {
                continue;
            }
            $this->assertTrue(OrderStateMachine::isValidTransition($status, 'canceled'));
        }
    }

    public function testTerminalStatusesRejectFurtherTransitions()
    {
        $this->assertFalse(OrderStateMachine::isValidTransition('delivered', 'new'));
        $this->assertFalse(OrderStateMachine::isValidTransition('canceled', 'new'));
        $this->assertFalse(OrderStateMachine::isValidTransition('cancelled', 'new'));
        $this->assertFalse(OrderStateMachine::isValidTransition('refunded', 'paid'));
    }

    public function testUnknownTargetStatusIsRejected()
    {
        $this->assertFalse(OrderStateMachine::isValidTransition('new', 'not_a_real_status'));
    }

    public function testLegacyCurrentStatusIsNotLockedOut()
    {
        // Импортированные/унаследованные заказы со старым статусом не должны
        // намертво зависать только потому, что их статус не входит в справочник.
        $this->assertTrue(OrderStateMachine::isValidTransition('imported', 'new'));
        $this->assertTrue(OrderStateMachine::isValidTransition('processing', 'confirmed_and_paid'));
    }

    public function testRequiresBuyoutOnlyForConfirmedToOrdered()
    {
        $this->assertTrue(OrderStateMachine::requiresBuyout('confirmed_and_paid', 'ordered'));
        $this->assertFalse(OrderStateMachine::requiresBuyout('confirmed_and_paid', 'awaiting_warehouse'));
        $this->assertFalse(OrderStateMachine::requiresBuyout('new', 'ordered'));
    }

    public function testIsTerminal()
    {
        $this->assertTrue(OrderStateMachine::isTerminal('delivered'));
        $this->assertTrue(OrderStateMachine::isTerminal('canceled'));
        $this->assertTrue(OrderStateMachine::isTerminal('cancelled'));
        $this->assertTrue(OrderStateMachine::isTerminal('refunded'));
        $this->assertFalse(OrderStateMachine::isTerminal('new'));
        $this->assertFalse(OrderStateMachine::isTerminal('ordered'));
    }
}
