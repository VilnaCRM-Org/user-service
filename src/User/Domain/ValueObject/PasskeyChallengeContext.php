<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

final class PasskeyChallengeContext
{
    private ?string $email;
    private ?string $initials;
    private ?string $displayName;
    private ?string $userId;
    private bool $rememberMe = false;

    public function __construct(
        ?string $email = null,
        ?string $initials = null,
        ?string $displayName = null,
        ?string $userId = null
    ) {
        $this->email = $email;
        $this->initials = $initials;
        $this->displayName = $displayName;
        $this->userId = $userId;
    }

    public function withRememberMe(): self
    {
        $context = clone $this;
        $context->rememberMe = true;

        return $context;
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
}
