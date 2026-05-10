<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\AuthSession;
use DateTimeImmutable;

interface AuthSessionFactoryInterface
{
    public function create(
        string $id,
        string $userId,
        string $ipAddress,
        string $userAgent,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt,
        bool $rememberMe,
    ): AuthSession;
}
