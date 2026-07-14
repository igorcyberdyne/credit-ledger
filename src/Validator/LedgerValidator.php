<?php

namespace App\Validator;

use App\Dto\Command\Domain\Ledger\CreateDebtCommand;
use App\Dto\Command\Domain\Ledger\CreatePaymentCommand;
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
    ): void {
        new InvalidAmountException()->assertAmountIsPositive(
            $command->amountInCents
        );

        if ($customer->getBalanceInCents() <= 0) {
            throw new PaymentAmountException('Le client ne possède aucune dette.');
        }

        if ($command->amountInCents > $customer->getBalanceInCents()) {
            throw new PaymentAmountException('Le paiement est supérieur au solde restant.');
        }
    }
}
