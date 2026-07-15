<?php

namespace App\EventListener\Domain;

use App\Dto\Command\Sms\SmsCommand;
use App\Entity\LedgerEntry;
use App\Event\Domain\DebtCreatedEvent;
use App\Event\Domain\PaymentCreatedEvent;
use App\Notification\Sms\SendSmsMessage;
use App\Notification\Sms\Tools\SmsTemplateBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: DebtCreatedEvent::class, method: 'onDebtCreated')]
#[AsEventListener(event: PaymentCreatedEvent::class, method: 'onPaymentCreated')]
final readonly class LedgerSmsListener
{
    public function __construct(
        private SmsTemplateBuilder $builder,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    private function isValidRecipient(LedgerEntry $ledger): bool
    {
        $customer = $ledger->getCustomer();

        if (null !== $customer && !empty($customer->getPhone())) {
            return true;
        }

        $this->logger->emergency('Cannot dispatch SMS to Messenger (dev/test environment). The customer does not have a valid phone number', [
            'id' => $customer->getId(),
            'firstname' => $customer->getFirstname(),
        ]);

        return false;
    }

    private function dispatch(
        string $message,
        LedgerEntry $ledger,
    ): void {
        try {
            $this->bus->dispatch(
                new SendSmsMessage(
                    new SmsCommand(
                        $ledger->getCustomer()->getPhone(),
                        $message,
                        $ledger->getUuid(),
                        $ledger::class
                    ),
                ),
            );
        } catch (\Throwable $exception) {
            $this->logger->emergency(
                sprintf(
                    'Cannot dispatch SMS to Messenger (dev/test environment). %s',
                    $exception->getMessage()
                ),
                $exception->getTrace()
            );
        }
    }

    public function onDebtCreated(
        DebtCreatedEvent $event,
    ): void {
        $ledger = $event->getLedgerEntry();

        if (!$this->isValidRecipient($ledger)) {
            return;
        }

        $this->dispatch(
            $this->builder->debtCreated($ledger),
            $ledger
        );
    }

    public function onPaymentCreated(
        PaymentCreatedEvent $event,
    ): void {
        $ledger = $event->getLedgerEntry();

        if (!$this->isValidRecipient($ledger)) {
            return;
        }

        $this->dispatch(
            $this->builder->paymentCreated($ledger),
            $ledger
        );
    }
}
