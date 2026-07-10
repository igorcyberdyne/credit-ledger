<?php

namespace App\Exception\Domain\Customer;

use App\Exception\Domain\BusinessException;

class CustomerMissingPhoneException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Le phone du client est obligatoire');
    }

    public function getHttpStatus(): ?int
    {
        return 404;
    }
}
