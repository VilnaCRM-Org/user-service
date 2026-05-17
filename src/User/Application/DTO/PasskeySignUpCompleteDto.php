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
        public string $challengeId = '',
        public array $credential = [],
        public string $label = ''
    ) {
    }

    public function isRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function setRememberMe(bool $rememberMe): void
    {
        $this->rememberMe = $rememberMe;
    }
}
