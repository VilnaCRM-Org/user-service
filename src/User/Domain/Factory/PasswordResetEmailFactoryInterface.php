<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\PasswordResetEmailInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;

interface PasswordResetEmailFactoryInterface
{
    public function create(
        PasswordResetTokenInterface $token,
        UserInterface $user,
    ): PasswordResetEmailInterface;
}
