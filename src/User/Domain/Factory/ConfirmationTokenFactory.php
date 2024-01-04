<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\ConfirmationToken;

class ConfirmationTokenFactory
{
    public function __construct(private int $tokenLength)
    {
    }

    public function create(string $userID): ConfirmationToken
    {
        return new ConfirmationToken(bin2hex(random_bytes($this->tokenLength)), $userID);
    }
}
