<?php

namespace App\Exception\Domain\Ledger;

use App\Exception\Domain\BusinessException;

class LedgerEntryCannotBeReversedException extends BusinessException
{
    public function getHttpStatus(): ?int
    {
        return 409;
    }
}
