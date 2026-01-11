<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class ConfirmPasswordResetDto
{
    public function __construct(
        #[\SensitiveParameter]
        public string $token = '',
        #[\SensitiveParameter]
        public string $newPassword = ''
    ) {
    }
}
