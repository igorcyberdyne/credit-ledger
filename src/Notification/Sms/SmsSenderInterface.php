<?php

declare(strict_types=1);

namespace App\Notification\Sms;

use App\Dto\Command\Sms\SmsCommand;

interface SmsSenderInterface
{
    public function send(SmsCommand $command): void;
}
