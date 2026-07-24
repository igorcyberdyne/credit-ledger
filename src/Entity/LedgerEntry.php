<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Abstracts\BaseEntitySoftDeletable;
use App\Entity\Contracts\BlameableInterface;
use App\Entity\Traits\BlameableTrait;
use App\Enum\CurrencyEnum;
use App\Enum\LedgerTypeEnum;
use App\Enum\PaymentMethodEnum;
use App\Exception\Domain\Ledger\LedgerEntryAlreadyReversedException;
use App\Repository\LedgerEntryRepository;
use App\ValueObject\Money;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LedgerEntryRepository::class)]
#[ORM\Index(name: 'idx_ledger_type', columns: ['type'])]
class LedgerEntry extends BaseEntitySoftDeletable implements BlameableInterface
{
    use BlameableTrait;
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\ManyToOne(inversedBy: 'ledgerEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Shop $shop = null;

    #[ORM\ManyToOne(inversedBy: 'ledgerEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\Column(enumType: LedgerTypeEnum::class)]
    private LedgerTypeEnum $type;

    #[Assert\Positive]
    #[ORM\Column]
    private int $amountInCents;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true, enumType: PaymentMethodEnum::class)]
    private ?PaymentMethodEnum $paymentMethod = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $occurredAt = null;

    #[ORM\OneToOne(
        targetEntity: self::class,
        inversedBy: 'reversal',
    )]
    #[ORM\JoinColumn(
        name: 'reversed_entry_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    /**
     * L'écriture originale annulée par celle-ci.
     */
    private ?LedgerEntry $originalEntry = null;

    #[ORM\OneToOne(
        targetEntity: self::class,
        mappedBy: 'originalEntry',
    )]
    /**
     * L'écriture qui annule celle-ci.
     */
    private ?LedgerEntry $reversal = null;

    /**
     * L'écriture originale corrigée par celle-ci.
     */
    #[ORM\ManyToOne(
        targetEntity: self::class,
        inversedBy: 'corrections'
    )]
    private ?LedgerEntry $correctedEntry = null;

    #[ORM\OneToMany(
        targetEntity: self::class,
        mappedBy: 'correctedEntry'
    )]
    private Collection $corrections;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->corrections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function setShop(?Shop $shop): static
    {
        $this->shop = $shop;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getType(): ?LedgerTypeEnum
    {
        return $this->type;
    }

    public function setType(LedgerTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethodEnum
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethodEnum $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getOccurredAt(): ?\DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(?\DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function isDebt(): bool
    {
        return LedgerTypeEnum::DEBT === $this->type;
    }

    public function isPayment(): bool
    {
        return LedgerTypeEnum::PAYMENT === $this->type;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %.2f %s',
            $this->type->value,
            Money::fromCents($this->amountInCents)->decimal(),
            CurrencyEnum::EURO->symbol()
        );
    }

    public function getAmountInCents(): ?int
    {
        return $this->amountInCents;
    }

    public function getAmountFormat(): string
    {
        return new Money($this->getAmountInCents(), $this->getShop()->getCurrency())->format();
    }

    public function getAmountDecimal(): string
    {
        return new Money($this->getAmountInCents(), $this->getShop()->getCurrency())->decimal();
    }

    public function setAmountInCents(int $amountInCents): static
    {
        $this->amountInCents = $amountInCents;

        return $this;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function balanceImpact(): int
    {
        return match ($this->getType()) {
            LedgerTypeEnum::DEBT => $this->amountInCents,
            LedgerTypeEnum::PAYMENT => -$this->amountInCents,
        };
    }

    public function getOriginalEntry(): ?self
    {
        return $this->originalEntry;
    }

    public function setOriginalEntry(?self $originalEntry): static
    {
        $this->originalEntry = $originalEntry;

        return $this;
    }

    public function getReversal(): ?self
    {
        return $this->reversal;
    }

    public function setReversal(?self $reversal): static
    {
        // unset the owning side of the relation if necessary
        if (null === $reversal && null !== $this->reversal) {
            $this->reversal->setOriginalEntry(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $reversal && $reversal->getOriginalEntry() !== $this) {
            $reversal->setOriginalEntry($this);
        }

        $this->reversal = $reversal;

        return $this;
    }

    public function getCorrectedEntry(): ?self
    {
        return $this->correctedEntry;
    }

    public function setCorrectedEntry(?self $correctedEntry): static
    {
        $this->correctedEntry = $correctedEntry;

        return $this;
    }

    /**
     * @return Collection<int, LedgerEntry>
     */
    public function getCorrections(): Collection
    {
        return $this->corrections;
    }

    public function addCorrection(LedgerEntry $correction): static
    {
        if (!$this->corrections->contains($correction)) {
            $this->corrections->add($correction);
            $correction->setCorrectedEntry($this);
        }

        return $this;
    }

    public function removeCorrection(LedgerEntry $correction): static
    {
        if ($this->corrections->removeElement($correction)) {
            // set the owning side to null (unless already changed)
            if ($correction->getCorrectedEntry() === $this) {
                $correction->setCorrectedEntry(null);
            }
        }

        return $this;
    }

    /**
     * Une écriture de reverse est purement technique.
     */
    public function isReverseEntry(): bool
    {
        return null !== $this->originalEntry;
    }

    /**
     * Détermine si une opération est annulée.
     */
    public function isCancelled(): bool
    {
        return null !== $this->reversal;
    }

    /**
     * Détermine si l'écriture est une correction.
     */
    public function isCorrection(): bool
    {
        return null !== $this->correctedEntry;
    }

    /**
     * Détermine si cette opération peut encore être annulée.
     */
    public function canBeReversed(): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        return true;
    }

    /**
     * Détermine si cette opération peut encore être corrigée.
     */
    public function canCorrect(): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        if ($this->isCorrection()) {
            return false;
        }

        return true;
    }

    public function reverse(): LedgerEntry
    {
        if (!$this->canBeReversed()) {
            throw new LedgerEntryAlreadyReversedException('Cette écriture est déjà annulée.');
        }

        $reverse = new self();

        $reverse
            ->setCustomer($this->customer)
            ->setOccurredAt(new \DateTimeImmutable())
            ->setAmountInCents($this->amountInCents)
            ->setOriginalEntry($this)
            ->setDescription(sprintf(
                'Annulation%s : %s',
                !empty($this->getDescription()) ? "({$this->getDescription()})" : '',
                $this->getAmountFormat(),
            ));

        $this->setReversal($reverse); // indique qu'elle est annulée

        $reverse->setType(
            match ($this->type) {
                LedgerTypeEnum::DEBT => LedgerTypeEnum::PAYMENT,
                LedgerTypeEnum::PAYMENT => LedgerTypeEnum::DEBT,
            }
        );

        return $reverse;
    }
}
