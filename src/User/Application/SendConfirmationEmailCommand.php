<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Domain\Entity\Token\ConfirmationToken;

class SendConfirmationEmailCommand implements Command
{
    public function __construct(private string $emailAddress, private ConfirmationToken $token)
    {
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getToken(): ConfirmationToken
    {
        return $this->token;
    }
}
