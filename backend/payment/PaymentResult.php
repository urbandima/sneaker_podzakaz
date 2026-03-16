<?php

namespace app\backend\payment;

/**
 * Результат платежа
 */
class PaymentResult
{
    private bool $success;
    private string $message;
    private array $data;

    public function __construct(bool $success, string $message, array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getError(): ?string
    {
        return $this->success ? null : $this->message;
    }
}
