<?php

namespace App\Validator;

use App\Exception\Domain\Payment\PaymentException;

final readonly class PaymentValidator
{
    public function assertPaymentNotGreaterThanBalance(
        int $payment,
        int $balance,
    ): void {
        if ($payment > $balance) {
            throw new PaymentException('Le paiement dépasse la dette.');
        }
    }
}
