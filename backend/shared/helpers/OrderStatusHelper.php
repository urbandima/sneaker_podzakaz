<?php

namespace app\backend\shared\helpers;

class OrderStatusHelper
{
    private static array $labels = [
        'new'              => 'Новый',
        'created'          => 'Создан',
        'pending'          => 'Ожидает',
        'paid'             => 'Оплачен',
        'processing'       => 'В обработке',
        'shipped'          => 'Отправлен',
        'in_transit'       => 'В пути',
        'delivered'        => 'Доставлен',
        'completed'        => 'Завершён',
        'cancelled'        => 'Отменён',
        'refunded'         => 'Возврат',
        'on_hold'          => 'На удержании',
        'awaiting_payment' => 'Ожидает оплаты',
        'imported_invalid' => 'Импорт (ошибка)',
    ];

    public static function label(string $status): string
    {
        return self::$labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public static function all(): array
    {
        return self::$labels;
    }
}
