<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final class RequestPasswordResetCommandResponse
{
    public function __construct(
        public readonly string $message
    ) {
    }
}
