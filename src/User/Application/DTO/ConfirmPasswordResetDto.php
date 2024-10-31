<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final class ConfirmPasswordResetDto
{
    public function __construct(
        public string $token,
        public string $newPassword
    ) {
    }
}
