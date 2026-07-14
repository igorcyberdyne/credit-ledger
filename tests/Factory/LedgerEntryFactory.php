<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\Customer;
use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Enum\LedgerTypeEnum;
use App\Enum\PaymentMethodEnum;

final class LedgerEntryFactory extends BaseFactory
{
    public static function class(): string
    {
        return LedgerEntry::class;
    }

    protected function defaults(): array
    {
        return [
            'customer' => CustomerFactory::new(),

            'type' => self::faker()->randomElement([
                LedgerTypeEnum::DEBT,
                LedgerTypeEnum::PAYMENT,
            ]),

            'amountInCents' => self::faker()->numberBetween(
                1000,
                500000,
            ),

            'description' => self::faker()->sentence(),

            'paymentMethod' => self::faker()->randomElement(
                PaymentMethodEnum::cases(),
            ),

            'occurredAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-1 year')
            ),
        ];
    }

    public function debt(): self
    {
        return $this->with([
            'type' => LedgerTypeEnum::DEBT,
            'paymentMethod' => null,
        ]);
    }

    public function payment(
        PaymentMethodEnum $method = PaymentMethodEnum::CASH,
    ): self {
        return $this->with([
            'type' => LedgerTypeEnum::PAYMENT,
            'paymentMethod' => $method,
        ]);
    }

    public function newLedgerEntry(
        Shop $shop,
        Customer $customer,
        LedgerTypeEnum $ledgerTypeEnum,
        \DateTimeImmutable $occurredAt,
    ): self {
        return $this->with([
            'shop' => $shop,
            'customer' => $customer,
            'type' => $ledgerTypeEnum,
            'occurredAt' => $occurredAt,
        ]);
    }
}
