<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\User;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

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
