<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class AuthRefreshToken
{
    private ?DateTimeImmutable $rotatedAt = null;
    private bool $graceUsed = false;
    private ?DateTimeImmutable $revokedAt = null;

    private string $tokenHash;

    public function __construct(
        private string $id,
        private string $sessionId,
        string $plainToken,
        private DateTimeImmutable $expiresAt
    ) {
        $this->tokenHash = $this->hash($plainToken);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getRotatedAt(): ?DateTimeImmutable
    {
        return $this->rotatedAt;
    }

    public function isGraceUsed(): bool
    {
        return $this->graceUsed;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function markAsRotated(?DateTimeImmutable $rotatedAt = null): void
    {
        $this->rotatedAt = $rotatedAt ?? new DateTimeImmutable();
    }

    public function isRotated(): bool
    {
        return $this->rotatedAt !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function rotate(string $newPlainToken, ?DateTimeImmutable $rotatedAt = null): void
    {
        $this->tokenHash = $this->hash($newPlainToken);
        $this->rotatedAt = $rotatedAt ?? new DateTimeImmutable();
        $this->graceUsed = false;
    }

    public function markGraceUsed(): void
    {
        $this->graceUsed = true;
    }

    public function revoke(?DateTimeImmutable $revokedAt = null): void
    {
        $this->revokedAt = $revokedAt ?? new DateTimeImmutable();
    }

    public function isExpired(?DateTimeImmutable $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTimeImmutable();

        return $currentTime > $this->expiresAt;
    }

    public function isWithinGracePeriod(
        DateTimeImmutable $currentTime,
        int $gracePeriodSeconds
    ): bool {
        if ($this->rotatedAt === null || $gracePeriodSeconds < 0) {
            return false;
        }

        $gracePeriodEnd = $this->rotatedAt->modify("+{$gracePeriodSeconds} seconds");

        return $currentTime <= $gracePeriodEnd;
    }

    public function matchesToken(string $plainToken): bool
    {
        return hash_equals($this->tokenHash, $this->hash($plainToken));
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
