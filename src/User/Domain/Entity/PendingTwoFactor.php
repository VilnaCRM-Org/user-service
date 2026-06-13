<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use DateTimeImmutable;

final class PendingTwoFactor
{
    public const DEFAULT_TTL_MINUTES = 5;
    public const MAX_FAILED_ATTEMPTS = 5;

    private DateTimeImmutable $expiresAt;
    private bool $rememberMe = false;
    private int $failedAttempts = 0;

    public function __construct(
        private string $id,
        private string $userId,
        private DateTimeImmutable $createdAt,
        ?DateTimeImmutable $expiresAt = null
    ) {
        $this->expiresAt =
            $expiresAt ?? $this->createdAt->modify('+' . self::DEFAULT_TTL_MINUTES . ' minutes');
    }

    public function withRememberMe(): static
    {
        $clone = clone $this;
        $clone->rememberMe = true;

        return $clone;
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

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    /**
     * @internal For Doctrine ODM hydration and test fixtures only.
     */
    public function setFailedAttempts(int $failedAttempts): void
    {
        $this->failedAttempts = max(0, $failedAttempts);
    }

    public function recordFailedAttempt(): void
    {
        ++$this->failedAttempts;
    }

    public function hasExhaustedAttempts(): bool
    {
        return $this->failedAttempts >= self::MAX_FAILED_ATTEMPTS;
    }

    public function isExpired(?DateTimeImmutable $currentTime = null): bool
    {
        $currentTime = $currentTime ?? new DateTimeImmutable();

        return $currentTime > $this->expiresAt;
    }
}
