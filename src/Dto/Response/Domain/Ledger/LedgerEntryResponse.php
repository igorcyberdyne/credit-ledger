<?php

declare(strict_types=1);

namespace App\Dto\Response\Domain\Ledger;

use App\Enum\PaymentMethodEnum;

final readonly class LedgerEntryResponse
{
    public function __construct(
        public string $uuid,
        public string $type,
        public string $amount,
        public ?string $description,
        public ?string $occurredAt,
        public ?PaymentMethodEnum $paymentMethod = null,
    ) {
    }
}
