<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;

interface ConfirmationEmailFactoryInterface
{
    public function create(
        ConfirmationTokenInterface $token,
        UserInterface $user,
    ): ConfirmationEmailInterface;
}
