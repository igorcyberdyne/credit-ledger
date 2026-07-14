<?php

namespace App\Exception\Domain\Customer;

use App\Exception\Domain\BusinessException;

class CustomerArchivedException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Le client est archivé');
    }

    public function getHttpStatus(): ?int
    {
        return 403;
    }
}
