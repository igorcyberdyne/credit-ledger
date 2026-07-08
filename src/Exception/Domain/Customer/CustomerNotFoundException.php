<?php

namespace App\Exception\Domain\Customer;

use App\Exception\Domain\BusinessException;

class CustomerNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Customer not found');
    }
}
