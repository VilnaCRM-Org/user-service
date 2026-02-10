<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class PendingTwoFactor
{
    private const DEFAULT_TTL_MINUTES = 5;

    private DateTimeImmutable $expiresAt;

    public function __construct(
        private string $id,
        private string $userId,
        private DateTimeImmutable $createdAt,
        ?DateTimeImmutable $expiresAt = null
    ) {
        $this->expiresAt =
            $expiresAt ?? $this->createdAt->modify('+' . self::DEFAULT_TTL_MINUTES . ' minutes');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(?DateTimeImmutable $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTimeImmutable();

        return $currentTime > $this->expiresAt;
    }
}
