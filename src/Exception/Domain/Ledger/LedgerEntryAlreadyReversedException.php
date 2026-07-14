<?php

namespace App\Exception\Domain\Ledger;

use App\Exception\Domain\BusinessException;

class LedgerEntryAlreadyReversedException extends BusinessException
{
    public function getHttpStatus(): ?int
    {
        return 409;
    }
}
