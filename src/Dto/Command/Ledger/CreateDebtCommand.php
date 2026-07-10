<?php

namespace App\Dto\Command\Ledger;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDebtCommand
{
    public function __construct(
        public int $amountInCents,

        #[Assert\Length(max: 255)]
        public ?string $description = null,

        #[Assert\DateTime]
        public ?string $occurredAt = null,
    ) {
    }
}
