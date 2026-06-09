<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

class PasswordResetToken implements PasswordResetTokenInterface
{
    private bool $isUsed;

    private string $tokenValue;

    /**
     * Transient plaintext token. Only populated when the token is created or
     * re-attached for delivery; it is never persisted (only the hash is).
     */
    private ?string $plainToken;

    public function __construct(
        string $plainToken,
        private string $userID,
        private DateTimeImmutable $expiresAt,
        private DateTimeImmutable $createdAt
    ) {
        $this->plainToken = $plainToken;
        $this->tokenValue = self::hashToken($plainToken);
        $this->isUsed = false;
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Stored hash of the token (used as the document identifier).
     */
    #[\Override]
    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }

    /**
     * Plaintext token to embed in the reset link; null once reconstituted
     * from storage and not re-attached.
     */
    #[\Override]
    public function getPlainToken(): ?string
    {
        return $this->plainToken;
    }

    #[\Override]
    public function attachPlainToken(string $plainToken): void
    {
        $this->plainToken = $plainToken;
    }

    #[\Override]
    public function matchesToken(string $plainToken): bool
    {
        return hash_equals($this->tokenValue, self::hashToken($plainToken));
    }

    #[\Override]
    public function getUserID(): string
    {
        return $this->userID;
    }

    #[\Override]
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    #[\Override]
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[\Override]
    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    #[\Override]
    public function isExpired(?DateTimeImmutable $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTimeImmutable();
        return $currentTime > $this->expiresAt;
    }

    #[\Override]
    public function markAsUsed(): void
    {
        $this->isUsed = true;
    }

    #[\Override]
    public function extendExpiration(DateTimeImmutable $newExpiresAt): void
    {
        $this->expiresAt = $newExpiresAt;
    }

    #[\Override]
    public function resetUsage(): void
    {
        $this->isUsed = false;
    }
}
