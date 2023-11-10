<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\User\Domain\Entity\User;

interface UserRepository
{
    public function save(User $user): void;
}
