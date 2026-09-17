<?php

/**
 * OrderStateMachine — таблица допустимых переходов статуса заказа
 *
 * НАЗНАЧЕНИЕ:
 * Единый источник истины для допустимых значений order.status и переходов
 * между ними. До появления этого класса проверка была разбросана по
 * OrderController (ad-hoc buyout-guard) и почти отсутствовала в bulk-экшенах.
 *
 * МЕТОДЫ:
 * - isValidTransition(): можно ли перевести заказ из статуса $from в $to
 * - requiresBuyout(): нужен ли заполненный блок «Выкуп» для перехода
 * - isTerminal(): финальный ли это статус (дальше меняться не должен)
 * - getKnownStatuses(): статусы, входящие в текущий workflow (см. миграцию
 *   infrastructure/migrations/m260412_150000_update_order_statuses.php)
 *
 * ИСПОЛЬЗОВАНИЕ:
 * if (!OrderStateMachine::isValidTransition($order->status, $newStatus)) { ... }
 */

namespace app\backend\modules\checkout\services;

use app\backend\modules\checkout\models\Order;

class OrderStateMachine
{
    public const STATUS_NEW = 'new';
    public const STATUS_PAID = 'paid';
    public const STATUS_CONFIRMED_AND_PAID = 'confirmed_and_paid';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_AWAITING_WAREHOUSE = 'awaiting_warehouse';
    public const STATUS_INTERNATIONAL_DELIVERY = 'international_delivery';
    public const STATUS_AT_WAREHOUSE = 'at_warehouse';
    public const STATUS_LOCAL_DELIVERY = 'local_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELED = 'canceled';

    /**
     * Текущий рабочий процесс заказа, в порядке продвижения (sort в order_status).
     * Не все заказы проходят каждый шаг (например, локальные заказы могут
     * пропускать international_delivery), поэтому это не строгая линейная
     * последовательность, а перечень известных состояний.
     */
    private const KNOWN_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PAID,
        self::STATUS_CONFIRMED_AND_PAID,
        self::STATUS_ORDERED,
        self::STATUS_AWAITING_WAREHOUSE,
        self::STATUS_INTERNATIONAL_DELIVERY,
        self::STATUS_AT_WAREHOUSE,
        self::STATUS_LOCAL_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELED,
    ];

    /**
     * Финальные статусы — переход из них дальше запрещён этим классом.
     * 'cancelled'/'refunded' — унаследованные значения, которые Order.php
     * уже трактует как терминальные (см. isOverdue()/getSlaStatus()).
     */
    private const TERMINAL_STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_CANCELED,
        'cancelled',
        'refunded',
    ];

    /**
     * @return string[]
     */
    public static function getKnownStatuses(): array
    {
        return self::KNOWN_STATUSES;
    }

    public static function isTerminal(string $status): bool
    {
        return in_array($status, self::TERMINAL_STATUSES, true);
    }

    /**
     * Проверка допустимости перехода по графу статусов.
     *
     * Правила:
     * - переход в тот же статус всегда допустим (no-op сохранение формы);
     * - из финального статуса (delivered/canceled/...) дальше перейти нельзя;
     * - в неизвестный этому классу статус (кроме canceled) перейти нельзя —
     *   это отсекает опечатки и мусорные значения из bulk-экшенов;
     * - если $from не входит в KNOWN_STATUSES (унаследованные/импортированные
     *   данные), запрет по терминальности не применяется — старые заказы не
     *   должны намертво зависать из-за статуса, которого сейчас нет в справочнике.
     */
    public static function isValidTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        if (self::isTerminal($from)) {
            return false;
        }

        if ($to === self::STATUS_CANCELED) {
            return true;
        }

        return in_array($to, self::KNOWN_STATUSES, true);
    }

    /**
     * Требуется ли заполненный блок «Выкуп» перед этим переходом.
     * Правило вынесено из OrderController::actionChangeStatus().
     */
    public static function requiresBuyout(string $from, string $to): bool
    {
        return $from === self::STATUS_CONFIRMED_AND_PAID && $to === self::STATUS_ORDERED;
    }

    /**
     * Удобная обёртка над isValidTransition() + requiresBuyout() для модели заказа.
     * Возвращает текст ошибки на русском или null, если переход допустим.
     */
    public static function getTransitionError(Order $order, string $to): ?string
    {
        if (self::requiresBuyout($order->status, $to) && !$order->isBuyoutFilled()) {
            return 'Необходимо заполнить блок «Выкуп» перед переводом в статус «Заказано»';
        }

        if (!self::isValidTransition($order->status, $to)) {
            return 'Недопустимый переход статуса заказа: "' . $order->status . '" → "' . $to . '"';
        }

        return null;
    }
}
