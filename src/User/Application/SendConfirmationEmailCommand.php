<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\Command;

class SendConfirmationEmailCommand implements Command
{
    public function __construct(private string $emailAddress, private string $userId)
    {
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
