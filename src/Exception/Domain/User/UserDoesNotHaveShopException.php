<?php

namespace App\Exception\Domain\User;

use App\Exception\Domain\BusinessException;

class UserDoesNotHaveShopException extends BusinessException
{
    public function __construct()
    {
        parent::__construct("L'utilisateur n'appartient à aucun shop");
    }

    public function getHttpStatus(): ?int
    {
        return 403;
    }
}
