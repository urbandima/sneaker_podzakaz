<?php

namespace app\backend\modules\common\value_objects;

/**
 * Money — Value Object для работы с деньгами
 * 
 * Рекомендация #8: Нормализовать поля денег
 * 
 * Хранит сумму в копейках (целое число) для избежания ошибок округления
 */
class Money
{
    private int $amount; // в копейках
    private string $currency;
    
    public function __construct(float $amount, string $currency = 'BYN')
    {
        $this->amount = (int) round($amount * 100);
        $this->currency = $currency;
    }
    
    /**
     * Создать из копеек
     */
    public static function fromCents(int $cents, string $currency = 'BYN'): self
    {
        $money = new self(0, $currency);
        $money->amount = $cents;
        return $money;
    }
    
    /**
     * Получить сумму в основной валюте
     */
    public function getAmount(): float
    {
        return $this->amount / 100;
    }
    
    /**
     * Получить в копейках
     */
    public function getCents(): int
    {
        return $this->amount;
    }
    
    /**
     * Получить валюту
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
    
    /**
     * Добавить сумму
     */
    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies');
        }
        
        return self::fromCents($this->amount + $other->amount, $this->currency);
    }
    
    /**
     * Вычесть сумму
     */
    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract different currencies');
        }
        
        return self::fromCents($this->amount - $other->amount, $this->currency);
    }
    
    /**
     * Умножить на процент
     */
    public function multiply(float $percent): self
    {
        return self::fromCents(
            (int) round($this->amount * $percent / 100),
            $this->currency
        );
    }
    
    /**
     * Форматировать для отображения
     */
    public function format(): string
    {
        return number_format($this->getAmount(), 2, '.', ' ') . ' ' . $this->currency;
    }
    
    /**
     * Сравнить
     */
    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
    
    /**
     * Больше чем
     */
    public function greaterThan(Money $other): bool
    {
        return $this->amount > $other->amount;
    }
    
    /**
     * Меньше чем
     */
    public function lessThan(Money $other): bool
    {
        return $this->amount < $other->amount;
    }
    
    /**
     * Проверить на ноль
     */
    public function isZero(): bool
    {
        return $this->amount === 0;
    }
    
    /**
     * Проверить на положительное значение
     */
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }
    
    public function __toString(): string
    {
        return $this->format();
    }
}
