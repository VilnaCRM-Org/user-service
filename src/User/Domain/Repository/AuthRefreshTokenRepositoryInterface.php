<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\AuthRefreshToken;

interface AuthRefreshTokenRepositoryInterface
{
    public function save(AuthRefreshToken $authRefreshToken): void;

    public function findById(string $id): ?AuthRefreshToken;

    public function findByTokenHash(string $tokenHash): ?AuthRefreshToken;

    /**
     * @return list<AuthRefreshToken>
     */
    public function findBySessionId(string $sessionId): array;

    public function delete(AuthRefreshToken $authRefreshToken): void;

    /**
     * Revoke all refresh tokens for a specific session.
     * Sets revokedAt timestamp on all tokens.
     */
    public function revokeBySessionId(string $sessionId): void;
}
