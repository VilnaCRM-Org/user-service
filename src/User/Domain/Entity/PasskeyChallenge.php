<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;

final class PasskeyChallenge
{
    public const PURPOSE_SIGNUP = 'signup';
    public const PURPOSE_REGISTRATION = 'registration';
    public const PURPOSE_AUTHENTICATION = 'authentication';

    private ?DateTimeImmutable $consumedAt = null;
    private ?string $email = null;
    private ?string $initials = null;
    private ?string $displayName = null;
    private ?string $userId = null;
    private bool $rememberMe = false;

    public function __construct(
        private string $id,
        private string $purpose,
        private string $challenge,
        private string $options,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $expiresAt,
        ?PasskeyChallengeContext $context = null
    ) {
        if ($context === null) {
            return;
        }

        $this->email = $context->getEmail();
        $this->initials = $context->getInitials();
        $this->displayName = $context->getDisplayName();
        $this->userId = $context->getUserId();
        $this->rememberMe = $context->isRememberMe();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getChallenge(): string
    {
        return $this->challenge;
    }

    public function getOptions(): string
    {
        return $this->options;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getInitials(): ?string
    {
        return $this->initials;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function getConsumedAt(): ?DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now > $this->expiresAt;
    }

    public function isConsumed(): bool
    {
        return $this->consumedAt !== null;
    }

    public function consume(DateTimeImmutable $consumedAt): void
    {
        $this->consumedAt = $consumedAt;
    }
}
