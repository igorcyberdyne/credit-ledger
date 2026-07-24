<?php

declare(strict_types=1);

namespace App\Service\Domain\Ledger;

use App\Dto\Response\Domain\Ledger\CustomerLedgerItemResponse;
use App\Entity\LedgerEntry;
use App\Enum\LedgerTypeEnum;

final class CustomerLedgerTimelineBuilder
{
    /**
     * Construit la timeline métier affichée dans l'application.
     *
     * Les écritures techniques de reverse ne sont jamais exposées.
     *
     * @param iterable<LedgerEntry> $entries
     *
     * @return CustomerLedgerItemResponse[]
     */
    public function build(iterable $entries): array
    {
        $timeline = [];

        $lastEntry = null;
        foreach ($entries as $entry) {
            /*
             * Une écriture possédant un reversedEntry
             * est l'écriture technique créée lors d'un reverse.
             *
             * Elle ne doit jamais être affichée.
             */
            if ($entry->isReverseEntry()) {
                continue;
            }

            /*
             * Cela va permettre de rendre uniquement l'entrée la plus récente modifiable (reverse ou correct)
             * Voir la méthode buildItem(...)
             */
            if (null === $lastEntry) {
                $lastEntry = $entry;
            }

            $timeline[] = $this->buildItem($entry, $lastEntry);
        }

        return $timeline;
    }

    /**
     * Construit un élément de timeline.
     */
    private function buildItem(LedgerEntry $entry, ?LedgerEntry $lastEntry = null): CustomerLedgerItemResponse
    {
        $uuid = $entry->getUuid()->toRfc4122();
        $isLasEntry = false;
        if ($lastEntry && $lastEntry->getUuid()->equals($entry->getUuid()) && ($entry->canCorrect() || $entry->canBeReversed())) {
            $isLasEntry = true;
        }

        return new CustomerLedgerItemResponse(
            uuid: $uuid,
            type: $entry->getType()->value,
            amount: $entry->getAmountDecimal(),
            description: $this->buildDescription($entry),
            occurredAt: ($entry->getOccurredAt() ?? $entry->getCreatedAt())->format(DATE_ATOM),
            paymentMethod: $entry->getPaymentMethod(),
            status: $this->buildStatus($entry),
            isCorrection: $entry->isCorrection(),
            correctedEntryUuid: $this->previousUuid($entry),
            previousAmount: $this->previousAmount($entry),
            // canReverse: $isLasEntry,
            // canCorrect: $isLasEntry, // 'd M Y H:i:s'
            canReverse: $entry->canBeReversed(),
            canCorrect: $entry->canCorrect(),
            icon: $this->buildIcon($entry),
            color: $this->buildColor($entry),
            badge: $this->buildBadge($entry),
        );
    }

    /**
     * ACTIVE / CANCELLED.
     */
    private function buildStatus(LedgerEntry $entry): string
    {
        return null === $entry->getReversal()
            ? 'ACTIVE'
            : 'CANCELLED';
    }

    /**
     * Dette créée
     * Paiement
     * Dette annulée
     * Paiement corrigé...
     */

    /**
     * Libellé affiché dans l'application.
     */
    private function buildDescription(LedgerEntry $entry): string
    {
        if ($entry->isCorrection()) {
            return match ($entry->getType()) {
                LedgerTypeEnum::DEBT => sprintf(
                    'Dette corrigée (%d → %d)',
                    $entry->getCorrectedEntry()->getAmountFormat(),
                    $entry->getAmountFormat(),
                ),
                LedgerTypeEnum::PAYMENT => sprintf(
                    'Paiement corrigé (%d → %d)',
                    $entry->getCorrectedEntry()->getAmountFormat(),
                    $entry->getAmountFormat(),
                ),
            };
        }

        if ($entry->isCancelled()) {
            return match ($entry->getType()) {
                LedgerTypeEnum::DEBT => 'Dette annulée',
                LedgerTypeEnum::PAYMENT => 'Paiement annulé',
            };
        }

        return match ($entry->getType()) {
            LedgerTypeEnum::DEBT => $entry->getDescription() ?? 'Dette',
            LedgerTypeEnum::PAYMENT => $entry->getDescription() ?? 'Paiement',
        };
    }

    private function buildIcon(LedgerEntry $entry): string
    {
        if ($entry->isCorrection()) {
            return 'edit';
        }

        if ($entry->isCancelled()) {
            return 'undo';
        }

        return match ($entry->getType()) {
            LedgerTypeEnum::DEBT => 'arrow_up',
            LedgerTypeEnum::PAYMENT => 'arrow_down',
        };
    }

    private function buildColor(LedgerEntry $entry): string
    {
        if ($entry->isCancelled()) {
            return 'grey';
        }

        return match ($entry->getType()) {
            LedgerTypeEnum::DEBT => 'red',
            LedgerTypeEnum::PAYMENT => 'green',
        };
    }

    private function buildBadge(LedgerEntry $entry): ?string
    {
        if ($entry->isCorrection()) {
            return 'Correction';
        }

        if ($entry->isCancelled()) {
            return 'Annulée';
        }

        return null;
    }

    /**
     * Montant précédent lors d'une correction.
     */
    private function previousAmount(LedgerEntry $entry): ?string
    {
        if (!$entry->isCorrection()) {
            return null;
        }

        return $entry
            ->getCorrectedEntry()
            ?->getAmountDecimal();
    }

    private function previousUuid(LedgerEntry $entry): ?string
    {
        if (!$entry->isCorrection()) {
            return null;
        }

        return $entry
            ->getCorrectedEntry()
            ?->getUuid()
            ->toRfc4122();
    }

    private function previousType(LedgerEntry $entry): ?string
    {
        if (!$entry->isCorrection()) {
            return null;
        }

        return $entry
            ->getCorrectedEntry()
            ?->getType()
            ->value;
    }
}
