<?php

namespace app\backend\payment;

/**
 * Статус платежа
 */
class PaymentStatus
{
    public string $id;
    public string $status;
    public float $amount;

    public function __construct(string $id, string $status, float $amount)
    {
        $this->id = $id;
        $this->status = $status;
        $this->amount = $amount;
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['succeeded', 'paid']);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'waiting_for_capture']);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'canceled']);
    }
}
