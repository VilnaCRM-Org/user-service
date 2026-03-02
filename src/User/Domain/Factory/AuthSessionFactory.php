<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\AuthSession;
use DateTimeImmutable;

final readonly class AuthSessionFactory implements AuthSessionFactoryInterface
{
    #[\Override]
    public function create(
        string $id,
        string $userId,
        string $ipAddress,
        string $userAgent,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt,
        bool $rememberMe,
    ): AuthSession {
        return new AuthSession(
            $id,
            $userId,
            $ipAddress,
            $userAgent,
            $createdAt,
            $expiresAt,
            $rememberMe
        );
    }
}
