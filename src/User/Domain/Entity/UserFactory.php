<?php

namespace App\User\Domain\Entity;

use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

readonly class UserFactory
{
    public function __construct(private UuidFactory $uuidFactory)
    {
    }

    public function create(string $email, string $initials, string $password, Uuid $id = null): User
    {
        if (!$id) {
            $id = $this->uuidFactory->create();
        }

        return new User($id, $email, $initials, $password);
    }
}
