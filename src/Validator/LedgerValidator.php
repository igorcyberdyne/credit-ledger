<?php

namespace App\Validator;

use App\Dto\Command\Domain\Ledger\CreateDebtCommand;
use App\Dto\Command\Domain\Ledger\CreatePaymentCommand;
use App\Exception\Domain\Ledger\InvalidAmountException;
use App\Exception\Domain\Payment\PaymentAmountException;

final readonly class LedgerValidator
{
    public function validateDebt(
        CreateDebtCommand $command,
    ): void {
        new InvalidAmountException()->assertAmountIsPositive(
            $command->amountInCents
        );
    }

    public function validatePayment(
        int $customerBalanceInCents,
        CreatePaymentCommand $command,
    ): void {
        new InvalidAmountException()->assertAmountIsPositive(
            $command->amountInCents
        );

        if ($customerBalanceInCents <= 0) {
            throw new PaymentAmountException('Le client ne possède aucune dette.');
        }

        if ($command->amountInCents > $customerBalanceInCents) {
            throw new PaymentAmountException(sprintf('Le paiement est supérieur au solde restant. B: %s, A: %s', $customerBalanceInCents, $command->amountInCents));
        }
    }
}
