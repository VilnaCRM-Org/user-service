<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class ConfirmPasswordResetDto
{
    public function __construct(
        #[Groups(['confirm_password_reset:write'])]
        public ?string $token = null,
        #[Groups(['confirm_password_reset:write'])]
        public ?string $newPassword = null
    ) {
    }
}
