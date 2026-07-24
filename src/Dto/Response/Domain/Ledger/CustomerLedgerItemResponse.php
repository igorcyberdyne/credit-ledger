<?php

namespace App\Dto\Response\Domain\Ledger;

use App\Enum\PaymentMethodEnum;

final readonly class CustomerLedgerItemResponse
{
    public function __construct(
        public string $uuid,
        public string $type,
        public string $amount,
        public string $description,
        public string $occurredAt,
        public ?PaymentMethodEnum $paymentMethod,
        public string $status,
        public bool $isCorrection,
        public ?string $correctedEntryUuid,
        public ?string $previousAmount,
        public bool $canReverse,
        public bool $canCorrect,
        public string $icon,
        public string $color,
        public ?string $badge,
    ) {
    }
}
