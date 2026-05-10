<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

/**
 * @psalm-api
 */
final class PasskeySignInCompleteDto
{
    /**
     * @param array<string, scalar|array|null> $credential
     *
     * @psalm-api
     */
    public function __construct(
        private string $challengeId = '',
        private array $credential = []
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
}
