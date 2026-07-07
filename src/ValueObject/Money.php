<?php

namespace App\ValueObject;

final readonly class Money
{
    public function __construct(
        private int $amount,
    ) {
    }

    public static function fromCents(int $amount): self
    {
        return new self($amount);
    }

    public static function fromDecimal(string $amount): self
    {
        return new self(
            (int) round(((float) $amount) * 100)
        );
    }

    public function cents(): int
    {
        return $this->amount;
    }

    public function decimal(): string
    {
        return number_format(
            $this->amount / 100,
            2,
            '.',
            ''
        );
    }

    public function format(): string
    {
        return number_format(
            $this->amount / 100,
            2,
            ',',
            ' '
        ).' €';
    }

    public function add(self $money): self
    {
        return new self(
            $this->amount + $money->amount
        );
    }

    public function subtract(self $money): self
    {
        return new self(
            $this->amount - $money->amount
        );
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isZero(): bool
    {
        return 0 === $this->amount;
    }
}
