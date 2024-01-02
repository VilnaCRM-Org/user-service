<?php

namespace App\User\Domain\Entity;

use Symfony\Component\Uid\Uuid;

readonly class UserFactory
{
    public function create(string $email, string $initials, string $password, Uuid $id = null): User
    {
        return new User($email, $initials, $password, $id);
    }
}
