<?php

namespace App\Exception\Domain\Ledger;

use App\Exception\Domain\BusinessException;

class LedgerNotFoundException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Entrée introuvable');
    }

    public function getHttpStatus(): ?int
    {
        return 404;
    }
}
