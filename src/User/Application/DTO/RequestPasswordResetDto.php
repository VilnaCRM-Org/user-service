<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class RequestPasswordResetDto
{
    public function __construct(
        #[Groups(['request_password_reset:write'])]
        public ?string $email = null
    ) {
    }
}
