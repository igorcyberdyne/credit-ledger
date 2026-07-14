<?php

namespace App\EventListener\Domain;

use App\Dto\Command\Sms\SmsCommand;
use App\Event\Domain\PaymentCreatedEvent;
use App\Notification\Sms\SendSmsMessage;
use App\Notification\Sms\Tools\SmsTemplateBuilder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(PaymentCreatedEvent::class)]
readonly class PaymentCreatedListener
{
    public function __construct(
        private SmsTemplateBuilder $smsTemplateBuilder,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(PaymentCreatedEvent $event): void
    {
        $ledger = $event->getLedgerEntry();

        $sendSmsMessage = new SendSmsMessage(
            new SmsCommand(
                $ledger->getCustomer()->getPhone(),
                $this->smsTemplateBuilder->paymentCreated($ledger),
                $ledger->getUuid(),
                $ledger::class
            )
        );

        $this->messageBus->dispatch($sendSmsMessage);
    }
}
