<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

/**
 * @psalm-api
 */
final class PasskeySignUpOptionsDto
{
    /**
     * @psalm-api
     */
    public function __construct(
        public string $email = '',
        public string $initials = '',
        public string $displayName = ''
    ) {
    }
}
