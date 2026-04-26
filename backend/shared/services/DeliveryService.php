<?php

namespace app\backend\shared\services;

use Yii;

/**
 * Calculates delivery cost and free-shipping threshold for a given cart total.
 *
 * Reads method configuration from infrastructure/config/shipping.php via Yii params.
 * Falls back to sensible defaults when config is absent.
 */
class DeliveryService
{
    /** Default method used when no method is specified */
    public const DEFAULT_METHOD = 'courier_minsk';

    /**
     * Calculate delivery cost for a cart total.
     *
     * Returns an array:
     *   cost         float  — delivery cost (0 = free)
     *   is_free      bool   — whether delivery is free
     *   remaining    float  — amount remaining until free delivery (0 if already free)
     *   threshold    float  — the free-delivery threshold for the chosen method
     *   method       string — shipping method key
     */
    public static function calculate(float $cartTotal, string $method = self::DEFAULT_METHOD): array
    {
        $config = self::getMethodConfig($method);
        $threshold = (float)($config['freeFrom'] ?? 0);
        $baseCost  = (float)($config['baseCost'] ?? 0);

        $isFree    = $threshold > 0 && $cartTotal >= $threshold;
        $cost      = $isFree ? 0.0 : $baseCost;
        $remaining = ($threshold > 0 && !$isFree) ? max(0.0, $threshold - $cartTotal) : 0.0;

        return [
            'cost'      => $cost,
            'is_free'   => $isFree,
            'remaining' => $remaining,
            'threshold' => $threshold,
            'method'    => $method,
        ];
    }

    /**
     * Return the free-delivery threshold for a method (0 = no free threshold).
     */
    public static function getFreeThreshold(string $method = self::DEFAULT_METHOD): float
    {
        $config = self::getMethodConfig($method);
        return (float)($config['freeFrom'] ?? 0);
    }

    private static function getMethodConfig(string $method): array
    {
        // params['shipping'] is the shipping.php array which itself has key 'shipping'
        $shippingFile = Yii::$app->params['shipping'] ?? [];
        $methods = $shippingFile['shipping']['methods'] ?? [];
        return $methods[$method] ?? ['baseCost' => 10, 'freeFrom' => 100];
    }
}
