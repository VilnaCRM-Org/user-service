<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class PasswordResetToken implements PasswordResetTokenInterface
{
    private bool $isUsed;

    public function __construct(
        private string $tokenValue,
        private string $userID,
        private DateTimeImmutable $expiresAt,
        private DateTimeImmutable $createdAt
    ) {
        $this->isUsed = false;
    }

    #[\Override]
    public function getTokenValue(): string
    {
        return $this->tokenValue;
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
}
