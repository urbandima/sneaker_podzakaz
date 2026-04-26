<?php

namespace app\backend\shared\helpers;

/**
 * Unified price formatting helper (X12).
 * Single source of truth for "259.00 BYN" display format across the site.
 */
class PriceHelper
{
    public const CURRENCY = 'BYN';

    /**
     * Format a price value as "259.00 BYN".
     */
    public static function format(?float $price, int $decimals = 2): string
    {
        if ($price === null || $price < 0) {
            return 'Цена уточняется';
        }
        return number_format($price, $decimals, '.', ' ') . ' ' . self::CURRENCY;
    }

    /**
     * Format as integer when .00 is not needed, e.g. "259 BYN".
     */
    public static function formatInt(?float $price): string
    {
        if ($price === null || $price < 0) {
            return 'Цена уточняется';
        }
        return number_format($price, 0, '.', ' ') . ' ' . self::CURRENCY;
    }
}
