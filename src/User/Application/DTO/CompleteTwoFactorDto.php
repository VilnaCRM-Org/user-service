<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final readonly class CompleteTwoFactorDto
{
    public function __construct(
        public string $pendingSessionId = '',
        public string $twoFactorCode = '',
    ) {
    }

    public function pendingSessionIdValue(): string
    {
        return $this->pendingSessionId;
    }

    public function twoFactorCodeValue(): string
    {
        return $this->twoFactorCode;
    }
}
