<?php

namespace App\Notification\Sms\Tools;

use App\Entity\LedgerEntry;
use App\Entity\Shop;
use App\Enum\CurrencyEnum;
use App\Service\Domain\Customer\Impl\CustomerBalanceService;
use App\ValueObject\Money;

final readonly class SmsTemplateBuilder
{
    public function __construct(
        private CustomerBalanceService $customerBalanceService,
    ) {
    }

    private function money(int $amountInCents, CurrencyEnum $currencyEnum): string
    {
        return new Money($amountInCents, $currencyEnum)->format();
    }

    private function shopInfo(Shop $shop): string
    {
        return sprintf(
            "\n\n%s\nMerci",
            $shop->getName()
        );
    }

    public function debtCreated(
        LedgerEntry $ledgerEntry,
    ): string {
        $customer = $ledgerEntry->getCustomer();

        return sprintf(
            "Bonjour %s,\n\nUne dette de %s a été ajoutée.\nVotre solde est désormais de %s.%s",
            $customer->getFirstName(),
            $this->money($ledgerEntry->getAmountInCents(), $ledgerEntry->getShop()->getCurrency()),
            $this->money($this->customerBalanceService->getBalanceInCents($customer), $ledgerEntry->getShop()->getCurrency()),
            $this->shopInfo($ledgerEntry->getShop()),
        );
    }

    public function paymentCreated(
        LedgerEntry $ledgerEntry,
    ): string {
        $customer = $ledgerEntry->getCustomer();

        return sprintf(
            "Bonjour %s,\n\nNous avons reçu un paiement de %s.\nVotre solde restant est de %s.%s",
            $customer->getFirstName(),
            $this->money($ledgerEntry->getAmountInCents(), $ledgerEntry->getShop()->getCurrency()),
            $this->money($this->customerBalanceService->getBalanceInCents($customer), $ledgerEntry->getShop()->getCurrency()),
            $this->shopInfo($ledgerEntry->getShop()),
        );
    }
}
