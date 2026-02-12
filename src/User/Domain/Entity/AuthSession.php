<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class AuthSession
{
    private ?DateTimeImmutable $revokedAt = null;

    public function __construct(
        private string $id,
        private string $userId,
        private string $ipAddress,
        private string $userAgent,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $expiresAt,
        private bool $rememberMe
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function revoke(?DateTimeImmutable $revokedAt = null): void
    {
        $this->revokedAt = $revokedAt ?? new DateTimeImmutable();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(?DateTimeImmutable $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTimeImmutable();

        return $currentTime > $this->expiresAt;
    }
}
