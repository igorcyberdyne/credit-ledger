<?php

namespace App\Entity\Contracts;

use App\Entity\User;

interface BlameableInterface
{
    public function getCreatedBy(): ?User;

    public function setCreatedBy(?User $user): static;

    public function getUpdatedBy(): ?User;

    public function setUpdatedBy(?User $user): static;
}
