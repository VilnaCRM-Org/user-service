<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class DisableTwoFactorDto
{
    public function __construct(
        public string $twoFactorCode = '',
    ) {
    }
}
