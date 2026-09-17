<?php

namespace app\backend\modules\checkout\viewmodels;

/**
 * Read-only line-item projection for checkout views (CMP-388).
 */
final class CheckoutOrderItemView
{
    public function __construct(
        public readonly string $productName,
        public readonly ?string $size,
        public readonly int $quantity,
        public readonly float $price
    ) {
    }
}
