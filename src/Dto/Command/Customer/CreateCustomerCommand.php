<?php

namespace App\Dto\Command\Customer;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCustomerCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le champ firstname est obligatoire.')]
        #[Assert\Length(max: 100, maxMessage: 'Le champ firstname doit avoir au maximum {{ limit }} caractères.')]
        public ?string $firstname,

        #[Assert\Length(max: 100, maxMessage: 'Le champ lastname doit avoir au maximum {{ limit }} caractères.')]
        public ?string $lastname = null,

        #[Assert\Length(max: 20, maxMessage: 'Le champ phone doit avoir au maximum {{ limit }} caractères.')]
        public ?string $phone = null,

        #[Assert\Length(max: 255, maxMessage: 'Le champ note doit avoir au maximum {{ limit }} caractères.')]
        public ?string $note = null,
    ) {
    }
}
