<?php

namespace App\Dto\Command\Ledger;

use App\Enum\PaymentMethodEnum;
use Symfony\Component\Validator\Constraints as Assert;

class CreatePaymentCommand
{
    public function __construct(
        #[Assert\Positive]
        public int $amountInCents,
        public PaymentMethodEnum $paymentMethod,
    ) {
    }
}
