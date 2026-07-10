<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Abstracts\BaseEntitySoftDeletable;
use App\Entity\Contracts\BlameableInterface;
use App\Entity\Traits\BlameableTrait;
use App\Enum\CustomerStatusEnum;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[UniqueEntity(fields: ['shop', 'phone'], message: '')]
#[ORM\UniqueConstraint(
    name: 'uniq_shop_phone',
    columns: ['shop_id', 'phone']
)]
#[ORM\Index(name: 'idx_customer_lastname', columns: ['lastname'])]
#[ORM\Index(name: 'idx_customer_phone', columns: ['phone'])]
class Customer extends BaseEntitySoftDeletable implements BlameableInterface
{
    use BlameableTrait;
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\ManyToOne(inversedBy: 'customers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Shop $shop = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $firstname;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastname = null;

    #[Assert\Length(max: 30)]
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(enumType: CustomerStatusEnum::class)]
    private CustomerStatusEnum $status = CustomerStatusEnum::ACTIVE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[Assert\Positive]
    #[ORM\Column]
    private int $balanceInCents = 0;

    /**
     * @var Collection<int, LedgerEntry>
     */
    #[ORM\OneToMany(
        targetEntity: LedgerEntry::class,
        mappedBy: 'customer',
        cascade: ['persist'],
        orphanRemoval: false
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $ledgerEntries;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->ledgerEntries = new ArrayCollection();
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstname.' '.($this->lastname ?? ''));
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getStatus(): ?CustomerStatusEnum
    {
        return $this->status;
    }

    public function setStatus(CustomerStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return CustomerStatusEnum::ACTIVE === $this->status;
    }

    /**
     * @return Collection<int, LedgerEntry>
     */
    public function getLedgerEntries(): Collection
    {
        return $this->ledgerEntries;
    }

    public function addLedgerEntry(LedgerEntry $ledgerEntry): static
    {
        if (!$this->ledgerEntries->contains($ledgerEntry)) {
            $this->ledgerEntries->add($ledgerEntry);
            $ledgerEntry->setCustomer($this);
        }

        return $this;
    }

    public function removeLedgerEntry(LedgerEntry $ledgerEntry): static
    {
        if ($this->ledgerEntries->removeElement($ledgerEntry)) {
            // set the owning side to null (unless already changed)
            if ($ledgerEntry->getCustomer() === $this) {
                $ledgerEntry->setCustomer(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getBalanceInCents(): ?int
    {
        return $this->balanceInCents;
    }

    public function setBalanceInCents(int $balanceInCents): static
    {
        $this->balanceInCents = $balanceInCents;

        return $this;
    }

    public function increaseBalance(int $amountInCents): static
    {
        if ($amountInCents <= 0) {
            throw new \InvalidArgumentException();
        }

        $this->balanceInCents += $amountInCents;

        return $this;
    }

    public function decreaseBalance(int $amountInCents): static
    {
        if ($amountInCents <= 0) {
            throw new \InvalidArgumentException();
        }

        if ($amountInCents > $this->balanceInCents) {
            throw new \LogicException('Balance cannot become negative.');
        }

        $this->balanceInCents -= $amountInCents;

        return $this;
    }

    public function applyLedgerEntry(
        LedgerEntry $entry,
    ): self {
        $this->balanceInCents += $entry->balanceImpact();

        if ($this->balanceInCents < 0) {
            throw new \LogicException('Le solde ne peut pas être négatif.');
        }

        return $this;
    }
}
