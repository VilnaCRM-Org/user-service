<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;

interface ConfirmationEmailFactoryInterface
{
    public function create(
        ConfirmationTokenInterface $token,
        UserInterface $user,
    ): ConfirmationEmail;
}
