<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

final class PasswordResetToken implements PasswordResetTokenInterface
{
    private bool $isUsed;

    public function __construct(
        private string $tokenValue,
        private string $userID,
        private \DateTimeImmutable $expiresAt,
        private \DateTimeImmutable $createdAt
    ) {
        $this->isUsed = false;
    }

    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }

    public function getUserID(): string
    {
        return $this->userID;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    public function markAsUsed(): void
    {
        $this->isUsed = true;
    }
}
