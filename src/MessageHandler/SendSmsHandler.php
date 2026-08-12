<?php

namespace App\MessageHandler;

use App\Notification\Sms\SendSmsMessage;
use App\Notification\Sms\SmsSenderInterface;
use App\Tools\TaskExecutor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendSmsHandler
{
    public function __construct(
        private SmsSenderInterface $smsSender,
        private TaskExecutor $executor,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(SendSmsMessage $message): void
    {
        $className = basename(str_replace('\\', '/', $message->command->type));
        $this
            ->executor
            ->configure(
                task: fn () => $this->smsSender->send($message->command),
                taskName: sprintf('sms.%s', $className),
                context: [
                    'phone' => $message->command->phone,
                    'message' => $message->command->message,
                    'reference' => $message->command->reference,
                    'type' => $message->command->type,
                ],
                category: 'sms',
            )
            ->execute();
    }
}
