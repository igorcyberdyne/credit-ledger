<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Abstracts\BaseEntitySoftDeletable;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_user_phone', columns: ['phone'])]
class User extends BaseEntitySoftDeletable implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\ManyToOne(targetEntity: Shop::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Shop $shop = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $firstname;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastname = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[Assert\Length(max: 30)]
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\OneToMany(
        targetEntity: LedgerEntry::class,
        mappedBy: 'user'
    )]
    private Collection $ledgerEntries;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column(enumType: UserStatusEnum::class)]
    private UserStatusEnum $status = UserStatusEnum::ACTIVE;

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
        return trim($this->firstname.' '.$this->lastname);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function isOwner(): bool
    {
        return in_array(UserRoleEnum::OWNER->value, $this->getRoles(), true);
    }

    public function isManager(): bool
    {
        return in_array(UserRoleEnum::MANAGER->value, $this->getRoles(), true);
    }

    public function isEmployee(): bool
    {
        return in_array(UserRoleEnum::EMPLOYEE->value, $this->getRoles(), true);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getStatus(): ?UserStatusEnum
    {
        return $this->status;
    }

    public function setStatus(UserStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isActive(): bool
    {
        return UserStatusEnum::ACTIVE === $this->status;
    }

    public function eraseCredentials(): void
    {
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
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
            $ledgerEntry->setUser($this);
        }

        return $this;
    }

    public function removeLedgerEntry(LedgerEntry $ledgerEntry): static
    {
        if ($this->ledgerEntries->removeElement($ledgerEntry)) {
            // set the owning side to null (unless already changed)
            if ($ledgerEntry->getUser() === $this) {
                $ledgerEntry->setUser(null);
            }
        }

        return $this;
    }
}
