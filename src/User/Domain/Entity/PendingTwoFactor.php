<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

/**
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
final class PendingTwoFactor
{
    private const DEFAULT_TTL_MINUTES = 5;

    private DateTimeImmutable $expiresAt;

    public function __construct(
        private string $id,
        private string $userId,
        private DateTimeImmutable $createdAt,
        ?DateTimeImmutable $expiresAt = null,
        private bool $rememberMe = false
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

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function isExpired(?DateTimeImmutable $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTimeImmutable();

        return $currentTime > $this->expiresAt;
    }
}
