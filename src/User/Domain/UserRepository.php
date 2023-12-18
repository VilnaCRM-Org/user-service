<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\User\Domain\Entity\User\User;

interface UserRepository
{
    public function save(User $user): void;

    public function find(string $userID): User;
}