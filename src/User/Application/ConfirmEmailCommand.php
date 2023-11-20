<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\Command;

class ConfirmEmailCommand implements Command
{
    public function __construct(private string $tokenValue)
    {
    }

    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }
}
