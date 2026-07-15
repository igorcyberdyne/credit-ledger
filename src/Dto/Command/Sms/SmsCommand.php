<?php

namespace App\Dto\Command\Sms;

final class SmsCommand
{
    public function __construct(
        public string $phone,
        public string $message,
        public string $reference,
        public string $type,
    ) {
    }
}
