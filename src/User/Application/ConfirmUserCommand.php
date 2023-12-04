<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Domain\Entity\Token\ConfirmationToken;

class ConfirmUserCommand implements Command
{
    public function __construct(private ConfirmationToken $token)
    {
    }

    public function getToken(): ConfirmationToken
    {
        return $this->token;
    }
}
