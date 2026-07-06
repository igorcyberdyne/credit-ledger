<?php

declare(strict_types=1);

namespace App\Entity\Contracts;

use App\Entity\User;

interface SoftDeletableInterface
{
    public function getDeletedAt(): ?\DateTimeImmutable;

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static;

    public function getDeletedBy(): ?User;

    public function setDeletedBy(?User $user): static;

    public function isDeleted(): bool;
}
