<?php

namespace App\Dto\Command\Ledger;

use App\Enum\LedgerTypeEnum;
use App\Enum\PaymentMethodEnum;

final class CorrectLedgerEntryCommand
{
    public function __construct(
        public LedgerTypeEnum $type,
        public int $amountInCents,
        public ?string $description,
        public ?PaymentMethodEnum $paymentMethod,
    ) {
    }
}
