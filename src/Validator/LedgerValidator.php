<?php

namespace App\Validator;

use App\Dto\Command\Ledger\CreateDebtCommand;
use App\Dto\Command\Ledger\CreatePaymentCommand;
use App\Entity\Customer;
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
        Customer $customer,
        CreatePaymentCommand $command,
        int $balanceInCents,
    ): void {
        new InvalidAmountException()->assertAmountIsPositive(
            $command->amountInCents
        );

        if (
            $command->amountInCents > $balanceInCents
        ) {
            throw new PaymentAmountException('Le montant du paiement est trop élévé');
        }
    }

    private function validateAmount(
        int $amount,
    ): void {
        if ($amount <= 0) {
            throw new InvalidAmountException();
        }

        if ($amount > 500_000) {
            throw new InvalidAmountException('Montant supérieur à 5 000 €.');
        }
    }
}
