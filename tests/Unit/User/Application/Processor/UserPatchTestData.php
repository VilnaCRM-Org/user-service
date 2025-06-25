<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\User\Domain\Entity\UserInterface;

final class UserPatchTestData
{
    public function __construct(
        public readonly UserInterface $user,
        public readonly string $email,
        public readonly string $initials,
        public readonly string $password,
        public readonly string $userId,
    ) {
    }
}
