<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;
use App\User\Domain\Entity\ConfirmationToken;

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
