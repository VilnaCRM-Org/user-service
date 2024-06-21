<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\ConfirmationTokenInterface;

interface ConfirmationTokenFactoryInterface
{
    public function create(string $userID): ConfirmationTokenInterface;
}
