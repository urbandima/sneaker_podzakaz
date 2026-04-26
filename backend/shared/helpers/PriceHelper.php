<?php

namespace app\backend\shared\helpers;

class PriceHelper
{
    /**
     * Format price as "349.00 BYN".
     * Hides zero values as "—" by default.
     */
    public static function format($amount, string $currency = 'BYN', bool $hideZero = false): string
    {
        $amount = (float)$amount;
        if ($hideZero && $amount == 0) {
            return '—';
        }
        return number_format($amount, 2, '.', ' ') . ' ' . $currency;
    }

    /**
     * Format CNY price as "¥349.00".
     */
    public static function cny($amount): string
    {
        $amount = (float)$amount;
        if ($amount == 0) return '—';
        return '¥' . number_format($amount, 2);
    }
}
