<?php

namespace App\Dto\Command\Domain\Ledger;

use Symfony\Component\Validator\Constraints as Assert;

class ReverseLedgerEntryCommand
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'Le champ reason doit avoir au maximum {{ limit }} caractères.')]
        public ?string $reason = null,
    ) {
    }
}
