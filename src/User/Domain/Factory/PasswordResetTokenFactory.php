<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;

final readonly class PasswordResetTokenFactory implements
    PasswordResetTokenFactoryInterface
{
    public function __construct(private int $tokenLength)
    {
    }

    public function create(string $userID): PasswordResetTokenInterface
    {
        return new PasswordResetToken(
            bin2hex(random_bytes($this->tokenLength)),
            $userID
        );
    }
}