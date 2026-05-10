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
        public string $challengeId = '',
        public array $credential = []
    ) {
    }
}
