<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\UserInterface;

interface UserFactoryInterface
{
    public function create(
        string $email,
        string $initials,
        string $password,
        Uuid $id
    ): UserInterface;
}
