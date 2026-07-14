<?php

namespace App\Notification\Sms;

use App\Dto\Command\Sms\SmsCommand;

final readonly class SendSmsMessage
{
    public function __construct(
        public SmsCommand $command,
    ) {
    }
}
