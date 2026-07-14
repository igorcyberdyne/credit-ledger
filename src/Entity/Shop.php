<?php

namespace App\Entity;

use App\Entity\Abstracts\BaseEntitySoftDeletable;
use App\Enum\CurrencyEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Index(name: 'idx_shop_slug', columns: ['slug'])]
class Shop extends BaseEntitySoftDeletable
{
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120)]
    private string $name;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120, unique: true)]
    private string $slug;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 120)]
    #[ORM\Column(length: 120)]
    private string $country = 'France';

    #[Assert\Length(max: 30)]
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 5, nullable: false, enumType: CurrencyEnum::class)]
    private CurrencyEnum $currency = CurrencyEnum::EURO;

    #[ORM\Column(length: 60)]
    private string $timezone = 'Europe/Paris';

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(
        targetEntity: User::class,
        mappedBy: 'shop',
        cascade: ['persist'],
        orphanRemoval: false
    )]
    private Collection $users;

    /**
     * @var Collection<int, Customer>
     */
    #[ORM\OneToMany(
        targetEntity: Customer::class,
        mappedBy: 'shop',
        cascade: ['persist'],
        orphanRemoval: false
    )]
    private Collection $customers;

    /**
     * @var Collection<int, LedgerEntry>
     */
    #[ORM\OneToMany(
        targetEntity: LedgerEntry::class,
        mappedBy: 'shop',
        cascade: ['persist'],
        orphanRemoval: false
    )]
    private Collection $ledgerEntries;

    public function __construct()
    {
        $this->uuid = Uuid::v7();

        $this->users = new ArrayCollection();
        $this->customers = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
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

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setShop($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getShop() === $this) {
                $user->setShop(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getCustomers(): Collection
    {
        return $this->customers;
    }

    /**
     * @return Collection<int, LedgerEntry>
     */
    public function getLedgerEntries(): Collection
    {
        return $this->ledgerEntries;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function addCustomer(Customer $customer): static
    {
        if (!$this->customers->contains($customer)) {
            $this->customers->add($customer);
            $customer->setShop($this);
        }

        return $this;
    }

    public function removeCustomer(Customer $customer): static
    {
        if ($this->customers->removeElement($customer)) {
            // set the owning side to null (unless already changed)
            if ($customer->getShop() === $this) {
                $customer->setShop(null);
            }
        }

        return $this;
    }

    public function addLedgerEntry(LedgerEntry $ledgerEntry): static
    {
        if (!$this->ledgerEntries->contains($ledgerEntry)) {
            $this->ledgerEntries->add($ledgerEntry);
            $ledgerEntry->setShop($this);
        }

        return $this;
    }

    public function removeLedgerEntry(LedgerEntry $ledgerEntry): static
    {
        if ($this->ledgerEntries->removeElement($ledgerEntry)) {
            // set the owning side to null (unless already changed)
            if ($ledgerEntry->getShop() === $this) {
                $ledgerEntry->setShop(null);
            }
        }

        return $this;
    }

    public function getCurrency(): ?CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(CurrencyEnum $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}
