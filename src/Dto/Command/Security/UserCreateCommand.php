<?php

namespace App\Dto\Command\Security;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserCreateCommand
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le champ firstname est obligatoire.')]
        #[Assert\Length(max: 100, maxMessage: 'Le champ firstname doit avoir au maximum {{ limit }} caractères.')]
        public string $firstname,
        #[Assert\Length(max: 100, maxMessage: 'Le champ firstname doit avoir au maximum {{ limit }} caractères.')]
        public ?string $lastname,
        #[Assert\Length(max: 180, maxMessage: 'Le lastname doit avoir au maximum {{ limit }} caractères.')]
        public string $email,
        #[Assert\Length(max: 30, maxMessage: 'Le phone doit avoir au maximum {{ limit }} caractères.')]
        public ?string $phone,
        #[Assert\Length(max: 50, maxMessage: 'Le password doit avoir au maximum {{ limit }} caractères.')]
        public string $password,
    ) {
    }
}
