<?php

namespace App\Dto\Command\Security;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserUpdateCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le champ firstname est obligatoire.')]
        #[Assert\Length(max: 100, maxMessage: 'Le champ firstname doit avoir au maximum {{ limit }} caractères.')]
        public string $firstname,
        #[Assert\Length(max: 100, maxMessage: 'Le champ firstname doit avoir au maximum {{ limit }} caractères.')]
        public ?string $lastname,
        #[Assert\Length(max: 30, maxMessage: 'Le phone doit avoir au maximum {{ limit }} caractères.')]
        public ?string $phone,
    ) {
    }
}
