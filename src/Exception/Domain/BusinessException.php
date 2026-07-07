<?php

namespace App\Exception\Domain;

abstract class BusinessException extends \DomainException
{
    private string $businessCode = '###';
    private array $details = [];

    public function getBusinessCode(): string
    {
        return $this->businessCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setBusinessCode(string $businessCode): self
    {
        $this->businessCode = $businessCode;

        return $this;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }
}
