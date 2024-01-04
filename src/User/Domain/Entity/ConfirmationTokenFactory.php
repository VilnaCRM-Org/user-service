<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

class ConfirmationTokenFactory
{
    private int $TOKEN_LENGTH;

    public function __construct()
    {
        $this->TOKEN_LENGTH = 10;
    }

    public function create(string $userID): ConfirmationToken
    {
        return new ConfirmationToken(bin2hex(random_bytes($this->TOKEN_LENGTH)), $userID);
    }
}
