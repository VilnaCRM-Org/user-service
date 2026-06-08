<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\User\Domain\Entity\PasskeyChallenge;

final readonly class PasskeyOptionsResult
{
    /**
     * @param array<string, scalar|array|null> $publicKeyOptions
     */
    public function __construct(
        private PasskeyChallenge $challenge,
        private array $publicKeyOptions
    ) {
    }

    public function getChallenge(): PasskeyChallenge
    {
        return $this->challenge;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    public function getPublicKeyOptions(): array
    {
        return $this->publicKeyOptions;
    }
}
