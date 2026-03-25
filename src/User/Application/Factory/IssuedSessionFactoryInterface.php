<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\IssuedSession;
use App\User\Domain\Entity\User;
use DateTimeImmutable;

interface IssuedSessionFactoryInterface
{
    public function create(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        DateTimeImmutable $issuedAt
    ): IssuedSession;
}
