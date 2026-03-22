<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\PasswordResetTokenInterface;

interface PasswordResetTokenFactoryInterface
{
    public function create(string $userID): PasswordResetTokenInterface;
}
