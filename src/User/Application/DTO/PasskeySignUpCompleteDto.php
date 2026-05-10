<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

/**
 * @psalm-api
 */
final class PasskeySignUpCompleteDto
{
    private bool $rememberMe = false;

    /**
     * @param array<string, scalar|array|null> $credential
     *
     * @psalm-api
     */
    public function __construct(
        private string $challengeId = '',
        private array $credential = [],
        private string $label = ''
    ) {
    }

    public function challengeIdValue(): string
    {
        return $this->challengeId;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    public function credentialValue(): array
    {
        return $this->credential;
    }

    public function labelValue(): string
    {
        return $this->label;
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    /**
     * @psalm-api
     */
    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }
}
