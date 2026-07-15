<?php

namespace App\Notification\Sms\Provider;

use App\Dto\Command\Sms\SmsCommand;
use App\Notification\Sms\SmsSenderInterface;
use Psr\Log\LoggerInterface;

final class FakeSmsSender implements SmsSenderInterface
{
    /**
     * @var list<SmsCommand>
     */
    private array $messages;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->messages = [];
    }

    public function send(SmsCommand $command): void
    {
        $this->messages[] = $command;

        $this->logger->warning('Fake SMS sent (dev/test environment)', [
            'phone' => $command->phone,
            'message' => $command->message,
            'type' => $command->type,
            'reference' => $command->reference,
        ]);
    }

    /**
     * @return list<SmsCommand>
     */
    public function all(): array
    {
        return $this->messages;
    }

    public function count(): int
    {
        return \count($this->messages);
    }

    public function last(): ?SmsCommand
    {
        if ([] === $this->messages) {
            return null;
        }

        return $this->messages[array_key_last($this->messages)];
    }

    public function clear(): void
    {
        $this->messages = [];
    }
}
