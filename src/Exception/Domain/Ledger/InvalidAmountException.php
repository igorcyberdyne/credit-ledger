<?php

namespace App\Exception\Domain\Ledger;

use App\Exception\Domain\BusinessException;

class InvalidAmountException extends BusinessException
{
    public function assertAmountIsPositive(
        int $amountInCents,
    ): void {
        if ($amountInCents <= 0) {
            throw new InvalidAmountException('Le montant doit être supérieur à zéro.');
        }
    }

    public function assertMaximumAmount(
        int $amountInCents,
    ): void {
        if ($amountInCents > 500_000) {
            throw new InvalidAmountException('Montant trop élevé.');
        }
    }
}
