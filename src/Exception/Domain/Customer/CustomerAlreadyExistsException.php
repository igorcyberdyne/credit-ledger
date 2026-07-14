<?php

namespace App\Exception\Domain\Customer;

use App\Exception\Domain\BusinessException;

class CustomerAlreadyExistsException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Ce numéro de téléphone est déjà utilisé.');
    }

    public function getHttpStatus(): ?int
    {
        return 409;
    }
}
