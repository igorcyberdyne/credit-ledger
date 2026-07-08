<?php

namespace App\Dto\Command\Customer;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCustomerCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $firstname,

        #[Assert\Length(max: 100)]
        public ?string $lastname,

        #[Assert\Length(max: 30)]
        public ?string $phone,

        #[Assert\Length(max: 255)]
        public ?string $note,
    ) {
    }
}
