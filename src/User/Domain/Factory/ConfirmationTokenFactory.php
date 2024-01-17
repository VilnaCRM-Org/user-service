<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;

class ConfirmationTokenFactory
{
    public function __construct(private int $tokenLength)
    {
    }

    public function create(string $userID): ConfirmationTokenInterface
    {
        return new ConfirmationToken(bin2hex(random_bytes($this->tokenLength)), $userID);
    }
}
