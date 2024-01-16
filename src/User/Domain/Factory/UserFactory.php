<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;

readonly class UserFactory
{
    public function __construct(private UuidFactory $uuidFactory)
    {
    }

    public function create(string $email, string $initials, string $password, Uuid $id = null): User
    {
        $id = $id ?? $this->uuidFactory->create();

        return new User($email, $initials, $password, $id);
    }
}
