<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\User;
use DateTimeImmutable;

interface AuthTokenFactoryInterface
{
    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array{sub: string, iss: non-empty-string, aud: non-empty-string, exp: int, iat: int, nbf: int, jti: string, sid: string, roles: list{'ROLE_USER'}}
     */
    public function buildJwtPayload(
        User $user,
        string $sessionId,
        DateTimeImmutable $issuedAt
    ): array;

    public function generateOpaqueToken(): string;

    public function createRefreshToken(
        string $sessionId,
        string $plainToken,
        DateTimeImmutable $issuedAt
    ): AuthRefreshToken;
}
