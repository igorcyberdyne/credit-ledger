<?php

namespace App\ValueObject;

use App\Enum\CurrencyEnum;

final readonly class Money
{
    public function __construct(
        private int $amountInCents,
        private CurrencyEnum $currencyEnum = CurrencyEnum::EURO,
    ) {
    }

    public static function fromCents(int $amountInCents): self
    {
        return new self($amountInCents);
    }

    public static function fromDecimal(string $amountInCents): self
    {
        return new self(
            (int) round(((float) $amountInCents) * 100)
        );
    }

    public function cents(): int
    {
        return $this->amountInCents;
    }

    public function decimal(): string
    {
        return number_format(
            $this->amountInCents / 100,
            2,
            '.',
            ''
        );
    }

    public function format(): string
    {
        return sprintf('%s %s', number_format(
            $this->amountInCents / 100,
            2,
            ',',
            ' '
        ), $this->currencyEnum->symbol());
    }

    public function add(self $money): self
    {
        return new self(
            $this->amountInCents + $money->amountInCents
        );
    }

    public function subtract(self $money): self
    {
        return new self(
            $this->amountInCents - $money->amountInCents
        );
    }

    public function isPositive(): bool
    {
        return $this->amountInCents > 0;
    }

    public function isZero(): bool
    {
        return 0 === $this->amountInCents;
    }
}
