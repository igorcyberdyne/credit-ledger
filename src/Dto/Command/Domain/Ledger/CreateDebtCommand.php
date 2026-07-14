<?php

namespace App\Dto\Command\Domain\Ledger;

use Symfony\Component\Validator\Constraints as Assert;

class CreateDebtCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le champ amountInCents est obligatoire.')]
        #[Assert\Positive(message: 'La valeur du champ amountInCents doit être supérieur à 0.')]
        public ?int $amountInCents,

        #[Assert\Length(max: 255, maxMessage: 'Le champ description doit avoir au maximum {{ limit }} caractères.')]
        public ?string $description = null,

        public ?string $occurredAt = null,
    ) {
    }
}
