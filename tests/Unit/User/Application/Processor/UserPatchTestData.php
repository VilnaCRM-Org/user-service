<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\User\Domain\Entity\UserInterface;

final class UserPatchTestData
{
    public function __construct(
        private readonly UserInterface $user,
        private readonly string $email,
        private readonly string $initials,
        private readonly string $password,
        private readonly string $userId,
    ) {
    }

    public function __get(string $name): UserInterface|string|null
    {
        return $this->$name ?? null;
    }
}
